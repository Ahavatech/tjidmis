(function () {
  var t = document.querySelector('.be_toggle'), s = document.getElementById('beSidebar');
  if (t && s) t.addEventListener('click', function () { var o = s.classList.toggle('is_open'); t.setAttribute('aria-expanded', o ? 'true' : 'false'); });
  // close the user menu when clicking elsewhere
  document.addEventListener('click', function (e) { document.querySelectorAll('details.be_user[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); }); });
})();
