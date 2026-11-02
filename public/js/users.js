document.addEventListener('DOMContentLoaded', function () {

    const modal = (id) => bootstrap.Modal.getOrCreateInstance(document.getElementById(id));

    // ── Politique de mot de passe (à garder synchronisée avec App\Rules\StrongPassword) ──
    const RULES = {
        length: v => v.length >= 8,
        upper: v => /\p{Lu}/u.test(v),
        digit: v => /[0-9]/.test(v),
        special: v => /[^\p{L}\p{N}]/u.test(v),
    };

    function checkPassword(input) {
        const list = document.querySelector(`.pwd-rules[data-for="${input.id}"]`);
        let allOk = true;
        list.querySelectorAll('li').forEach(li => {
            const ok = RULES[li.dataset.rule](input.value);
            li.classList.toggle('ok', ok);
            if (!ok) allOk = false;
        });
        const submit = input.closest('form').querySelector('button[type="submit"]');
        if (submit) submit.disabled = !allOk;
    }

    document.querySelectorAll('.js-pwd-input').forEach(input => {
        input.addEventListener('input', () => checkPassword(input));
    });

    // ── Œil : afficher / masquer ─────────────────────────────────────────
    document.querySelectorAll('.js-toggle-pwd').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.target);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    // ── Générateur : mot de passe conforme, affiché en clair pour être communiqué ──
    function randomInt(max) {
        const a = new Uint32Array(1);
        crypto.getRandomValues(a);
        return a[0] % max;
    }
    function generatePassword() {
        const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ', lower = 'abcdefghijkmnpqrstuvwxyz',
              digits = '23456789', special = '@#$!%*?', all = upper + lower + digits + special;
        const pick = s => s[randomInt(s.length)];
        const chars = [pick(upper), pick(digits), pick(special), pick(lower)];
        while (chars.length < 10) chars.push(pick(all));
        for (let i = chars.length - 1; i > 0; i--) {
            const j = randomInt(i + 1);
            [chars[i], chars[j]] = [chars[j], chars[i]];
        }
        return chars.join('');
    }

    document.querySelectorAll('.js-gen-pwd').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.target);
            input.value = generatePassword();
            input.type = 'text';
            const eye = document.querySelector(`.js-toggle-pwd[data-target="${input.id}"] i`);
            if (eye) eye.className = 'bi bi-eye-slash';
            checkPassword(input);
        });
    });

    // Réinitialise le champ mot de passe à l'ouverture / fermeture d'une modale
    function resetPwdField(modalEl) {
        const input = modalEl.querySelector('.js-pwd-input');
        if (!input) return;
        input.value = '';
        input.type = 'password';
        const eye = modalEl.querySelector('.js-toggle-pwd i');
        if (eye) eye.className = 'bi bi-eye';
        checkPassword(input);
    }
    ['createUserModal', 'resetPwdModal'].forEach(id => {
        document.getElementById(id).addEventListener('show.bs.modal', () => resetPwdField(document.getElementById(id)));
    });

    // ── Modifier un utilisateur ──────────────────────────────────────────
    document.querySelectorAll('.js-edit-user').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('editUserForm').action = btn.dataset.url;
            document.getElementById('editUserName').value = btn.dataset.name;
            document.getElementById('editUserUsername').value = btn.dataset.username;
            document.getElementById('editUserRole').value = btn.dataset.role;
            modal('editUserModal').show();
        });
    });

    // ── Réinitialiser le mot de passe ────────────────────────────────────
    document.querySelectorAll('.js-reset-pwd').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('resetPwdForm').action = btn.dataset.url;
            document.getElementById('resetPwdName').textContent = btn.dataset.name;
            modal('resetPwdModal').show();
        });
    });

    // ── Désactivation : confirmation personnalisée ───────────────────────
    document.querySelectorAll('.js-user-toggle').forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            confirmAction(form, {
                title: 'Désactiver ce compte ?',
                message: `${form.dataset.name} ne pourra plus se connecter tant que son compte ne sera pas réactivé. Son historique reste intact.`,
                confirmLabel: 'Oui, désactiver',
            });
        });
    });
});
