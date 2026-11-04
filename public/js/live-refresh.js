/**
 * Actualisation automatique des données.
 *
 * Toutes les 20 s, la page courante est redemandée en arrière-plan. Si les
 * tableaux ou les cartes de chiffres ont changé (nouvelle transaction, vente,
 * dette… enregistrée depuis un autre poste), la page est rechargée.
 *
 * Par sécurité, rien n'est rechargé quand :
 *   - l'onglet est caché ;
 *   - une fenêtre modale est ouverte ;
 *   - l'utilisateur est en train de saisir (champ actif) ou a modifié un
 *     champ de formulaire (saisie non enregistrée) ;
 *   - la session a expiré (redirection vers /login) : on s'arrête.
 *
 * Désactiver sur une page : <meta name="live-refresh" content="off">.
 */
(function () {
    'use strict';

    const INTERVAL_MS = 20000;
    const meta = document.querySelector('meta[name="live-refresh"]');
    if (meta && meta.content === 'off') return;

    // Page sans donnée à surveiller → rien à faire.
    const WATCH = 'table, .metric-card';
    if (!document.querySelector(WATCH)) return;

    let dirty = false;      // un champ a été modifié par l'utilisateur
    let busy = false;       // une vérification est déjà en cours
    let stopped = false;

    document.addEventListener('input', e => {
        if (e.isTrusted && e.target.closest('form')) dirty = true;
    });

    // Empreinte du contenu surveillé (texte uniquement, sans jetons CSRF).
    function fingerprint(root) {
        return Array.from(root.querySelectorAll(WATCH))
            .map(el => el.textContent.replace(/\s+/g, ' ').trim())
            .join('|');
    }

    function userIsBusy() {
        if (document.hidden || dirty) return true;
        if (document.querySelector('.modal.show')) return true;
        const a = document.activeElement;
        return !!(a && /^(INPUT|TEXTAREA|SELECT)$/.test(a.tagName));
    }

    async function check() {
        if (stopped || busy || userIsBusy()) return;
        busy = true;
        try {
            const res = await fetch(location.href, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            // Session expirée / erreur : on arrête sans bruit.
            if (!res.ok || res.redirected && new URL(res.url).pathname !== location.pathname) {
                stopped = true;
                return;
            }
            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            if (fingerprint(doc) !== fingerprint(document) && !userIsBusy()) {
                location.reload();
            }
        } catch (e) {
            /* réseau coupé : on réessaiera au prochain tour */
        } finally {
            busy = false;
        }
    }

    setInterval(check, INTERVAL_MS);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) check(); });
})();
