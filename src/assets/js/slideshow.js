/* ============================================================================
   slideshow.js — Moteur du diaporama
   Fondu enchaîné à deux couches, autoplay, préchargement, contrôles, clavier,
   plein écran, masquage auto, et RAFRAÎCHISSEMENT À CHAUD : la liste est
   re-vérifiée périodiquement et les changements (ajout / suppression / ordre)
   sont appliqués au bouclage, sans recharger la page.
   ========================================================================== */

(function () {
    'use strict';

    const cfg = Object.assign(
        { interval: 5000, fade: 800, poll: 10000, uploads: 'uploads' },
        window.SLIDESHOW_CONFIG || {}
    );
    let slides = Array.isArray(window.SLIDES) ? window.SLIDES.slice() : [];

    document.documentElement.style.setProperty('--fade', cfg.fade + 'ms');

    const layers   = document.getElementById('layers');
    const bar      = document.getElementById('bar');
    const progress = document.getElementById('progress');
    const counter  = document.getElementById('counter');
    const controls = document.getElementById('controls');
    const emptyEl  = document.getElementById('empty');
    const playBtn  = document.getElementById('play');

    // Deux couches alternées pour le fondu
    const lyrs = [makeLayer(), makeLayer()];
    layers.append(lyrs[0], lyrs[1]);
    let front = 0;

    let current = 0, playing = true, timer = null, raf = null, t0 = 0;
    let pending = null, polling = null, errSkips = 0;

    function makeLayer() {
        const d = document.createElement('div');
        d.className = 'slide';
        const im = new Image();
        im.addEventListener('error', onImgError);
        d.appendChild(im);
        return d;
    }
    // Si une image a disparu (supprimée côté serveur pendant la boucle), on saute
    function onImgError() {
        if (slides.length > 1 && errSkips < slides.length) { errSkips++; swapTo(current + 1); }
    }

    function sig(list) { return list.map(function (s) { return s.id; }).join(','); }
    function srcFor(i) { return cfg.uploads + '/' + slides[i].file; }

    function paint(layerIdx, slideIdx) {
        const im = lyrs[layerIdx].firstElementChild;
        im.src = srcFor(slideIdx);
        im.alt = slides[slideIdx].name || '';
    }

    function updateCounter() {
        counter.textContent = slides.length ? (current + 1) + ' / ' + slides.length : '';
    }

    function setEmpty(on) {
        emptyEl.hidden = !on;
        progress.style.visibility = on ? 'hidden' : '';
        if (on) controls.classList.remove('show');
    }

    function preload(i) { const im = new Image(); im.src = srcFor(i); }

    // --- Fondu vers un index (sans logique de boucle) -----------------------
    function swapTo(index) {
        if (!slides.length) return;
        index = (index + slides.length) % slides.length;
        const back = 1 - front;
        paint(back, index);
        lyrs[back].classList.add('is-active');
        lyrs[front].classList.remove('is-active');
        front = back;
        current = index;
        errSkips = 0;
        updateCounter();
        if (slides.length > 1) preload((current + 1) % slides.length);
        restart();
    }

    // --- Avance, en appliquant les mises à jour en fin de boucle ------------
    function next() {
        if (!slides.length) return;
        if (pending && current === slides.length - 1) { applyPending(); return; }
        swapTo(current + 1);
    }
    function prev() { if (slides.length) swapTo(current - 1); }

    // Applique une liste en attente : repart de la première slide
    function applyPending() {
        const incoming = pending; pending = null;
        slides = incoming;
        if (!slides.length) { goEmpty(); return; }
        setEmpty(false);
        const back = 1 - front;
        current = 0;
        paint(back, 0);
        lyrs[back].classList.add('is-active');
        lyrs[front].classList.remove('is-active');
        front = back;
        errSkips = 0;
        updateCounter();
        if (slides.length > 1) preload(1 % slides.length);
        restart();
    }

    function goEmpty() {
        stopAuto();
        lyrs[0].classList.remove('is-active');
        lyrs[1].classList.remove('is-active');
        current = 0;
        setEmpty(true);
        updateCounter();
    }

    // --- Autoplay + barre de progression ------------------------------------
    function restart() {
        clearTimeout(timer); cancelAnimationFrame(raf);
        if (!playing || slides.length < 2) { bar.style.width = '0%'; return; }
        t0 = performance.now();
        tickProgress();
        timer = setTimeout(next, cfg.interval);
    }
    function stopAuto() { clearTimeout(timer); cancelAnimationFrame(raf); bar.style.width = '0%'; }
    function tickProgress() {
        const pct = Math.min(100, (performance.now() - t0) / cfg.interval * 100);
        bar.style.width = pct + '%';
        if (pct < 100 && playing) raf = requestAnimationFrame(tickProgress);
    }
    function setPlaying(state) {
        playing = state;
        if (playBtn) playBtn.textContent = playing ? '❚❚' : '▶';
        if (playing) restart(); else stopAuto();
    }

    // --- Démarrage / état initial -------------------------------------------
    function boot() {
        if (!slides.length) {
            goEmpty();
        } else {
            setEmpty(false);
            current = 0;
            paint(front, 0);
            lyrs[front].classList.add('is-active');
            updateCounter();
            if (slides.length > 1) preload(1 % slides.length);
            restart();
        }
        startPolling();
    }

    // --- Vérification périodique des changements ----------------------------
    function startPolling() {
        if (polling) return;
        polling = setInterval(checkUpdates, cfg.poll);
    }
    async function checkUpdates() {
        if (document.hidden) return;
        try {
            const res = await fetch('api/slides.php', { cache: 'no-store' });
            if (!res.ok) return;
            const list = await res.json();
            if (!Array.isArray(list)) return;

            if (sig(list) === sig(slides)) { pending = null; return; } // en phase

            if (!slides.length) {
                // Le diaporama était vide : on démarre immédiatement
                slides = list; pending = null;
                setEmpty(false);
                front = 0; current = 0;
                paint(0, 0);
                lyrs[0].classList.add('is-active');
                lyrs[1].classList.remove('is-active');
                updateCounter();
                if (slides.length > 1) preload(1 % slides.length);
                restart();
            } else {
                pending = list; // appliqué au prochain bouclage
            }
        } catch (e) { /* réseau indisponible : on réessaiera au prochain tick */ }
    }

    // --- Contrôles ----------------------------------------------------------
    document.getElementById('next').addEventListener('click', next);
    document.getElementById('prev').addEventListener('click', prev);
    playBtn.addEventListener('click', function () { setPlaying(!playing); });
    document.getElementById('fs').addEventListener('click', toggleFullscreen);

    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen && document.documentElement.requestFullscreen();
        } else {
            document.exitFullscreen && document.exitFullscreen();
        }
    }

    // --- Clavier ------------------------------------------------------------
    document.addEventListener('keydown', function (e) {
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
        if (slides.length) controls.classList.add('show');
        document.body.classList.remove('idle');
        clearTimeout(idleTimer);
        idleTimer = setTimeout(function () {
            controls.classList.remove('show');
            document.body.classList.add('idle');
        }, 2800);
    }
    ['mousemove', 'touchstart', 'keydown'].forEach(function (ev) {
        document.addEventListener(ev, wake, { passive: true });
    });
    wake();

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) stopAuto();
        else if (playing && slides.length) restart();
    });

    boot();
})();