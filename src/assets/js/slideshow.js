/* ============================================================================
   slideshow.js — Moteur du diaporama (affichage en deux volets)
   Volet gauche : diaporama (fondu, autoplay, contrôles, clavier, plein écran).
   Volet droit : image fixe optionnelle.
   Rafraîchissement à chaud via api/state.php : les slides changent au bouclage,
   l'image fixe est mise à jour immédiatement, sans recharger la page.
   ========================================================================== */

(function () {
    'use strict';

    const cfg = Object.assign(
        { interval: 5000, fade: 800, poll: 10000, uploads: 'uploads' },
        window.SLIDESHOW_CONFIG || {}
    );
    let slides = Array.isArray(window.SLIDES) ? window.SLIDES.slice() : [];

    document.documentElement.style.setProperty('--fade', cfg.fade + 'ms');

    const split    = document.getElementById('split');
    const fixedImg  = document.getElementById('fixedImg');
    const layers   = document.getElementById('layers');
    const bar      = document.getElementById('bar');
    const progress = document.getElementById('progress');
    const counter  = document.getElementById('counter');
    const controls = document.getElementById('controls');
    const emptyEl  = document.getElementById('empty');
    const playBtn  = document.getElementById('play');

    const lyrs = [makeLayer(), makeLayer()];
    layers.append(lyrs[0], lyrs[1]);
    let front = 0;

    let current = 0, playing = true, timer = null, raf = null, t0 = 0;
    let pending = null, polling = null, errSkips = 0;
    let fixedSig = window.FIXED && window.FIXED.file ? window.FIXED.file : '';

    function makeLayer() {
        const d = document.createElement('div');
        d.className = 'slide';
        const im = new Image();
        im.addEventListener('error', onImgError);
        d.appendChild(im);
        return d;
    }
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

    // --- Volet droit : image fixe -------------------------------------------
    function applyFixed(fx) {
        const has = fx && fx.file;
        split.classList.toggle('has-fixed', !!has);
        if (has) {
            fixedImg.src = cfg.uploads + '/' + fx.file;
            fixedImg.alt = fx.name || '';
            fixedImg.hidden = false;
        } else {
            fixedImg.removeAttribute('src');
            fixedImg.hidden = true;
        }
        fixedSig = has ? fx.file : '';
    }

    // --- Fondu vers un index ------------------------------------------------
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

    function next() {
        if (!slides.length) return;
        if (pending && current === slides.length - 1) { applyPending(); return; }
        swapTo(current + 1);
    }
    function prev() { if (slides.length) swapTo(current - 1); }

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

    // --- Démarrage ----------------------------------------------------------
    function boot() {
        applyFixed(window.FIXED);
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

    // --- Vérification périodique (slides + image fixe) ----------------------
    function startPolling() {
        if (polling) return;
        polling = setInterval(checkUpdates, cfg.poll);
    }
    async function checkUpdates() {
        if (document.hidden) return;
        try {
            const res = await fetch('api/state.php', { cache: 'no-store' });
            if (!res.ok) return;
            const state = await res.json();
            if (!state || typeof state !== 'object') return;

            // Image fixe : appliquée tout de suite (hors boucle)
            const fx = state.fixed || null;
            const newFixedSig = fx && fx.file ? fx.file : '';
            if (newFixedSig !== fixedSig) applyFixed(fx);

            // Slides : appliquées au bouclage
            const list = Array.isArray(state.slides) ? state.slides : [];
            if (sig(list) === sig(slides)) { pending = null; return; }

            if (!slides.length) {
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
                pending = list;
            }
        } catch (e) { /* réseau indisponible : on réessaiera */ }
    }

    // --- Contrôles / clavier / plein écran / inactivité ---------------------
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

    document.addEventListener('keydown', function (e) {
        switch (e.key) {
            case 'ArrowRight': next(); break;
            case 'ArrowLeft':  prev(); break;
            case ' ':          e.preventDefault(); setPlaying(!playing); break;
            case 'f': case 'F': toggleFullscreen(); break;
        }
    });

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