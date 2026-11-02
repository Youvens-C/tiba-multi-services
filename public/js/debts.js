document.addEventListener('DOMContentLoaded', function () {

    const fmt = (n, cur) => {
        const cents = Math.round(n * 100);
        const digits = cents % 100 === 0 ? 0 : 2;
        return n.toLocaleString('fr-FR', { minimumFractionDigits: digits, maximumFractionDigits: 2 }) + ' ' + cur;
    };
    const modal = (id) => bootstrap.Modal.getOrCreateInstance(document.getElementById(id));

    // ── Onglets Actives / Archives ───────────────────────────────────────
    document.querySelectorAll('.debt-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.debt-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.debt-pane').forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById(tab.dataset.pane).classList.add('active');
        });
    });

    // ── Filtre des archives ──────────────────────────────────────────────
    document.querySelectorAll('#archiveFilters .movement-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#archiveFilters .movement-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.js-archive-row').forEach(row => {
                row.style.display = (btn.dataset.filter === 'all' || row.dataset.status === btn.dataset.filter) ? '' : 'none';
            });
        });
    });

    // ── Nouvelle dette : choix de la devise ─────────────────────────────
    document.querySelectorAll('#createCurrencyGroup .movement-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#createCurrencyGroup .movement-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('createCurrency').value = btn.dataset.currency;
        });
    });

    // ── Modifier une dette ───────────────────────────────────────────────
    const editAmount = document.getElementById('editDebtAmount');
    const editHint = document.getElementById('editDebtHint');
    const editSubmit = document.getElementById('editDebtSubmit');
    let editPaid = 0, editCurrency = '';

    function checkEdit() {
        const v = parseFloat(editAmount.value);
        const tooLow = v && Math.round(v * 100) < Math.round(editPaid * 100);
        editHint.classList.toggle('text-danger', !!tooLow);
        editHint.textContent = tooLow
            ? `Le montant ne peut pas être inférieur au total déjà payé (${fmt(editPaid, editCurrency)}).`
            : `Déjà payé : ${fmt(editPaid, editCurrency)} — le solde restant sera recalculé automatiquement.`;
        editSubmit.disabled = !!tooLow;
    }

    document.querySelectorAll('.js-edit-debt').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('editDebtForm').action = btn.dataset.url;
            document.getElementById('editDebtName').value = btn.dataset.name;
            document.getElementById('editDebtReason').value = btn.dataset.reason;
            editAmount.value = btn.dataset.amount;
            editPaid = parseFloat(btn.dataset.paid) || 0;
            editCurrency = btn.dataset.currency;
            document.getElementById('editDebtCurrency').textContent = editCurrency;
            checkEdit();
            modal('editDebtModal').show();
        });
    });
    editAmount.addEventListener('input', checkEdit);

    // ── Paiement : aperçu en direct (partiel / exact / monnaie à rendre) ──
    const payAmount = document.getElementById('payAmount');
    const payPreview = document.getElementById('payPreview');
    const paySubmit = document.getElementById('paySubmit');
    let payBalance = 0, payCurrency = '', payName = '';

    function previewPayment() {
        const v = parseFloat(payAmount.value);
        if (!v || v <= 0) {
            payPreview.innerHTML = '<span class="text-muted">Saisissez le montant versé.</span>';
            paySubmit.disabled = true;
            return;
        }
        paySubmit.disabled = false;
        const paidCents = Math.round(v * 100);
        const balCents = Math.round(payBalance * 100);

        if (paidCents < balCents) {
            payPreview.innerHTML = `<div class="d-flex justify-content-between"><span>Nouveau montant dû</span><strong>${fmt((balCents - paidCents) / 100, payCurrency)}</strong></div>`;
        } else if (paidCents === balCents) {
            payPreview.innerHTML = '<strong style="color:var(--green-dark);">Dette intégralement payée !</strong>';
        } else {
            payPreview.innerHTML = `<strong style="color:var(--green-dark);">Dette intégralement payée !</strong>
                <div class="mt-1">Monnaie à rendre : <strong>${fmt((paidCents - balCents) / 100, payCurrency)}</strong></div>`;
        }
    }

    document.querySelectorAll('.js-pay-debt').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('payDebtForm').action = btn.dataset.url;
            payBalance = parseFloat(btn.dataset.balance);
            payCurrency = btn.dataset.currency;
            payName = btn.dataset.name;
            document.getElementById('payDebtInfo').textContent = `${payName} — solde actuel : ${fmt(payBalance, payCurrency)}`;
            payAmount.value = '';
            previewPayment();
            modal('payDebtModal').show();
        });
    });
    payAmount.addEventListener('input', previewPayment);

    // ── Historique des paiements (+ annulation d'un paiement mal saisi) ──
    const historyEl = document.getElementById('historyModal');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    document.querySelectorAll('.js-history').forEach(btn => {
        btn.addEventListener('click', () => {
            const payments = JSON.parse(btn.dataset.payments || '[]');
            const cur = btn.dataset.currency;
            const debtOpen = ['active', 'soldee'].includes(btn.dataset.status);
            document.getElementById('historyTitle').textContent = btn.dataset.name;
            const body = document.getElementById('historyBody');

            if (!payments.length) {
                body.innerHTML = '<p class="text-muted mb-0">Aucun paiement enregistré.</p>';
            } else {
                body.innerHTML = `<div class="table-responsive"><table class="table table-tiba align-middle mb-0">
                    <thead><tr><th>Date</th><th>Versé</th><th>Appliqué</th><th>Monnaie rendue</th><th>Statut</th><th></th></tr></thead>
                    <tbody>${payments.map(p => `
                        <tr>
                            <td>${p.date}</td>
                            <td>${fmt(p.paid, cur)}</td>
                            <td>${fmt(p.applied, cur)}</td>
                            <td>${p.change > 0 ? fmt(p.change, cur) : '—'}</td>
                            <td><span class="${p.status === 'valide' ? 'status-validee' : 'status-inactif'}">${p.status === 'valide' ? 'Valide' : 'Annulé'}</span></td>
                            <td class="text-end">${(p.status === 'valide' && debtOpen)
                                ? `<button type="button" class="btn-tiba-danger js-cancel-payment" data-id="${p.id}" data-amount="${fmt(p.applied, cur)}" style="padding:.25rem .6rem;font-size:.78rem;">Annuler</button>`
                                : ''}</td>
                        </tr>`).join('')}
                    </tbody></table></div>`;

                body.querySelectorAll('.js-cancel-payment').forEach(cb => {
                    cb.addEventListener('click', () => {
                        const f = document.createElement('form');
                        f.method = 'POST';
                        f.action = historyEl.dataset.cancelUrl.replace('__ID__', cb.dataset.id);
                        f.style.display = 'none';
                        f.innerHTML = `<input type="hidden" name="_token" value="${csrf}"><input type="hidden" name="_method" value="DELETE">`;
                        document.body.appendChild(f);

                        modal('historyModal').hide();
                        setTimeout(() => confirmAction(f, {
                            title: 'Annuler ce paiement ?',
                            message: `Le paiement de ${cb.dataset.amount} sera annulé et ce montant sera remis dans le solde de la dette.`,
                            confirmLabel: 'Oui, annuler le paiement',
                        }), 300);
                    });
                });
            }
            modal('historyModal').show();
        });
    });

    // ── Abandon / annulation d'une dette : confirmation personnalisée ────
    document.querySelectorAll('.js-debt-confirm').forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const name = form.dataset.name;
            if (form.dataset.kind === 'abandon') {
                confirmAction(form, {
                    title: 'Abandonner cette dette ?',
                    message: `La dette de ${name} sera considérée comme perdue : elle sera retirée du total des dettes actives et conservée dans les archives.`,
                    confirmLabel: 'Oui, abandonner',
                });
            } else {
                confirmAction(form, {
                    title: 'Annuler cette dette ?',
                    message: `À utiliser seulement si la dette de ${name} a été créée par erreur. Elle sera retirée du total des dettes actives et conservée dans l'historique.`,
                    confirmLabel: 'Oui, annuler',
                });
            }
        });
    });
});
