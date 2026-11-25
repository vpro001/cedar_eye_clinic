/* Admin panel behaviour (kept out of the HTML so the content security policy can forbid inline scripts). */
(function () {
  'use strict';
  // confirm before anything destructive: <button data-confirm="Delete this item?">
  document.addEventListener('submit', function (e) {
    var b = e.submitter;
    if (b && b.dataset.confirm && !window.confirm(b.dataset.confirm)) e.preventDefault();
  });
  // the sidebar menu on small screens
  var t = document.querySelector('[data-menu-toggle]');
  var n = document.getElementById('side-nav');
  if (t && n) {
    t.addEventListener('click', function () {
      var hidden = n.classList.toggle('hidden');
      t.setAttribute('aria-expanded', String(!hidden));
    });
  }
  // product photograph preview
  var file = document.getElementById('image');
  var img = document.getElementById('preview');
  if (file && img) {
    file.addEventListener('change', function () {
      var f = file.files[0];
      if (!f) return;
      img.src = URL.createObjectURL(f);
      img.classList.remove('hidden');
      var none = document.getElementById('noimg');
      if (none) none.remove();
    });
  }
})();
