/* ============================================================================
   slideshow.js — Moteur du diaporama
   Fondu enchaîné à deux couches, autoplay, préchargement de la slide suivante,
   contrôles, navigation clavier, plein écran, masquage auto en cas d'inactivité.
   ========================================================================== */

(function () {
    'use strict';

    const slides = Array.isArray(window.SLIDES) ? window.SLIDES : [];
    const cfg = Object.assign(
        { interval: 5000, fade: 800, uploads: 'uploads' },
        window.SLIDESHOW_CONFIG || {}
    );
    if (slides.length === 0) return;

    // Applique la durée de fondu issue de la config PHP
    document.documentElement.style.setProperty('--fade', cfg.fade + 'ms');

    const layers   = document.getElementById('layers');
    const bar      = document.getElementById('bar');
    const counter  = document.getElementById('counter');
    const controls = document.getElementById('controls');

    let current = 0;
    let playing = true;
    let timer   = null;
    let progressStart = 0;
    let rafId = null;

    // --- Deux couches alternées pour le fondu -------------------------------
    const a = makeLayer();
    const b = makeLayer();
    layers.append(a, b);
    let front = a, back = b;

    function makeLayer() {
        const div = document.createElement('div');
        div.className = 'slide';
        div.appendChild(new Image());
        return div;
    }

    function srcFor(i) { return cfg.uploads + '/' + slides[i].file; }

    // Affiche immédiatement la première slide
    front.querySelector('img').src = srcFor(0);
    front.querySelector('img').alt = slides[0].name || '';
    front.classList.add('is-active');
    updateCounter();
    preload((current + 1) % slides.length);

    // --- Transition vers un index donné -------------------------------------
    function goTo(index, resetTimer = true) {
        index = (index + slides.length) % slides.length;
        if (index === current && resetTimer) { restart(); return; }

        const img = back.querySelector('img');
        img.src = srcFor(index);
        img.alt = slides[index].name || '';

        // bascule des couches
        back.classList.add('is-active');
        front.classList.remove('is-active');
        [front, back] = [back, front];

        current = index;
        updateCounter();
        preload((current + 1) % slides.length);
        if (resetTimer) restart();
    }

    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    function preload(i) { const im = new Image(); im.src = srcFor(i); }

    function updateCounter() {
        counter.textContent = (current + 1) + ' / ' + slides.length;
    }

    // --- Autoplay + barre de progression ------------------------------------
    function restart() {
        clearTimeout(timer);
        cancelAnimationFrame(rafId);
        if (!playing) { bar.style.width = '0%'; return; }
        progressStart = performance.now();
        tickProgress();
        timer = setTimeout(next, cfg.interval);
    }

    function tickProgress() {
        const elapsed = performance.now() - progressStart;
        const pct = Math.min(100, (elapsed / cfg.interval) * 100);
        bar.style.width = pct + '%';
        if (pct < 100 && playing) rafId = requestAnimationFrame(tickProgress);
    }

    function setPlaying(state) {
        playing = state;
        playBtn.textContent = playing ? '❚❚' : '▶';
        if (playing) restart();
        else { clearTimeout(timer); cancelAnimationFrame(rafId); }
    }

    // --- Contrôles ----------------------------------------------------------
    const playBtn = document.getElementById('play');
    document.getElementById('next').addEventListener('click', next);
    document.getElementById('prev').addEventListener('click', prev);
    playBtn.addEventListener('click', () => setPlaying(!playing));
    document.getElementById('fs').addEventListener('click', toggleFullscreen);

    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen && document.documentElement.requestFullscreen();
        } else {
            document.exitFullscreen && document.exitFullscreen();
        }
    }

    // --- Clavier ------------------------------------------------------------
    document.addEventListener('keydown', (e) => {
        switch (e.key) {
            case 'ArrowRight': next(); break;
            case 'ArrowLeft':  prev(); break;
            case ' ':          e.preventDefault(); setPlaying(!playing); break;
            case 'f': case 'F': toggleFullscreen(); break;
        }
    });

    // --- Masquage auto des contrôles et du curseur --------------------------
    let idleTimer;
    function wake() {
        controls.classList.add('show');
        document.body.classList.remove('idle');
        clearTimeout(idleTimer);
        idleTimer = setTimeout(() => {
            controls.classList.remove('show');
            document.body.classList.add('idle');
        }, 2800);
    }
    ['mousemove', 'touchstart', 'keydown'].forEach(ev =>
        document.addEventListener(ev, wake, { passive: true }));
    wake();

    // Met en pause l'autoplay quand l'onglet n'est pas visible
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) { clearTimeout(timer); cancelAnimationFrame(rafId); }
        else if (playing) restart();
    });

    // Démarrage
    restart();
})();
