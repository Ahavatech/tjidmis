// Site-specific additions only; menus and dropdowns are handled by the theme's own main.js (jQuery + Bootstrap dropdown).
(function () {
  var sel = document.getElementById('citeStyle');
  if (sel) sel.addEventListener('change', function () {
    document.querySelectorAll('.csl-bib-body').forEach(function (d) { d.hidden = d.getAttribute('data-style') !== sel.value; });
  });
})();
