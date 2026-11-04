/**
 * Tableaux : forme classique conservée sur smartphone.
 * Chaque <table> est enveloppé dans un conteneur .table-scroll (défilement
 * horizontal, colonnes à largeur minimale, contenu sur une seule ligne —
 * voir responsive-tables.css). Les tableaux ajoutés dynamiquement (historique
 * d'une dette…) sont gérés par un MutationObserver.
 * Un tableau à ignorer porte l'attribut data-no-scroll.
 */
(function () {
    'use strict';

    function wrap(table) {
        if (table.hasAttribute('data-no-scroll')) return;
        const parent = table.parentElement;
        if (!parent || parent.classList.contains('table-scroll')) return;

        // Réutilise le .table-responsive de Bootstrap s'il existe, sinon crée un conteneur.
        if (parent.classList.contains('table-responsive')) {
            parent.classList.add('table-scroll');
        } else {
            const box = document.createElement('div');
            box.className = 'table-scroll';
            parent.insertBefore(box, table);
            box.appendChild(table);
        }
    }

    function scan(root) { (root || document).querySelectorAll('table').forEach(wrap); }

    function init() {
        scan(document);
        new MutationObserver(ms => ms.forEach(m => m.addedNodes.forEach(n => {
            if (n.nodeType !== 1) return;
            if (n.tagName === 'TABLE') wrap(n); else if (n.querySelectorAll) scan(n);
        }))).observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
