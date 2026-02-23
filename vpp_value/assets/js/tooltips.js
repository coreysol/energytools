/**
 * Show tooltip text in a popover on hover/focus for .tooltip-trigger elements.
 * Uses the element's title attribute (and clears it so the browser doesn't show a second tooltip).
 */
(function () {
    function showTooltip(el, text) {
        if (!text) return;
        var tip = document.getElementById('vpp-tooltip-el');
        if (!tip) {
            tip = document.createElement('div');
            tip.id = 'vpp-tooltip-el';
            tip.setAttribute('role', 'tooltip');
            tip.className = 'vpp-tooltip-popover';
            document.body.appendChild(tip);
        }
        tip.textContent = text;
        tip.style.display = 'block';
        var rect = el.getBoundingClientRect();
        var tipWidth = tip.offsetWidth;
        var x = rect.left + rect.width / 2 - tipWidth / 2;
        var padding = 8;
        if (x < padding) x = padding;
        if (x + tipWidth > document.documentElement.clientWidth - padding) {
            x = document.documentElement.clientWidth - tipWidth - padding;
        }
        var y = rect.top - tip.offsetHeight - 8;
        if (y < padding) y = rect.bottom + 8;
        tip.style.left = x + 'px';
        tip.style.top = y + 'px';
    }
    function hideTooltip() {
        var tip = document.getElementById('vpp-tooltip-el');
        if (tip) tip.style.display = 'none';
    }
    document.querySelectorAll('.tooltip-trigger').forEach(function (el) {
        var text = el.getAttribute('data-tooltip') || el.getAttribute('title');
        if (!text) return;
        el.setAttribute('title', '');
        el.setAttribute('tabindex', '0');
        el.addEventListener('mouseenter', function () { showTooltip(el, text); });
        el.addEventListener('mouseleave', hideTooltip);
        el.addEventListener('focus', function () { showTooltip(el, text); });
        el.addEventListener('blur', hideTooltip);
    });
})();
