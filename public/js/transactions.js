/**
 * Module Transactions : saisie rapide + modale de correction.
 * La grille tarifaire (service_fees) et les préfixes opérateurs sont reçus
 * du serveur une seule fois ; tout le calcul se fait en JS, sans aller-retour
 * réseau. Le serveur revalide tout à la soumission (source de vérité).
 */

const MOVEMENT_LABELS = {
    retrait: 'Retrait',
    transfert: 'Transfert',
    depot: 'Dépôt',
    pap_padap: 'Pap Padap',
    lapoula: 'Lapoula',
    depot_paryaj_pam: 'Paryaj Pam',
    depot_paryaj_lakay: 'Paryaj Lakay',
    depot_lotto_mobile: 'Lotto Mobile',
};
const MOVEMENTS_BY_SERVICE = {
    moncash: ['retrait', 'transfert', 'pap_padap', 'depot_paryaj_pam', 'depot_paryaj_lakay', 'depot_lotto_mobile'],
    natcash: ['retrait', 'transfert', 'depot', 'lapoula', 'depot_paryaj_pam', 'depot_paryaj_lakay', 'depot_lotto_mobile'],
    zelle: ['transfert'],
};
const NO_FEE_MOVEMENTS = ['pap_padap', 'lapoula', 'depot_paryaj_pam', 'depot_paryaj_lakay', 'depot_lotto_mobile'];
const NO_FEE_MIN_AMOUNTS = { pap_padap: 10, lapoula: 5, depot_paryaj_pam: 50, depot_paryaj_lakay: 50, depot_lotto_mobile: 50 };
const MAX_AMOUNTS = { moncash: 74999, natcash: 99999 };

function corePhoneDigits(rawPhone, service) {
    let digits = rawPhone.replace(/\D/g, '');
    if (service === 'zelle') {
        if (digits.length === 11 && digits.startsWith('1')) digits = digits.slice(1);
    } else {
        if (digits.length >= 10 && digits.startsWith('509')) digits = digits.slice(3);
    }
    return digits;
}

function expectedPhoneLength(service) {
    return service === 'zelle' ? 10 : 8;
}

/**
 * Calcule le résultat (frais ou erreur) pour un service/mouvement/montant
 * donné. Retourne { ok: true, feeClient, feeNet, currency, noFee } ou
 * { ok: false, message }.
 */
function computeFee(service, movement, amount, serviceFees) {
    const currency = service === 'zelle' ? 'USD' : 'HTG';

    if (NO_FEE_MOVEMENTS.includes(movement)) {
        const minAmount = NO_FEE_MIN_AMOUNTS[movement];
        if (amount < minAmount) {
            return { ok: false, message: `Montant minimum pour cette opération : ${minAmount.toLocaleString('fr-FR')} HTG.` };
        }
        return { ok: true, feeClient: 0, feeNet: 0, currency, noFee: true };
    }

    const maxAmount = MAX_AMOUNTS[service];
    if (maxAmount && amount > maxAmount) {
        return { ok: false, message: `Montant supérieur au plafond de ${maxAmount.toLocaleString('fr-FR')} HTG pour ce service. Scindez l'opération.` };
    }

    if (service === 'zelle') {
        const feeClient = Math.round(amount * 0.10 * 100) / 100;
        return { ok: true, feeClient, feeNet: Math.round((feeClient / 2) * 100) / 100, currency, noFee: false };
    }

    const tier = serviceFees.find(f =>
        f.service === service && f.movement_type === movement &&
        amount >= parseFloat(f.min_amount) && amount <= parseFloat(f.max_amount)
    );
    if (!tier) {
        return { ok: false, message: 'Aucun tarif trouvé pour ce montant.' };
    }
    const feeClient = parseFloat(tier.fee);
    const feeNet = (service === 'natcash' && movement === 'transfert') ? 0 : feeClient;
    return { ok: true, feeClient, feeNet, currency, noFee: false };
}

function renderFeeSummary(el, result) {
    if (!result.ok) {
        el.innerHTML = `<span class="text-danger fw-semibold">${result.message}</span>`;
        return;
    }
    if (result.noFee) {
        el.innerHTML = `
            <div class="d-flex justify-content-between"><span>Frais</span><strong>Aucun</strong></div>
            <div class="text-muted small mt-1">Pas de plafond maximum pour cette opération.</div>
        `;
        return;
    }
    el.innerHTML = `
        <div class="d-flex justify-content-between"><span>Frais facturés au client</span><strong>${result.feeClient.toLocaleString('fr-FR', {minimumFractionDigits:2})} ${result.currency}</strong></div>
        <div class="d-flex justify-content-between text-muted small mt-1"><span>Commission entreprise</span><span>${result.feeNet.toLocaleString('fr-FR', {minimumFractionDigits:2})} ${result.currency}</span></div>
    `;
}

/* ──────────────────────────────────────────────────────────────────────
   Panneau de saisie rapide (nouvelle transaction)
   ────────────────────────────────────────────────────────────────────── */
function initTransactionForm(config) {
    const { serviceFees, operatorPrefixes, formId } = config;
    const form = document.getElementById(formId);
    if (!form) return;

    const serviceInput = form.querySelector('[name="service"]');
    const phoneInput = form.querySelector('[name="client_phone"]');
    const amountInput = form.querySelector('[name="amount"]');
    const movementInput = form.querySelector('[name="movement_type"]');
    const serviceButtons = form.querySelectorAll('.service-btn');
    const movementWrap = form.querySelector('#movementWrap');
    const feeSummary = form.querySelector('#feeSummary');
    const submitBtn = form.querySelector('#txnSubmitBtn');
    const phoneHint = form.querySelector('#phoneHint');

    let currentService = null;
    let canSubmit = false;

    function setService(service, { fromDetection = false } = {}) {
        currentService = service;
        serviceInput.value = service;
        serviceButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.service === service));

        movementWrap.innerHTML = '';
        (MOVEMENTS_BY_SERVICE[service] || []).forEach((m, i) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'movement-btn' + (i === 0 ? ' active' : '');
            btn.textContent = MOVEMENT_LABELS[m];
            btn.dataset.movement = m;
            btn.addEventListener('click', () => {
                movementWrap.querySelectorAll('.movement-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                movementInput.value = m;
                recalculate();
            });
            movementWrap.appendChild(btn);
        });
        movementInput.value = (MOVEMENTS_BY_SERVICE[service] || [])[0] || '';

        if (!fromDetection) {
            phoneHint.textContent = service === 'zelle'
                ? "Zelle n'utilise pas de préfixe local — numéro à 10 chiffres."
                : 'Numéro à 8 chiffres, sans le +509.';
            phoneHint.classList.remove('text-danger');
        }

        recalculate();
    }

    function detectPrefix(rawPhone) {
        const digits = corePhoneDigits(rawPhone, 'moncash');
        const prefix = digits.slice(0, 2);
        return operatorPrefixes.find(p => p.prefix === prefix) || null;
    }

    function recalculate() {
        const amount = parseFloat(amountInput.value);
        canSubmit = false;

        if (!currentService) {
            feeSummary.innerHTML = '<span class="text-muted">Sélectionnez un service pour commencer.</span>';
            submitBtn.disabled = true;
            return;
        }

        if (phoneInput.value) {
            const core = corePhoneDigits(phoneInput.value, currentService);
            const expected = expectedPhoneLength(currentService);
            if (core.length !== expected) {
                phoneHint.textContent = `Le numéro doit comporter ${expected} chiffres (hors indicatif).`;
                phoneHint.classList.add('text-danger');
                submitBtn.disabled = true;
                feeSummary.innerHTML = '<span class="text-muted">Corrigez le numéro pour continuer.</span>';
                return;
            } else {
                phoneHint.classList.remove('text-danger');
                if (currentService !== 'zelle') phoneHint.textContent = 'Préfixe reconnu.';
            }
        }

        if (!amount || amount <= 0) {
            feeSummary.innerHTML = '<span class="text-muted">Saisissez un montant pour voir les frais.</span>';
            submitBtn.disabled = true;
            return;
        }

        const result = computeFee(currentService, movementInput.value, amount, serviceFees);
        renderFeeSummary(feeSummary, result);
        canSubmit = result.ok;
        submitBtn.disabled = !result.ok;
    }

    serviceButtons.forEach(btn => btn.addEventListener('click', () => setService(btn.dataset.service)));

    phoneInput.addEventListener('input', () => {
        if (currentService !== 'zelle') {
            const match = detectPrefix(phoneInput.value);
            if (match && match.service !== currentService) {
                setService(match.service, { fromDetection: true });
            }
        }
        recalculate();
    });

    amountInput.addEventListener('input', recalculate);

    form.addEventListener('submit', (e) => {
        if (!canSubmit) {
            e.preventDefault();
            return;
        }
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...';
    });
}

/* ──────────────────────────────────────────────────────────────────────
   Modale de correction d'une transaction existante (service/mouvement fixes)
   ────────────────────────────────────────────────────────────────────── */
function initEditTransactionModal(config) {
    const { serviceFees } = config;
    const form = document.getElementById('editTxnForm');
    const modalEl = document.getElementById('editTransactionModal');
    if (!form || !modalEl) return;

    const phoneInput = form.querySelector('[name="client_phone"]');
    const amountInput = form.querySelector('[name="amount"]');
    const feeSummary = document.getElementById('editFeeSummary');
    const submitBtn = document.getElementById('editTxnSubmitBtn');
    const serviceBadge = document.getElementById('editServiceBadge');
    const movementBadge = document.getElementById('editMovementBadge');

    let service = null;
    let movement = null;

    function recalc() {
        const amount = parseFloat(amountInput.value);
        let ok = true;

        const core = corePhoneDigits(phoneInput.value, service);
        const expected = expectedPhoneLength(service);
        if (phoneInput.value && core.length !== expected) {
            feeSummary.innerHTML = `<span class="text-danger fw-semibold">Le numéro doit comporter ${expected} chiffres (hors indicatif).</span>`;
            ok = false;
        } else if (!amount || amount <= 0) {
            feeSummary.innerHTML = '<span class="text-muted">Saisissez un montant.</span>';
            ok = false;
        } else {
            const result = computeFee(service, movement, amount, serviceFees);
            renderFeeSummary(feeSummary, result);
            ok = result.ok;
        }

        submitBtn.disabled = !ok;
    }

    document.querySelectorAll('.js-edit-transaction').forEach(btn => {
        btn.addEventListener('click', () => {
            service = btn.dataset.service;
            movement = btn.dataset.movement;
            form.action = btn.dataset.url;
            phoneInput.value = btn.dataset.phone;
            amountInput.value = btn.dataset.amount;
            serviceBadge.textContent = btn.dataset.service.charAt(0).toUpperCase() + btn.dataset.service.slice(1);
            movementBadge.textContent = MOVEMENT_LABELS[movement] || movement;
            recalc();
            new bootstrap.Modal(modalEl).show();
        });
    });

    phoneInput.addEventListener('input', recalc);
    amountInput.addEventListener('input', recalc);
}
