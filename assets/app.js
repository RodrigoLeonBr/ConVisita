// Calculo ao vivo da pontuacao no formulario de visita
(function () {
  var form = document.getElementById('form-visita');
  if (!form) return;
  var pesos = JSON.parse(form.dataset.pesos || '{}');
  var faixas = JSON.parse(form.dataset.faixas || '[]');
  function fmt(x) { return x === null ? '–' : (x * 100).toFixed(1).replace('.', ',') + '%'; }
  function recalc() {
    var dims = {};
    form.querySelectorAll('.item-aval').forEach(function (el) {
      var d = el.dataset.dim, max = parseInt(el.dataset.max, 10);
      var sel = el.querySelector('input[type=radio]:checked');
      dims[d] = dims[d] || { pts: 0, poss: 0 };
      if (sel && sel.value !== '' && sel.value !== 'NA') { dims[d].pts += parseInt(sel.value, 10); dims[d].poss += max; }
    });
    var soma = 0, somaPeso = 0;
    Object.keys(dims).forEach(function (d) {
      var p = dims[d].poss ? dims[d].pts / dims[d].poss : null;
      var alvo = document.getElementById('calc-dim-' + d);
      if (alvo) alvo.textContent = dims[d].pts + ' / ' + dims[d].poss + ' pts · ' + fmt(p);
      if (p !== null) { soma += p * pesos[d]; somaPeso += pesos[d]; }
    });
    var nota = somaPeso ? soma / somaPeso : null;
    var t = document.getElementById('calc-total');
    if (t) {
      t.textContent = fmt(nota);
      var cor = 'cinza';
      if (nota !== null) faixas.forEach(function (f) { if (nota >= f.li && nota < f.ls) cor = f.cor; });
      t.className = 'selo ' + cor;
    }
  }
  form.addEventListener('change', recalc);
  recalc();
})();

// Confirmacao em botoes de exclusao
document.querySelectorAll('[data-confirma]').forEach(function (b) {
  b.addEventListener('click', function (ev) { if (!confirm(b.dataset.confirma)) ev.preventDefault(); });
});

// Alternancia de tema claro/escuro (persiste em localStorage)
(function () {
  var btn = document.getElementById('btn-tema'); if (!btn) return;
  var raiz = document.documentElement;
  function sincroniza() { btn.setAttribute('aria-pressed', raiz.getAttribute('data-bs-theme') === 'dark'); }
  sincroniza();
  btn.addEventListener('click', function () {
    var novo = raiz.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    raiz.setAttribute('data-bs-theme', novo);
    try { localStorage.setItem('tema', novo); } catch (e) {}
    sincroniza();
  });
})();

// Menu lateral: hambúrguer recolhe no desktop, abre gaveta no mobile.
// Dois estados simétricos: sem classe = {desktop aberto, mobile fechado}; com ambas = {desktop recolhido, mobile aberto}.
(function () {
  var lat = document.getElementById('barraLat'), btn = document.getElementById('btn-menu'), ov = document.getElementById('overlay');
  if (!lat || !btn) return;
  var cl = document.body.classList;
  function desktop() { return matchMedia('(min-width:992px)').matches; }
  function visivel() { return desktop() ? !cl.contains('menu-recolhido') : cl.contains('menu-aberto'); }
  function sincroniza() { btn.setAttribute('aria-expanded', visivel() ? 'true' : 'false'); }
  function alterna() { cl.toggle('menu-aberto'); cl.toggle('menu-recolhido'); sincroniza(); }
  function fecha() { cl.remove('menu-aberto'); cl.remove('menu-recolhido'); sincroniza(); }
  btn.addEventListener('click', alterna);
  if (ov) ov.addEventListener('click', fecha);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') fecha(); });
  lat.querySelectorAll('nav a').forEach(function (a) { a.addEventListener('click', function () { if (!desktop()) fecha(); }); });
  window.addEventListener('resize', sincroniza);
  sincroniza();
})();

// Dropdown do usuário: fecha ao clicar fora ou com Esc
(function () {
  var m = document.getElementById('menuUsuario');
  if (!m) return;
  document.addEventListener('click', function (e) { if (m.open && !m.contains(e.target)) m.open = false; });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') m.open = false; });
})();
