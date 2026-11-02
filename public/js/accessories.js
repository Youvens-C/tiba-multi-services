document.addEventListener('DOMContentLoaded', function () {

    // ── Vente rapide : total en direct, stock affiché en indice dynamique ──
    const sellProduct = document.getElementById('sellProduct');
    const sellQuantity = document.getElementById('sellQuantity');
    const sellTotal = document.getElementById('sellTotal');
    const sellStockHint = document.getElementById('sellStockHint');
    const sellSubmitBtn = document.getElementById('sellSubmitBtn');

    function recalcSale() {
        if (!sellProduct || !sellProduct.value) {
            sellTotal.innerHTML = '<span class="text-muted">Choisissez un produit pour voir le total.</span>';
            sellStockHint.textContent = '';
            sellSubmitBtn.disabled = true;
            return;
        }

        const opt = sellProduct.selectedOptions[0];
        const price = parseFloat(opt.dataset.price);
        const stock = parseInt(opt.dataset.stock, 10);
        const qty = parseInt(sellQuantity.value, 10);

        sellQuantity.max = stock;
        sellStockHint.classList.remove('text-danger');
        sellStockHint.textContent = `Stock disponible : ${stock}.`;

        if (!qty || qty <= 0) {
            sellTotal.innerHTML = '<span class="text-muted">Saisissez une quantité.</span>';
            sellSubmitBtn.disabled = true;
            return;
        }

        if (qty > stock) {
            sellStockHint.textContent = `Stock disponible : ${stock} (quantité trop élevée).`;
            sellStockHint.classList.add('text-danger');
            sellSubmitBtn.disabled = true;
            sellTotal.innerHTML = '<span class="text-danger fw-semibold">Quantité supérieure au stock disponible.</span>';
            return;
        }

        sellSubmitBtn.disabled = false;
        sellTotal.innerHTML = `<strong>${(price * qty).toLocaleString('fr-FR', {minimumFractionDigits:2})} HTG</strong>`;
    }

    if (sellProduct) {
        sellProduct.addEventListener('change', recalcSale);
        sellQuantity.addEventListener('input', recalcSale);
    }

    // ── Modale Modifier produit : pré-remplissage ─────────────────────────
    document.querySelectorAll('.js-edit-product').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('editProductForm').action = btn.dataset.url;
            document.getElementById('editName').value = btn.dataset.name || '';
            document.getElementById('editDescription').value = btn.dataset.description || '';
            document.getElementById('editPrice').value = btn.dataset.price || '';
            document.getElementById('editThreshold').value = btn.dataset.threshold || '';
            new bootstrap.Modal(document.getElementById('editProductModal')).show();
        });
    });

    // ── Modale Ajuster le stock : pré-remplissage + bascule Ajouter/Retirer ──
    const adjustTypeInput = document.getElementById('adjustType');
    document.querySelectorAll('.movement-btn-group .movement-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.movement-btn-group .movement-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            adjustTypeInput.value = btn.dataset.type;
        });
    });

    document.querySelectorAll('.js-adjust-stock').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('adjustStockForm').action = btn.dataset.url;
            document.getElementById('adjustStockProductName').textContent =
                `${btn.dataset.name} — stock actuel : ${btn.dataset.stock}`;
            document.getElementById('adjustQuantity').value = '';
            new bootstrap.Modal(document.getElementById('adjustStockModal')).show();
        });
    });

    // ── Confirmation avant désactivation d'un produit ─────────────────────
    document.querySelectorAll('.js-confirm-toggle').forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            confirmAction(form, {
                title: 'Désactiver ce produit ?',
                message: "Il ne sera plus proposé à la vente tant qu'il n'aura pas été réactivé. L'historique des ventes déjà faites reste intact.",
                confirmLabel: 'Oui, désactiver',
            });
        });
    });
});

// ── Modale de correction d'une vente ─────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('editSaleModal');
    if (!modalEl) return;
    const form = document.getElementById('editSaleForm');
    const qty = document.getElementById('editSaleQuantity');
    const total = document.getElementById('editSaleTotal');
    const info = document.getElementById('editSaleInfo');
    let unitPrice = 0;

    function recalc() {
        const q = parseInt(qty.value, 10);
        total.innerHTML = (!q || q <= 0)
            ? '<span class="text-muted">Saisissez une quantité.</span>'
            : `<div class="d-flex justify-content-between"><span>Nouveau total</span><strong>${(q * unitPrice).toLocaleString('fr-FR', {minimumFractionDigits:2})} HTG</strong></div>`;
    }

    document.querySelectorAll('.js-edit-sale').forEach(btn => {
        btn.addEventListener('click', () => {
            form.action = btn.dataset.url;
            unitPrice = parseFloat(btn.dataset.price);
            qty.value = btn.dataset.quantity;
            info.textContent = `${btn.dataset.name} — ${unitPrice.toLocaleString('fr-FR', {minimumFractionDigits:2})} HTG l'unité (prix non modifiable)`;
            recalc();
            new bootstrap.Modal(modalEl).show();
        });
    });
    qty.addEventListener('input', recalc);
});
