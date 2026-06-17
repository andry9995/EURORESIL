/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

// ═══════════════════════════════════════════════════════════════════
//  EURORESIL — JS principal (wizard, toast, UI interactions)
// ═══════════════════════════════════════════════════════════════════

// ── Toast notification ───────────────────────────────────────────
function showToast(msg, type = 'success', duration = 3200) {
    const t = document.getElementById('toast');
    if (!t) return;
    t.textContent = msg;
    t.className = 'toast show ' + type;
    clearTimeout(t._timer);
    t._timer = setTimeout(() => { t.className = 'toast'; }, duration);
}

// ── Wizard : stepper visuel ──────────────────────────────────────
document.querySelectorAll('.stepper .sg.done').forEach(el => {
    el.style.cursor = 'pointer';
});

// ── Assureur : fermeture liste au clic extérieur ─────────────────
document.addEventListener('click', e => {
    const list = document.getElementById('insList');
    const search = document.getElementById('insSearch');
    if (list && search && !list.contains(e.target) && e.target !== search) {
        list.innerHTML = '';
    }
});

// ── Packs : slider de quantité ────────────────────────────────────
const slider = document.getElementById('qtySlider');
if (slider) {
    slider.addEventListener('input', function () {
        if (typeof updateCalc === 'function') updateCalc();
    });
}

// ── Récap : sauvegarde auto du texte additionnel ──────────────────
const extraTextArea = document.getElementById('extraText');
if (extraTextArea) {
    let saveTimer;
    extraTextArea.addEventListener('input', function () {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => {
            const form = this.closest('div.wizard');
            const saveUrl = form?.dataset?.saveUrl;
            if (!saveUrl) return;
            fetch(saveUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=save_extra&extra_text=' + encodeURIComponent(this.value)
            }).then(() => showToast('Texte sauvegardé', 'info', 2000));
        }, 1000);
    });
}

// ── Dashboard : lignes cliquables ────────────────────────────────
document.querySelectorAll('tr.row[onclick]').forEach(tr => {
    tr.style.cursor = 'pointer';
    tr.addEventListener('keydown', e => {
        if (e.key === 'Enter') tr.click();
    });
    tr.setAttribute('tabindex', '0');
});

// ── Flash auto-dismiss ────────────────────────────────────────────
document.querySelectorAll('.flash').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .5s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 500);
    }, 5000);
});

// ── CSS des tags statut (injectés par Twig, styler par JS si pas en CSS) ──
const tagColors = {
    draft:    { bg: '#F4F3EC', color: '#6B655B', dot: '#C9C5BB' },
    signing:  { bg: '#FFF4DC', color: '#B45309', dot: '#F59E0B' },
    sending:  { bg: '#EFF6FF', color: '#2747DC', dot: '#60A5FA' },
    sent:     { bg: '#EFF6FF', color: '#2747DC', dot: '#2747DC' },
    received: { bg: '#DCFCE7', color: '#16a34a', dot: '#22c55e' },
    failed:   { bg: '#FEE2E2', color: '#DC2626', dot: '#EF4444' },
    recu:     { bg: '#DCFCE7', color: '#16a34a', dot: '#22c55e' },
};

document.querySelectorAll('.tag').forEach(el => {
    const classes = [...el.classList];
    for (const key of Object.keys(tagColors)) {
        if (classes.includes(key)) {
            const c = tagColors[key];
            el.style.background = c.bg;
            el.style.color       = c.color;
            const dot = el.querySelector('i');
            if (dot) { dot.style.background = c.dot; }
        }
    }
});

