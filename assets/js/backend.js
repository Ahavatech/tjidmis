(function () {
  var t = document.querySelector('.be_toggle'), s = document.getElementById('beSidebar');
  if (t && s) t.addEventListener('click', function () { var o = s.classList.toggle('is_open'); t.setAttribute('aria-expanded', o ? 'true' : 'false'); });
  // close the user menu when clicking elsewhere
  document.addEventListener('click', function (e) { document.querySelectorAll('details.be_user[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); }); });
})();

// Prevents double submits: a second submit of the same form within 8 s is ignored, and the form shows a busy state.
document.addEventListener('submit', function (e) {
  var f = e.target; if (e.defaultPrevented || !f || f.tagName !== 'FORM') return;
  if (f.getAttribute('data-busy')) { e.preventDefault(); return; }
  f.setAttribute('data-busy', '1'); f.classList.add('is_busy');
  setTimeout(function () { f.removeAttribute('data-busy'); f.classList.remove('is_busy'); }, 8000);
});
window.addEventListener('pageshow', function () { document.querySelectorAll('form.is_busy').forEach(function (f) { f.removeAttribute('data-busy'); f.classList.remove('is_busy'); }); });
document.addEventListener('click', function (e) {
  var a = e.target.closest ? e.target.closest('.pkp_controllers_grid .show_extras') : null; if (!a) return;
  e.preventDefault(); var row = a.closest('tr'), next = row.nextElementSibling, open = row.classList.toggle('expanded');
  if (next && next.classList.contains('row_controls')) next.classList.toggle('expanded', open);
  a.setAttribute('aria-expanded', open ? 'true' : 'false');
});
