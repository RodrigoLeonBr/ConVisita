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
