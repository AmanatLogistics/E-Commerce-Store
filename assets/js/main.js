/* Storefront behaviour. Everything here is an enhancement — the pages work
   with JavaScript switched off, the steppers just fall back to typing. */
(function () {
    'use strict';

    // Quantity steppers: the − and + squares drive the number input.
    document.querySelectorAll('[data-stepper]').forEach(function (stepper) {
        var input = stepper.querySelector('input');
        if (!input) { return; }

        stepper.querySelectorAll('[data-step]').forEach(function (button) {
            button.addEventListener('click', function () {
                var min  = parseInt(input.min, 10) || 1;
                var max  = parseInt(input.max, 10) || Infinity;
                var next = (parseInt(input.value, 10) || min) + parseInt(button.dataset.step, 10);

                input.value = Math.min(max, Math.max(min, next));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        // Typing something impossible snaps back to what we can actually sell.
        input.addEventListener('blur', function () {
            var min = parseInt(input.min, 10) || 1;
            var max = parseInt(input.max, 10) || Infinity;
            var now = parseInt(input.value, 10);

            input.value = isNaN(now) ? min : Math.min(max, Math.max(min, now));
        });
    });
}());
