/* Keeps the unread badge current without a page refresh. */
(function () {
    'use strict';

    var url = window.UNREAD_URL;
    if (!url) { return; }

    function paint(count) {
        document.querySelectorAll('[data-unread]').forEach(function (badge) {
            badge.textContent = count;
            badge.hidden = count === 0;
        });
    }

    function poll() {
        // A hidden tab does not need a count; save the round trip.
        if (document.hidden) { return; }

        fetch(url, { credentials: 'same-origin' })
            .then(function (response) {
                if (!response.ok) { throw new Error('signed out'); }
                return response.json();
            })
            .then(function (data) { paint(parseInt(data.unread, 10) || 0); })
            .catch(function () { /* Offline or signed out — leave the last count alone. */ });
    }

    setInterval(poll, 30000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) { poll(); }
    });
}());
