function tierFor(q) {
    if (typeof pricingGrid !== 'undefined' && Array.isArray(pricingGrid)) {
        for (const tier of pricingGrid) {
            if (q >= tier.min && (tier.max === null || q <= tier.max)) {
                return { u: tier.price, n: tier.label };
            }
        }
    }
    return { u: 4.90, n: 'Découverte' };
}

function eur(n) {
    return n.toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' €';
}

function updateCalc() {
    const slider = document.getElementById('qtySlider');
    if (!slider) return;

    const q = +slider.value, t = tierFor(q);
    document.getElementById('qtyVal').textContent = q;
    document.getElementById('tierBadge').textContent = 'Tarif ' + t.n;
    document.getElementById('unitVal').textContent = t.u.toLocaleString('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' €';
    document.getElementById('totalVal').textContent = eur(q * t.u);
    document.getElementById('saveVal').textContent = eur(q * (4.90 - t.u));
    document.getElementById('buyQty').value = q;
}

function setQty(q) {
    const slider = document.getElementById('qtySlider');
    if (slider) {
        slider.value = q;
        updateCalc();
        document.querySelector('.calc').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    updateCalc();

    document.getElementById('qtySlider')?.addEventListener('input', updateCalc);

    document.getElementById('btnBuy')?.addEventListener('click', () => {
        document.getElementById('buyForm').submit();
    });

    document.querySelectorAll('.pack-card').forEach(pack => {
        pack.addEventListener('click', () => {
            const qty = pack.dataset.qty;
            if (qty) setQty(qty);
        });
    });
});