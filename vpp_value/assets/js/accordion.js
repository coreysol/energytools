/**
 * Toggle "Additional inputs to customize" accordion. Closed by default.
 */
(function () {
    var header = document.getElementById('accordion-toggle');
    var panel = document.getElementById('accordion-panel');
    var icon = header && header.querySelector('.accordion-icon');

    if (!header || !panel) return;

    function open() {
        panel.removeAttribute('hidden');
        header.setAttribute('aria-expanded', 'true');
        if (icon) icon.textContent = '\u2212';
    }

    function close() {
        panel.setAttribute('hidden', '');
        header.setAttribute('aria-expanded', 'false');
        if (icon) icon.textContent = '+';
    }

    function toggle() {
        if (panel.hasAttribute('hidden')) {
            open();
        } else {
            close();
        }
    }

    header.addEventListener('click', toggle);
})();
