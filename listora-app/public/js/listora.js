/* Listora — front-end behaviour. No build step, no dependencies. */
(function () {
    'use strict';

    /* ---------------------------------------------------------- header ---- */
    var header = document.getElementById('siteHeader');

    function onScroll() {
        if (!header) return;
        header.classList.toggle('scrolled', window.scrollY > 40);
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    /* ------------------------------------------------------ mobile nav ---- */
    var burger = document.getElementById('burger');
    var mobileNav = document.getElementById('mobileNav');

    if (burger && mobileNav) {
        burger.addEventListener('click', function () {
            var open = mobileNav.classList.toggle('open');
            burger.setAttribute('aria-expanded', String(open));
            document.body.style.overflow = open ? 'hidden' : '';
        });
    }

    /* --------------------------------------------------- scroll reveal ---- */
    var reveals = document.querySelectorAll('.reveal');

    if ('IntersectionObserver' in window && reveals.length) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry, i) {
                if (!entry.isIntersecting) return;
                var el = entry.target;
                setTimeout(function () { el.classList.add('in'); }, Math.min(i * 70, 280));
                io.unobserve(el);
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

        reveals.forEach(function (el) { io.observe(el); });
    } else {
        reveals.forEach(function (el) { el.classList.add('in'); });
    }

    /* ------------------------------------------- how-it-works switcher ---- */
    var howSwitch = document.getElementById('howSwitch');

    if (howSwitch) {
        var owner = document.getElementById('stepsOwner');
        var traveler = document.getElementById('stepsTraveler');

        howSwitch.addEventListener('click', function (e) {
            var btn = e.target.closest('button');
            if (!btn) return;

            howSwitch.querySelectorAll('button').forEach(function (b) { b.classList.remove('on'); });
            btn.classList.add('on');

            var isOwner = btn.dataset.side === 'owner';
            if (owner) owner.hidden = !isOwner;
            if (traveler) traveler.hidden = isOwner;

            var shown = isOwner ? owner : traveler;
            if (shown) shown.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('in'); });
        });
    }

    /* --------------------------------------------------------- wizard ---- */
    var wizard = document.getElementById('wizard');

    if (wizard) {
        var steps = wizard.querySelectorAll('.wiz-step');
        var markers = document.querySelectorAll('#stepper .s');
        var current = 0;

        function paint() {
            steps.forEach(function (s, i) { s.classList.toggle('on', i === current); });
            markers.forEach(function (m, i) {
                m.classList.toggle('on', i === current);
                m.classList.toggle('done', i < current);
            });
            window.scrollTo({ top: Math.max(0, wizard.offsetTop - 140), behavior: 'smooth' });
        }

        wizard.addEventListener('click', function (e) {
            if (e.target.closest('[data-next]') && current < steps.length - 1) { current++; paint(); }
            if (e.target.closest('[data-back]') && current > 0) { current--; paint(); }
        });

        /* choice groups -> hidden inputs */
        function group(containerId, attr, inputId, after) {
            var box = document.getElementById(containerId);
            var input = document.getElementById(inputId);
            if (!box || !input) return;

            box.addEventListener('click', function (e) {
                var choice = e.target.closest('.choice');
                if (!choice) return;
                box.querySelectorAll('.choice').forEach(function (c) { c.classList.remove('on'); });
                choice.classList.add('on');
                input.value = choice.dataset[attr];
                if (after) after(input.value);
            });
        }

        function showKindFields(kind) {
            ['home', 'points', 'weeks'].forEach(function (k) {
                document.querySelectorAll('.js-' + k).forEach(function (el) {
                    el.hidden = (k !== kind);
                    el.querySelectorAll('input, select').forEach(function (f) { f.disabled = (k !== kind); });
                });
            });
            document.querySelectorAll('.js-club').forEach(function (el) {
                el.style.display = kind === 'home' ? 'none' : '';
            });
        }

        group('kindChoices', 'kind', 'kindInput', showKindFields);
        group('modeChoices', 'mode', 'modeInput');
        group('planChoices', 'plan', 'planInput');

        showKindFields(document.getElementById('kindInput').value || 'home');

        /* if validation bounced us back, land on the step with the error */
        if (document.querySelector('.field .err')) {
            current = steps.length - 1;
            paint();
        }
    }
}());
