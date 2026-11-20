// Site-specific additions only; menus and dropdowns are handled by the theme's own main.js (jQuery + Bootstrap dropdown).
(function () {
  var sel = document.getElementById('citeStyle');
  if (sel) sel.addEventListener('change', function () {
    document.querySelectorAll('.csl-bib-body').forEach(function (d) { d.hidden = d.getAttribute('data-style') !== sel.value; });
  });
})();

// Prevents double submits: a second submit of the same form within 8 s is ignored, and the form shows a busy state.
document.addEventListener('submit', function (e) {
  var f = e.target; if (e.defaultPrevented || !f || f.tagName !== 'FORM') return;
  if (f.getAttribute('data-busy')) { e.preventDefault(); return; }
  f.setAttribute('data-busy', '1'); f.classList.add('is_busy');
  setTimeout(function () { f.removeAttribute('data-busy'); f.classList.remove('is_busy'); }, 8000);
});
window.addEventListener('pageshow', function () { document.querySelectorAll('form.is_busy').forEach(function (f) { f.removeAttribute('data-busy'); f.classList.remove('is_busy'); }); });
