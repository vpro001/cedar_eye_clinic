/* Cedar Eye Clinic: the public site's behaviour. Everything works without it; this only adds comfort. */
(function () {
  'use strict';
  var doc = document;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---- menu on small screens */
  var toggle = doc.querySelector('.nav-toggle');
  var nav = doc.getElementById('site-nav');
  function setMenu(open) {
    if (!toggle || !nav) return;
    toggle.setAttribute('aria-expanded', String(open));
    nav.classList.toggle('is-open', open);
  }
  if (toggle && nav) {
    toggle.addEventListener('click', function () { setMenu(toggle.getAttribute('aria-expanded') !== 'true'); });
    nav.addEventListener('click', function (e) { if (e.target.closest('a')) setMenu(false); });
    doc.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') { setMenu(false); toggle.focus(); }
    });
    window.matchMedia('(min-width: 900px)').addEventListener('change', function (m) { if (m.matches) setMenu(false); });
  }

  /* ---- header rule once the page scrolls; the phone action bar once the first screen has passed */
  var head = doc.querySelector('.site-head');
  var bar = doc.querySelector('.actionbar');
  var ticking = false;
  function onScroll() {
    ticking = false;
    var y = window.scrollY;
    if (head) head.classList.toggle('is-stuck', y > 8);
    if (bar) {
      var nearForm = false;
      doc.querySelectorAll('form.form').forEach(function (f) {
        var r = f.getBoundingClientRect();
        if (r.top < window.innerHeight && r.bottom > 0) nearForm = true;
      });
      bar.classList.toggle('is-shown', y > window.innerHeight * 0.6 && !nearForm);
    }
  }
  window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
  onScroll();

  /* ---- the eye chart comes into focus, like the right lens dropping into place */
  var chart = doc.querySelector('.chart');
  if (chart) {
    if (reduce) chart.classList.add('is-focused');
    else window.setTimeout(function () { chart.classList.add('is-focused'); }, 700);
  }

  /* ---- text that sharpens as it scrolls into view */
  var soft = doc.querySelectorAll('.focus-in');
  if (soft.length) {
    if (reduce || !('IntersectionObserver' in window)) {
      soft.forEach(function (el) { el.classList.add('is-in'); });
    } else {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); }
        });
      }, { rootMargin: '0px 0px -20% 0px' });
      soft.forEach(function (el) { io.observe(el); });
    }
  }

  /* ---- forms: check before sending, with the same words the server uses */
  function fieldOf(input) { return input.closest('.field'); }
  function errOf(input) { var f = fieldOf(input); return f && f.querySelector('.field__error'); }
  function invalid(input) {
    var v = input.value.trim();
    if (input.required && v === '') return true;
    if (v === '') return false;
    if (input.type === 'tel') { var d = v.replace(/\D/g, ''); return d.length < 10 || d.length > 15; }
    if (input.type === 'email') return !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
    if (input.type === 'date') {
      if (input.min && v < input.min) return true;
      if (input.max && v > input.max) return true;
    }
    return false;
  }
  function show(input, bad) {
    var err = errOf(input);
    if (bad) { input.setAttribute('aria-invalid', 'true'); if (err && err.textContent) err.hidden = false; }
    else { input.removeAttribute('aria-invalid'); if (err) err.hidden = true; }
  }
  doc.querySelectorAll('form.form').forEach(function (form) {
    var inputs = form.querySelectorAll('.field input, .field select, .field textarea');
    var status = form.querySelector('[data-form-status]');
    inputs.forEach(function (input) {
      input.addEventListener('blur', function () { if (input.value.trim() !== '' || input.hasAttribute('aria-invalid')) show(input, invalid(input)); });
      input.addEventListener('input', function () { if (input.hasAttribute('aria-invalid') && !invalid(input)) show(input, false); });
    });
    form.addEventListener('submit', function (e) {
      var bad = [];
      inputs.forEach(function (input) { var b = invalid(input); show(input, b); if (b) bad.push(input); });
      if (bad.length) {
        e.preventDefault();
        if (status) {
          status.textContent = bad.length + (bad.length === 1 ? ' field' : ' fields') + ' to fix before we can send it.';
          status.setAttribute('data-error', '');
        }
        bad[0].focus();
        return;
      }
      var btn = form.querySelector('button[type="submit"]');
      if (btn) {
        if (form.dataset.sending) { e.preventDefault(); return; }
        form.dataset.sending = '1';
        btn.setAttribute('aria-disabled', 'true');
        btn.style.opacity = '.7';
        btn.dataset.label = btn.textContent;
        btn.textContent = 'Sending…';
      }
    });
  });

  /* back/forward cache: a restored page must not keep the "Sending…" state */
  window.addEventListener('pageshow', function (e) {
    if (!e.persisted) return;
    doc.querySelectorAll('form.form').forEach(function (form) {
      delete form.dataset.sending;
      var btn = form.querySelector('button[type="submit"]');
      if (btn && btn.dataset.label) { btn.textContent = btn.dataset.label; btn.removeAttribute('aria-disabled'); btn.style.opacity = ''; }
    });
  });
})();
