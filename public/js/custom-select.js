/**
 * Listes déroulantes personnalisées.
 * Chaque <select class="form-select"> garde son <select> natif (caché) : les
 * formulaires, la validation et les scripts existants (change, .value…)
 * continuent de fonctionner ; seul l'affichage est remplacé.
 * Ignorer un select : attribut data-native. Les <select multiple> sont ignorés.
 */
(function () {
    'use strict';

    const CARET = '<svg class="tsel-caret" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4l4 4 4-4"/></svg>';
    let openInst = null;

    function enhance(sel) {
        if (sel.dataset.tsel === '1' || sel.multiple || sel.size > 1 || sel.hasAttribute('data-native')) return;
        sel.dataset.tsel = '1';

        const root = document.createElement('div');
        root.className = 'tsel';
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'tsel-btn';
        btn.setAttribute('aria-haspopup', 'listbox');
        const label = document.createElement('span');
        label.className = 'tsel-label';
        btn.appendChild(label);
        btn.insertAdjacentHTML('beforeend', CARET);
        const panel = document.createElement('div');
        panel.className = 'tsel-panel';
        panel.setAttribute('role', 'listbox');

        sel.parentNode.insertBefore(root, sel);
        root.appendChild(btn);
        root.appendChild(sel);
        document.body.appendChild(panel);
        sel.style.display = 'none';
        // Garde le <label for=""> fonctionnel : il ouvre la liste
        if (sel.id) document.querySelectorAll('label[for="' + sel.id + '"]').forEach(l => l.addEventListener('click', e => { e.preventDefault(); btn.focus(); }));

        let items = [], active = -1;

        function build() {
            panel.innerHTML = '';
            items = [];
            Array.from(sel.children).forEach(ch => {
                if (ch.tagName === 'OPTGROUP') {
                    const g = document.createElement('div'); g.className = 'tsel-group'; g.textContent = ch.label; panel.appendChild(g);
                    Array.from(ch.children).forEach(add);
                } else add(ch);
            });
        }
        function add(opt) {
            const d = document.createElement('div');
            d.className = 'tsel-opt' + (opt.disabled ? ' disabled' : '');
            d.setAttribute('role', 'option');
            d.textContent = opt.textContent;
            d.addEventListener('mousedown', e => e.preventDefault());
            d.addEventListener('click', () => { if (!opt.disabled) choose(opt); });
            panel.appendChild(d);
            items.push({ opt, el: d });
        }
        function sync() {
            const cur = sel.options[sel.selectedIndex];
            label.textContent = cur ? cur.textContent : '';
            label.classList.toggle('is-empty', !!cur && cur.value === '');
            btn.disabled = sel.disabled;
            items.forEach(i => i.el.classList.toggle('selected', i.opt === cur));
        }
        function choose(opt) {
            if (sel.value !== opt.value) {
                sel.value = opt.value;
                sel.dispatchEvent(new Event('change', { bubbles: true }));
            }
            close(); btn.focus();
        }
        function place() {
            const r = btn.getBoundingClientRect();
            const below = window.innerHeight - r.bottom, h = Math.min(260, panel.scrollHeight + 14);
            panel.style.minWidth = r.width + 'px';
            panel.style.left = Math.max(8, Math.min(r.left, window.innerWidth - r.width - 8)) + 'px';
            if (below < h && r.top > below) { panel.style.top = ''; panel.style.bottom = (window.innerHeight - r.top + 4) + 'px'; panel.style.maxHeight = Math.min(260, r.top - 12) + 'px'; }
            else { panel.style.bottom = ''; panel.style.top = (r.bottom + 4) + 'px'; panel.style.maxHeight = Math.min(260, below - 12) + 'px'; }
        }
        function setActive(i) {
            active = i;
            items.forEach((it, k) => it.el.classList.toggle('active', k === i));
            if (items[i]) items[i].el.scrollIntoView({ block: 'nearest' });
        }
        function open() {
            if (sel.disabled) return;
            if (openInst && openInst !== inst) openInst.close();
            build(); sync();
            panel.classList.add('show'); root.classList.add('open'); place();
            setActive(items.findIndex(i => i.opt === sel.options[sel.selectedIndex]));
            openInst = inst;
        }
        function close() {
            panel.classList.remove('show'); root.classList.remove('open');
            if (openInst === inst) openInst = null;
        }
        const inst = { close, panel, btn };

        btn.addEventListener('click', () => (root.classList.contains('open') ? close() : open()));
        btn.addEventListener('keydown', e => {
            const isOpen = root.classList.contains('open');
            if (['ArrowDown', 'ArrowUp'].includes(e.key)) {
                e.preventDefault();
                if (!isOpen) return open();
                let i = active, step = e.key === 'ArrowDown' ? 1 : -1;
                do { i += step; } while (items[i] && items[i].opt.disabled);
                if (items[i]) setActive(i);
            } else if ((e.key === 'Enter' || e.key === ' ') && isOpen) {
                e.preventDefault(); if (items[active]) choose(items[active].opt);
            } else if (e.key === 'Escape' && isOpen) { e.preventDefault(); e.stopPropagation(); close(); }
            else if (e.key === 'Tab') close();
        });

        // Les scripts existants modifient parfois sel.value / selectedIndex / disabled directement.
        ['value', 'selectedIndex'].forEach(p => {
            const d = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, p);
            Object.defineProperty(sel, p, { get() { return d.get.call(this); }, set(v) { d.set.call(this, v); sync(); }, configurable: true });
        });
        sel.addEventListener('change', sync);
        new MutationObserver(() => { build(); sync(); }).observe(sel, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'selected'] });
        sel.form && sel.form.addEventListener('reset', () => setTimeout(sync, 0));
        build(); sync();
    }

    document.addEventListener('mousedown', e => {
        if (openInst && !openInst.panel.contains(e.target) && !openInst.btn.contains(e.target)) openInst.close();
    });
    window.addEventListener('resize', () => openInst && openInst.close());
    window.addEventListener('scroll', e => { if (openInst && !openInst.panel.contains(e.target)) openInst.close(); }, true);

    function scan(root) { (root || document).querySelectorAll('select.form-select, select.form-control').forEach(enhance); }
    function init() {
        scan(document);
        new MutationObserver(ms => ms.forEach(m => m.addedNodes.forEach(n => { if (n.nodeType === 1 && n.querySelectorAll) { if (n.matches && n.matches('select')) enhance(n); scan(n); } }))).observe(document.body, { childList: true, subtree: true });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
