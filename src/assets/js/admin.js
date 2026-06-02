/* ============================================================================
   admin.js — Logique du back-office
   - Upload (clic + glisser-déposer) vers api/upload.php
   - Réordonnancement par glisser-déposer (SortableJS) -> api/save_order.php
   - Suppression -> api/delete.php
   ========================================================================== */

(function () {
    'use strict';

    const grid      = document.getElementById('grid');
    const dropzone  = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');
    const browseBtn = document.getElementById('browseBtn');
    const progress  = document.getElementById('progress');
    const countEl   = document.getElementById('count');
    const emptyEl   = document.getElementById('empty');
    const toastEl   = document.getElementById('toast');

    // ---------------------------------------------------------------- Toast
    let toastTimer;
    function toast(message, type) {
        toastEl.textContent = message;
        toastEl.className = 'toast show ' + (type ? 'toast--' + type : '');
        toastEl.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toastEl.classList.remove('show'); }, 2600);
    }

    // ---------------------------------------------------------------- Compteur
    function refreshCount() {
        const n = grid.querySelectorAll('.card').length;
        countEl.textContent = n;
        emptyEl.hidden = n > 0;
    }

    // ---------------------------------------------------------------- Upload
    function browse() { fileInput.click(); }
    browseBtn && browseBtn.addEventListener('click', (e) => { e.stopPropagation(); browse(); });
    dropzone.addEventListener('click', browse);
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) uploadFiles(fileInput.files);
        fileInput.value = '';
    });

    ['dragenter', 'dragover'].forEach(ev =>
        dropzone.addEventListener(ev, (e) => {
            e.preventDefault(); dropzone.classList.add('is-drag');
        }));
    ['dragleave', 'drop'].forEach(ev =>
        dropzone.addEventListener(ev, (e) => {
            e.preventDefault();
            if (ev === 'drop' || e.target === dropzone) dropzone.classList.remove('is-drag');
        }));
    dropzone.addEventListener('drop', (e) => {
        const files = e.dataTransfer && e.dataTransfer.files;
        if (files && files.length) uploadFiles(files);
    });

    async function uploadFiles(fileList) {
        const fd = new FormData();
        for (const f of fileList) fd.append('files[]', f);

        progress.hidden = false;
        progress.textContent = 'Envoi de ' + fileList.length + ' fichier(s)…';

        try {
            const res  = await fetch('api/upload.php', { method: 'POST', body: fd });
            const text = await res.text();

            // Réponse non-JSON = erreur serveur (typiquement une 500 PHP)
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Réponse upload non-JSON', res.status, text);
                toast('Erreur serveur ' + res.status + ' — voir la console', 'error');
                return;
            }

            if (res.status === 401 || data.error === 'unauthorized') {
                toast('Session expirée — reconnectez-vous', 'error');
                return;
            }

            const added  = data.added  || [];
            const errors = data.errors || [];

            added.forEach(addCard);
            if (added.length) refreshCount();

            if (errors.length) {
                console.warn('Fichiers refusés', errors);
                const why = errors[0] && errors[0].error ? ' (' + errors[0].error + ')' : '';
                toast(errors.length + ' fichier(s) refusé(s)' + why, 'error');
            } else if (added.length) {
                toast(added.length + ' slide(s) ajoutée(s)', 'ok');
            } else {
                toast('Aucun fichier traité' + (data.error ? ' (' + data.error + ')' : ''), 'error');
            }
        } catch (err) {
            console.error(err);
            toast('Erreur réseau', 'error');
        } finally {
            progress.hidden = true;
        }
    }

    // ------------------------------------------------------- Création de carte
    function addCard(slide) {
        const li = document.createElement('li');
        li.className = 'card';
        li.dataset.id = slide.id;
        li.innerHTML = `
            <div class="card__thumb">
                <img src="uploads/${esc(slide.file)}" alt="${esc(slide.name || '')}" loading="lazy">
                <span class="card__handle" title="Glisser pour réordonner">⠿</span>
            </div>
            <div class="card__foot">
                <span class="card__name" title="${esc(slide.name || '')}">${esc(slide.name || '')}</span>
                <button class="card__del" data-id="${esc(slide.id)}" title="Supprimer" aria-label="Supprimer">✕</button>
            </div>`;
        grid.appendChild(li);
    }

    function esc(s) {
        return String(s).replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    // ----------------------------------------------------------- Suppression
    grid.addEventListener('click', async (e) => {
        const btn = e.target.closest('.card__del');
        if (!btn) return;
        const card = btn.closest('.card');
        const id   = btn.dataset.id;
        if (!confirm('Supprimer cette slide ?')) return;

        try {
            const res  = await fetch('api/delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const data = await res.json();
            if (data.ok) {
                card.remove();
                refreshCount();
                toast('Slide supprimée', 'ok');
            } else {
                toast('Suppression impossible', 'error');
            }
        } catch (err) {
            toast('Erreur réseau', 'error');
        }
    });

    // ------------------------------------------------- Réordonnancement (drag)
    let saveTimer;
    Sortable.create(grid, {
        animation: 160,
        handle: '.card__handle',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: scheduleSaveOrder
    });

    function scheduleSaveOrder() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveOrder, 350);
    }

    async function saveOrder() {
        const ids = [...grid.querySelectorAll('.card')].map(c => c.dataset.id);
        try {
            const res  = await fetch('api/save_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids })
            });
            const data = await res.json();
            toast(data.ok ? 'Ordre enregistré' : 'Échec de l\'enregistrement',
                data.ok ? 'ok' : 'error');
        } catch (err) {
            toast('Erreur réseau', 'error');
        }
    }

    refreshCount();
})();