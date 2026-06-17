'use strict';

// ── Toast ─────────────────────────────────────────────────────────────────
window.showToast = function(msg, type = 'success', duration = 3200) {
    let t = document.getElementById('er-toast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'er-toast';
        t.className = 'er-toast';
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.className = 'er-toast show';
    if (type === 'error') t.style.background = '#dc2626';
    else if (type === 'info') t.style.background = '#2747DC';
    else t.style.background = '#12182B';

    clearTimeout(t._timer);
    t._timer = setTimeout(() => { t.className = 'er-toast'; }, duration);
};

// ── Autocomplete assureur — VERSION CORRIGÉE ──────────────────────────────
(function initAssureurAutocomplete() {
    const input   = document.getElementById('assureur-search');
    const list    = document.getElementById('assureur-list');
    const hidden  = document.getElementById('assureur-hidden');
    const nextBtn = document.getElementById('assureur-next-btn');

    if (!input || !list) return; // pas sur la page assureur

    const apiUrl = input.dataset.apiUrl;
    let debounceTimer = null;
    let selectedName  = hidden ? hidden.value : '';

    // Activer le bouton si une valeur est déjà sélectionnée
    if (selectedName && nextBtn) nextBtn.disabled = false;

    function closeList() {
        list.classList.remove('open');
        list.innerHTML = '';
    }

    function selectAssureur(name) {
        selectedName      = name;
        input.value       = name;
        if (hidden)  hidden.value = name;
        if (nextBtn) nextBtn.disabled = false;
        closeList();
        // Auto-submit après sélection
        const form = document.getElementById('assureur-form');
        if (form) form.submit();
    }

    function renderResults(items) {
        list.innerHTML = '';
        if (!items.length) {
            list.innerHTML = '<div class="er-autocomplete-item text-muted">Aucun résultat</div>';
            list.classList.add('open');
            return;
        }
        items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'er-autocomplete-item';
            div.textContent = item.name;
            div.dataset.name = item.name;
            // Utiliser click avec une closure propre (évite le bug de fermeture de variable)
            div.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                selectAssureur(div.dataset.name);
            });
            list.appendChild(div);
        });
        list.classList.add('open');
    }

    function search(q) {
        if (!q || q.length < 2) { closeList(); return; }
        fetch(apiUrl + '?q=' + encodeURIComponent(q))
            .then(r => {
                if (!r.ok) throw new Error('Erreur réseau');
                return r.json();
            })
            .then(data => renderResults(data))
            .catch(err => {
                console.error('Autocomplete error:', err);
                showToast('Erreur lors de la recherche.', 'error');
            });
    }

    input.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        if (hidden) hidden.value = '';
        if (nextBtn) nextBtn.disabled = true;
        debounceTimer = setTimeout(() => search(this.value.trim()), 280);
    });

    input.addEventListener('keydown', function(e) {
        const items = list.querySelectorAll('.er-autocomplete-item');
        const highlighted = list.querySelector('.highlighted');
        let idx = -1;
        if (highlighted) {
            items.forEach((el, i) => { if (el === highlighted) idx = i; });
            highlighted.classList.remove('highlighted');
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            const next = items[idx + 1] || items[0];
            if (next) { next.classList.add('highlighted'); next.scrollIntoView({block:'nearest'}); }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            const prev = items[idx - 1] || items[items.length - 1];
            if (prev) { prev.classList.add('highlighted'); prev.scrollIntoView({block:'nearest'}); }
        } else if (e.key === 'Enter' && highlighted) {
            e.preventDefault();
            selectAssureur(highlighted.dataset.name);
        } else if (e.key === 'Escape') {
            closeList();
        }
    });

    // Fermer la liste si clic en dehors
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !list.contains(e.target)) {
            closeList();
        }
    });
})();

// ── Wizard : type de contrat ──────────────────────────────────────────────
(function initContratType() {
    const filterInput = document.getElementById('type-search');
    const form        = document.getElementById('contrat-type-form');
    if (!filterInput || !form) return;

    filterInput.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.er-ctype-item').forEach(btn => {
            btn.style.display = btn.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    window.selectContratType = function(key) {
        const hidden = document.getElementById('type-contrat-hidden');
        if (hidden) { hidden.value = key; form.submit(); }
    };
})();

// ── Récap : signature + envoi ─────────────────────────────────────────────
window.signWithUniversign = function(signUrl, csrfToken) {
    const btn = document.getElementById('sign-btn');
    if (btn) { btn.disabled = true; btn.textContent = 'Signature en cours...'; }

    fetch(signUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) throw new Error(data.error);
        // En production: ouvrir l'URL Universign
        // window.open(data.signature_url, '_blank');
        document.getElementById('sign-status').textContent = 'Signé via Universign (eIDAS)';
        document.getElementById('sign-status').className = 'text-success fw-bold';
        const sendBtn = document.getElementById('send-btn');
        if (sendBtn) sendBtn.disabled = false;
        if (btn) btn.style.display = 'none';
        showToast('Signature complétée.', 'success');
    })
    .catch(err => {
        showToast(err.message || 'Erreur lors de la signature.', 'error');
        if (btn) { btn.disabled = false; btn.textContent = 'Signer via Universign'; }
    });
};

window.sendLre = function(sendUrl, csrfToken) {
    const btn = document.getElementById('send-btn');
    if (btn) { btn.disabled = true; btn.textContent = 'Envoi en cours...'; }

    fetch(sendUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) throw new Error(data.error);
        showToast('LRE envoyée avec succès !');
        if (data.redirect) setTimeout(() => { window.location.href = data.redirect; }, 1000);
    })
    .catch(err => {
        showToast(err.message || 'Erreur lors de l\'envoi.', 'error');
        if (btn) { btn.disabled = false; btn.textContent = 'Envoyer en LRE qualifiée'; }
    });
};

// ── Dashboard : lignes cliquables ─────────────────────────────────────────
document.querySelectorAll('tr[data-href]').forEach(tr => {
    tr.style.cursor = 'pointer';
    tr.addEventListener('click', function(e) {
        if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON') return;
        window.location.href = this.dataset.href;
    });
    tr.setAttribute('tabindex', '0');
    tr.addEventListener('keydown', e => { if (e.key === 'Enter') tr.click(); });
});

// ── Code de vérification email (6 chiffres) ───────────────────────────────
document.querySelectorAll('.er-code-input').forEach((el, i, arr) => {
    el.addEventListener('input', () => {
        if (el.value && arr[i + 1]) arr[i + 1].focus();
    });
    el.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !el.value && arr[i - 1]) arr[i - 1].focus();
    });
});

const codeForm = document.getElementById('code-form');
if (codeForm) {
    codeForm.addEventListener('submit', function() {
        const hidden = document.getElementById('code-hidden');
        if (hidden) {
            hidden.value = [...document.querySelectorAll('.er-code-input')]
                .map(e => e.value).join('');
        }
    });
}

// ── Packs : slider dégressif ──────────────────────────────────────────────
(function initPackSlider() {
    const slider = document.getElementById('qty-slider');
    if (!slider) return;

    const pricingApiUrl = slider.dataset.pricingUrl;

    function tierFor(q) {
        if (q >= 600) return { price: 1.95, label: 'Grand compte' };
        if (q >= 300) return { price: 2.40, label: 'Volume' };
        if (q >= 100) return { price: 2.90, label: 'Pro' };
        if (q >= 25)  return { price: 3.90, label: 'Avantage' };
        return { price: 4.90, label: 'Standard' };
    }

    function eur(n) { return n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'; }

    function update() {
        const q = parseInt(slider.value);
        const t = tierFor(q);
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
        set('qty-display', q);
        set('tier-badge', 'Tarif ' + t.label);
        set('unit-price', eur(t.price));
        set('total-ht',   eur(q * t.price));
        set('economy',    eur(q * (4.90 - t.price)));
        const hidden = document.getElementById('buy-qty');
        if (hidden) hidden.value = q;
    }

    slider.addEventListener('input', update);
    update();

    window.setPackQty = function(q) {
        slider.value = q;
        update();
        slider.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };
})();

// ── Auto-dismiss des alertes Bootstrap ───────────────────────────────────
document.querySelectorAll('.alert.alert-dismissible').forEach(el => {
    setTimeout(() => {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
        if (bsAlert) bsAlert.close();
    }, 5000);
});
