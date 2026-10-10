<div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
  <div><h1 class="h3 mb-1">Comparativo entre ciclos</h1><p class="text-muted mb-0">Evolução das unidades avaliadas nos dois ciclos (visita mais recente de cada ciclo).</p></div>
  <form class="row g-2 align-items-end d-print-none" method="get" action="<?= BASE ?>/comparativo">
    <div class="col-auto"><label class="form-label mb-0">Ciclo base</label><select class="form-select form-select-sm" name="a"><?= opcoes($cs, 'id', 'nome', $cA) ?></select></div>
    <div class="col-auto"><label class="form-label mb-0">Ciclo comparado</label><select class="form-select form-select-sm" name="b"><?= opcoes($cs, 'id', 'nome', $cB) ?></select></div>
    <div class="col-auto"><button class="btn btn-secondary btn-sm">Comparar</button></div></form>
</div>
<div class="table-responsive"><table class="table table-striped align-middle">
  <thead><tr><th scope="col">Unidade</th><th scope="col" class="text-end">Nota <?= e($nomeA) ?></th><th scope="col" class="text-end">Nota <?= e($nomeB) ?></th><th scope="col" class="text-end">Variação (p.p.)</th><th scope="col" class="d-none d-lg-table-cell">Física</th><th scope="col" class="d-none d-lg-table-cell">Humana</th><th scope="col" class="d-none d-lg-table-cell">Plano</th><th scope="col" class="d-none d-lg-table-cell">Usuário</th></tr></thead>
  <tbody>
  <?php foreach ($linhas as $l): ?>
  <tr><td><a href="<?= BASE ?>/unidade/<?= $l['id'] ?>"><?= e($l['nome']) ?></a></td>
    <td class="text-end"><?= $l['a'] ? selo($l['a']['nota_final']) : '–' ?></td>
    <td class="text-end"><?= $l['b'] ? selo($l['b']['nota_final']) : '–' ?></td>
    <td class="text-end" style="font-weight:600;color:<?= $l['dif'] === null ? 'inherit' : ($l['dif'] >= 0 ? 'var(--verde)' : 'var(--vermelho)') ?>"><?= $l['dif'] === null ? '–' : ($l['dif'] >= 0 ? '+' : '') . number_format($l['dif'] * 100, 1, ',', '') ?></td>
    <?php foreach (['perc_infra_fisica', 'perc_infra_humana', 'perc_plano_municipal', 'perc_percepcao_usuario'] as $k): ?>
      <td class="d-none d-lg-table-cell small"><?= $l['a'] ? pct($l['a'][$k], 0) : '–' ?> → <?= $l['b'] ? pct($l['b'][$k], 0) : '–' ?></td>
    <?php endforeach; ?></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<h2 class="h4">Média por item do instrumento</h2>
<div class="table-responsive"><table class="table table-striped align-middle">
  <thead><tr><th scope="col">Item</th><th scope="col" class="d-none d-md-table-cell">Dimensão</th><th scope="col" class="text-end">Média <?= e($nomeA) ?></th><th scope="col" class="text-end">Média <?= e($nomeB) ?></th><th scope="col" class="text-end">Unidades com nota 0 (<?= e($nomeB) ?>)</th></tr></thead>
  <tbody>
  <?php foreach ($IB as $i): $a = $IA[$i['codigo']] ?? null; ?>
  <tr><td><b><?= e($i['codigo']) ?></b> <?= e($i['nome']) ?></td><td class="d-none d-md-table-cell small"><?= e($i['dimensao']) ?></td>
    <td class="text-end"><?= $a && $a['media'] !== null ? number_format((float)$a['media'], 2, ',', '') : '–' ?></td>
    <td class="text-end"><?= $i['media'] !== null ? number_format((float)$i['media'], 2, ',', '') : '–' ?></td>
    <td class="text-end"><?= (int)$i['zeros'] ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
