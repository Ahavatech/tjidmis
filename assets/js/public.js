// Vanilla JS. Everything degrades gracefully without it (menus open on :hover / :focus-within).
(function () {
  var toggle = document.querySelector('.pkp_site_nav_toggle'), menu = document.getElementById('siteNavMenu');
  if (toggle && menu) toggle.addEventListener('click', function () {
    var open = menu.classList.toggle('is_open'); toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  // On narrow screens, the first tap on a parent item opens its submenu; the second follows the link.
  document.querySelectorAll('.pkp_navigation_primary > li.has_children > a').forEach(function (a) {
    a.addEventListener('click', function (e) {
      if (window.innerWidth >= 992) return;
      var li = a.parentNode;
      if (!li.classList.contains('is_open')) { e.preventDefault(); li.classList.add('is_open'); a.setAttribute('aria-expanded', 'true'); }
    });
  });
  var sel = document.getElementById('citeStyle');
  if (sel) sel.addEventListener('change', function () {
    document.querySelectorAll('.csl-bib-body').forEach(function (d) { d.hidden = d.getAttribute('data-style') !== sel.value; });
  });
})();
