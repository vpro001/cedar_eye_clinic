/* Cedar Eye Clinic: the booking wizard in book.php.
   Everything here is a convenience over the plain form: without this file the three steps are
   all on the page and the form posts to book.php exactly as it always did. */
(function () {
  'use strict';
  var doc = document;
  var form = doc.querySelector('form[data-wiz]');
  if (!form) return;

  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var steps = [].slice.call(form.querySelectorAll('.wiz__step'));
  var rails = [].slice.call(form.querySelectorAll('[data-rail]'));
  var status = form.querySelector('[data-form-status]');
  var avail = form.querySelector('[data-avail]');
  var dateInput = doc.getElementById('f-date');
  var current = 1;
  var lastAv = null;   // the last answer from api/slots.php
  var slotsBusy = 0;   // counts in-flight requests, so an older reply cannot overwrite a newer one

  form.classList.add('is-on');

  /* ---- which step is showing */
  function show(n, moveFocus) {
    current = n;
    steps.forEach(function (s) {
      var on = Number(s.dataset.step) === n;
      s.classList.toggle('is-active', on);
      if (on) s.setAttribute('tabindex', '-1');
      else s.removeAttribute('tabindex');
    });
    rails.forEach(function (r) {
      var on = Number(r.dataset.rail) === n;
      r.classList.toggle('is-now', on);
      if (on) r.setAttribute('aria-current', 'step');
      else r.removeAttribute('aria-current');
    });
    if (n === 3) fillSummary();
    if (moveFocus) {
      var step = steps[n - 1];
      if (step) {
        step.focus({ preventScroll: true });
        var box = form.closest('.slip') || form;
        box.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'nearest' });
      }
    }
  }

  /* ---- errors, in the same words the server uses */
  form.querySelectorAll('.field__error').forEach(function (p) { p.dataset.hint = p.textContent; });

  function clearErrors() {
    form.querySelectorAll('.field__error').forEach(function (p) {
      p.textContent = p.dataset.hint;
      p.hidden = true;
    });
    form.querySelectorAll('[aria-invalid]').forEach(function (el) { el.removeAttribute('aria-invalid'); });
    if (status) { status.textContent = ''; status.removeAttribute('data-error'); }
  }
  function errorEl(key) { return key === 'form' ? status : doc.getElementById('f-' + key + '-err'); }
  function stepOf(key) {
    if (key === 'service') return 1;
    if (key === 'date' || key === 'time') return 2;
    return 3;
  }
  function showError(key, msg) {
    var el = errorEl(key);
    if (el) {
      el.textContent = msg;
      if (key === 'form') el.setAttribute('data-error', '');
      else el.hidden = false;
    }
    var input = doc.getElementById('f-' + key);
    if (input) input.setAttribute('aria-invalid', 'true');
  }
  function showErrors(errors) {
    clearErrors();
    var keys = Object.keys(errors || {});
    if (!keys.length) return;
    keys.forEach(function (k) { showError(k, errors[k]); });
    var first = keys[0];
    show(stepOf(first), true);
    if (first === 'service' || first === 'time') {
      var group = steps[stepOf(first) - 1].querySelector('.wiz__opts');
      if (group) group.focus();
    }
  }

  function chosen(name) {
    var r = form.querySelector('input[name="' + name + '"]:checked');
    return r ? r.value : '';
  }
  function cssEsc(s) { return s.replace(/["\\]/g, '\\$&'); }

  /* ---- step 1: a service */
  function step1ok() {
    if (chosen('service')) return true;
    showError('service', 'Choose the service you are booking.');
    var group = steps[0].querySelector('.wiz__opts');
    if (group) group.focus();
    return false;
  }

  /* ---- step 2: a day and a time of day, with what is left on that day */
  var notes = {};
  form.querySelectorAll('[data-slot]').forEach(function (label) {
    var note = label.querySelector('[data-note]');
    notes[label.dataset.slot] = { label: label, note: note, base: note.textContent };
  });

  function setSlot(key, free, text) {
    var n = notes[key];
    if (!n) return;
    var input = n.label.querySelector('input');
    input.disabled = !free;
    n.label.classList.toggle('is-blocked', !free);
    if (!free && input.checked) input.checked = false;
    n.note.textContent = n.base + ' · ' + text;
  }

  function paint(av) {
    lastAv = av;
    if (!av) {
      Object.keys(notes).forEach(function (k) { setSlot(k, false, 'Choose a day first'); });
      if (avail) avail.hidden = true;
      return;
    }
    if (!av.valid) {
      Object.keys(notes).forEach(function (k) { setSlot(k, false, 'Choose a day first'); });
      if (avail) avail.hidden = true;
      return;
    }
    if (!av.open) {
      Object.keys(notes).forEach(function (k) { setSlot(k, false, 'Closed that day'); });
      if (avail) {
        avail.textContent = 'We are closed on ' + dayName(av.date) + '. Choose another day.';
        avail.hidden = false;
        avail.setAttribute('data-error', '');
      }
      return;
    }
    if (avail) {
      var free = av.free === 1 ? '1 place free' : av.free + ' places free';
      avail.textContent = 'Open ' + av.hours.open + ' to ' + av.hours.close + ' · ' + free;
      avail.hidden = false;
      avail.removeAttribute('data-error');
    }
    av.slots.forEach(function (s) {
      if (!s.open) setSlot(s.key, false, 'Not this time');
      else if (s.full) setSlot(s.key, false, 'Full');
      else setSlot(s.key, true, s.left === 1 ? '1 place left' : s.left + ' places left');
    });
  }

  function dayName(iso) {
    var d = new Date(iso + 'T00:00:00');
    return isNaN(d) ? 'that day' : d.toLocaleDateString('en-NG', { weekday: 'long' });
  }

  function loadSlots() {
    var date = dateInput ? dateInput.value : '';
    if (!date) { paint(null); return; }
    var id = ++slotsBusy;
    fetch(form.dataset.slots + '?date=' + encodeURIComponent(date), { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (av) { if (id === slotsBusy) paint(av); })
      .catch(function () {
        if (id !== slotsBusy) return;
        // no answer: leave the times as they are rather than pretend they are full
        lastAv = null;
        if (avail) {
          avail.textContent = 'Could not check what is left. Choose a time and we will confirm it.';
          avail.hidden = false;
          avail.setAttribute('data-error', '');
        }
      });
  }

  function step2ok() {
    var d = dateInput ? dateInput.value : '';
    if (!d) { showError('date', 'Choose the day you want to come.'); if (dateInput) dateInput.focus(); return false; }
    if (dateInput.min && d < dateInput.min) { showError('date', 'Choose a day between today and three months from now.'); dateInput.focus(); return false; }
    if (dateInput.max && d > dateInput.max) { showError('date', 'Choose a day between today and three months from now.'); dateInput.focus(); return false; }
    if (lastAv && lastAv.valid && !lastAv.open) {
      showError('date', 'We are closed on ' + dayName(lastAv.date) + '. Choose another day.');
      dateInput.focus();
      return false;
    }
    var t = chosen('time');
    if (!t) { showError('time', 'Choose morning, midday or afternoon.'); return false; }
    var input = form.querySelector('input[name="time"][value="' + cssEsc(t) + '"]');
    if (input && input.disabled) { showError('time', 'That time of day is full or not offered. Choose another.'); return false; }
    return true;
  }

  /* ---- step 3: what we are about to send */
  function set(key, text) {
    var el = form.querySelector('[data-sum="' + key + '"]');
    if (el) el.textContent = text;
  }
  function fillSummary() {
    var svc = chosen('service');
    var svcName = '';
    if (svc) {
      var opt = form.querySelector('input[name="service"][value="' + cssEsc(svc) + '"]');
      if (opt) svcName = opt.parentElement.querySelector('.wiz__opt-t').textContent;
    }
    var day = '';
    if (dateInput && dateInput.value) {
      var dt = new Date(dateInput.value + 'T00:00:00');
      if (!isNaN(dt)) {
        day = dt.toLocaleDateString('en-NG', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
      }
    }
    var t = chosen('time');
    var tName = t && notes[t] ? notes[t].label.querySelector('.wiz__opt-t').textContent : '';
    set('service', svcName);
    set('day', day);
    set('time', tName);
  }

  /* ---- moving between steps */
  form.addEventListener('click', function (e) {
    var next = e.target.closest ? e.target.closest('[data-next]') : null;
    var back = e.target.closest ? e.target.closest('[data-back]') : null;
    if (next) {
      clearErrors();
      if (current === 1 && !step1ok()) return;
      if (current === 2 && !step2ok()) return;
      show(current + 1, true);
    } else if (back) {
      clearErrors();
      show(current - 1, true);
    }
  });

  // Enter on steps 1 and 2 advances instead of submitting the whole form early
  form.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' || current >= 3) return;
    var t = e.target.tagName;
    if (t !== 'INPUT' && t !== 'SELECT' && t !== 'TEXTAREA') return;
    e.preventDefault();
    var next = form.querySelector('.is-active [data-next]');
    if (next) next.click();
  });

  if (dateInput) {
    dateInput.addEventListener('change', function () { clearErrors(); loadSlots(); });
    dateInput.addEventListener('input', function () { clearErrors(); });
  }
  form.addEventListener('change', function (e) {
    if (e.target.name === 'service' || e.target.name === 'time') clearErrors();
  });

  /* ---- sending it, without leaving the page */
  function resetSending() {
    delete form.dataset.sending;
    var btn = form.querySelector('button[type="submit"]');
    if (btn && btn.dataset.label) {
      btn.textContent = btn.dataset.label;
      btn.removeAttribute('aria-disabled');
      btn.style.opacity = '';
    }
  }

  form.addEventListener('submit', function (e) {
    if (e.defaultPrevented) return; // site.js found a field to fix first
    e.preventDefault();

    fetch(form.dataset.book, { method: 'POST', body: new FormData(form) })
      .then(function (r) { return r.json().catch(function () { return null; }); })
      .then(function (data) {
        if (data && data.ok) {
          var slip = form.closest('.slip');
          if (data.html && slip) {
            slip.outerHTML = data.html;
            var made = doc.querySelector('.slip[role="status"]');
            if (made) {
              made.setAttribute('tabindex', '-1');
              made.focus({ preventScroll: true });
              made.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
            }
            if (window.history && history.replaceState) {
              history.replaceState(null, '', form.dataset.done + encodeURIComponent(data.ref));
            }
          } else {
            // no slip to show: fall back to the plain confirmation page
            window.location.href = form.dataset.done + encodeURIComponent(data.ref);
          }
          return;
        }
        resetSending();
        if (data && data.errors) showErrors(data.errors);
        else if (status) {
          status.textContent = 'We could not send that. Check your connection and try again, or call the clinic.';
          status.setAttribute('data-error', '');
        }
      })
      .catch(function () {
        // no answer from the server: send the form the ordinary way, which posts to book.php
        resetSending();
        HTMLFormElement.prototype.submit.call(form);
      });
  });

  /* ---- open on the step that has something to fix, or on the day already chosen */
  (function start() {
    var bad = null;
    form.querySelectorAll('.field__error').forEach(function (p) {
      if (!bad && !p.hidden) bad = p.id;
    });
    if (status && status.hasAttribute('data-error') && status.textContent) bad = bad || 'f-notes-err';
    if (bad) {
      show(stepOf(bad.replace(/^f-/, '').replace(/-err$/, '')), false);
      if (dateInput && dateInput.value) loadSlots();
    } else if (dateInput && dateInput.value) {
      show(2, false);
      loadSlots();
    } else {
      show(1, false);
    }
  })();
})();
