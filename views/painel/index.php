<div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
  <div>
    <h1 class="h3 mb-1">Painel de desempenho das unidades</h1>
    <p class="text-muted mb-0">Resultado da visita mais recente de cada unidade no ciclo. Nota final = média ponderada das quatro dimensões (25% cada), com redistribuição quando uma dimensão não se aplica.</p>
  </div>
  <form class="row g-2 align-items-end d-print-none" method="get" action="<?= BASE ?>/painel">
    <div class="col-auto"><label class="form-label mb-0">Ciclo</label><select class="form-select form-select-sm" name="ciclo" onchange="this.form.submit()"><?= opcoes(ciclos(), 'id', 'nome', $cicloId) ?></select></div>
    <div class="col-auto"><label class="form-label mb-0">Tipo de unidade</label><select class="form-select form-select-sm" name="tipo" onchange="this.form.submit()"><?= opcoes(consulta('SELECT * FROM tipo_unidade ORDER BY nome'), 'id', 'nome', $tipoId, 'Todos') ?></select></div>
    <div class="col-auto"><label class="form-label mb-0">Exibir</label><select class="form-select form-select-sm" name="exibir" onchange="this.form.submit()">
      <?php foreach (['todas' => 'Todas as unidades', 'visitadas' => 'Somente visitadas', 'sem_visita' => 'Somente sem visita'] as $k => $r): ?>
        <option value="<?= $k ?>" <?= $exibir === $k ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?></select></div>
  </form>
</div>

<div class="row g-3 mb-3">
  <div class="col-12 col-md-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small">Unidades visitadas em <?= e($ciclo['nome'] ?? '') ?></div>
    <div class="fs-4"><?= count($res) ?> <small class="text-muted">de <?= $totUnid ?></small></div>
    <div class="small"><?= count($res) - count($completas) ?> incompletas · <a href="<?= BASE ?>/painel?ciclo=<?= $cicloId ?>&tipo=<?= (int)$tipoId ?>&exibir=sem_visita#classificacao"><?= count($faltantes) ?> sem visita</a></div>
  </div></div></div>
  <div class="col-12 col-md-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small">Nota média (completas)</div>
    <div class="fs-4"><?= selo($media) ?></div>
    <div class="small"><?= count($completas) ?> unidades consideradas</div>
  </div></div></div>
  <div class="col-12 col-md-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small">Classificação</div>
    <div class="mt-1"><span class="selo verde"><?= $dist['verde'] ?> adequadas</span> <span class="selo amarelo"><?= $dist['amarelo'] ?> atenção</span> <span class="selo vermelho"><?= $dist['vermelho'] ?> críticas</span></div>
  </div></div></div>
  <div class="col-12 col-md-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small">Agenda de visitas</div>
    <div class="fs-4"><?= (int)$agenda['atrasadas'] ?> <small class="text-muted">atrasadas</small></div>
    <div class="small"><?= (int)$agenda['proximas'] ?> nos próximos 30 dias · <?= (int)$agenda['sem_visita'] ?> sem visita · <a href="<?= BASE ?>/pendencias"><?= $pend ?> pendências</a></div>
  </div></div></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-12 col-lg-6"><div class="card h-100"><div class="card-body">
    <h2 class="h6">Média por dimensão</h2>
    <table class="table table-sm align-middle mb-0"><?php foreach ($medDim as $rot => $v): ?><tr><td style="width:45%"><?= e($rot) ?></td><td><?= barra($v) ?></td></tr><?php endforeach; ?></table>
  </div></div></div>
  <div class="col-12 col-lg-6"><div class="card h-100"><div class="card-body">
    <h2 class="h6">Itens com pior desempenho no ciclo</h2>
    <div class="table-responsive"><table class="table table-sm mb-0"><tr><th scope="col">Item</th><th scope="col" class="text-end">Média (0 a 2)</th><th scope="col" class="text-end">Unidades com 0</th></tr>
    <?php foreach (array_slice($itens, 0, 5) as $i): ?>
      <tr><td><b><?= e($i['codigo']) ?></b> <?= e($i['nome']) ?></td><td class="text-end"><?= $i['media'] === null ? '–' : number_format((float)$i['media'], 2, ',', '') ?></td><td class="text-end"><?= (int)$i['zeros'] ?></td></tr>
    <?php endforeach; ?></table></div>
  </div></div></div>
</div>

<h2 class="h4" id="classificacao">Classificação das unidades</h2>
<div class="table-responsive"><table class="table table-striped align-middle">
  <thead><tr><th scope="col">#</th><th scope="col">Unidade</th><th scope="col" class="d-none d-md-table-cell">Tipo</th><th scope="col">Visita</th><th scope="col">Infra. física</th><th scope="col">Infra. humana</th><th scope="col" class="d-none d-lg-table-cell">Plano Municipal</th><th scope="col" class="d-none d-lg-table-cell">Percepção usuário</th><th scope="col" class="text-end">Nota final</th><th scope="col"></th></tr></thead>
  <tbody>
  <?php if ($exibir !== 'sem_visita'): foreach ($res as $k => $r): ?>
  <tr>
    <td><?= $k + 1 ?></td>
    <td><a href="<?= BASE ?>/unidade/<?= $r['unidade_id'] ?>"><?= e($r['unidade']) ?></a></td>
    <td class="d-none d-md-table-cell small"><?= e($r['tipo_unidade']) ?></td>
    <td><a href="<?= BASE ?>/visita/<?= $r['visita_id'] ?>"><?= data_br($r['data_visita']) ?></a><?= $r['data_aproximada'] ? ' <span class="small text-muted">(aprox.)</span>' : '' ?></td>
    <td><?= barra($r['perc_infra_fisica']) ?></td><td><?= barra($r['perc_infra_humana']) ?></td>
    <td class="d-none d-lg-table-cell"><?= barra($r['perc_plano_municipal']) ?></td><td class="d-none d-lg-table-cell"><?= barra($r['perc_percepcao_usuario']) ?></td>
    <td class="text-end"><?= selo($r['nota_final']) ?></td>
    <td><?= $r['situacao'] !== 'concluida' ? '<span class="badge text-bg-secondary">' . rotulo($r['situacao']) . '</span>' : '' ?></td>
  </tr>
  <?php endforeach; endif; ?>
  <?php if ($exibir !== 'visitadas'): foreach ($faltantes as $f): ?>
  <tr class="table-light">
    <td>–</td>
    <td><a href="<?= BASE ?>/unidade/<?= $f['unidade_id'] ?>"><?= e($f['unidade']) ?></a></td>
    <td class="d-none d-md-table-cell small"><?= e($f['tipo_unidade']) ?></td>
    <td colspan="5" class="small text-muted">Sem visita em <?= e($ciclo['nome'] ?? '') ?><?php if ($f['visita_id']): ?> · última em <a href="<?= BASE ?>/visita/<?= $f['visita_id'] ?>"><?= data_br($f['ultima_visita']) ?></a> (<?= pct($f['nota_final']) ?>)<?php else: ?> · nenhuma visita registrada<?php endif; ?></td>
    <td class="text-end"><span class="selo cinza">sem visita</span></td>
    <td class="d-print-none"><?php if (pode_editar()): ?><a class="btn btn-sm btn-outline-secondary" href="<?= BASE ?>/visita/nova?unidade=<?= $f['unidade_id'] ?>">Registrar</a><?php endif; ?></td>
  </tr>
  <?php endforeach; endif; ?>
  <?php if (($exibir === 'sem_visita' || !$res) && ($exibir === 'visitadas' || !$faltantes)): ?><tr><td colspan="10">Nenhuma unidade nesta seleção.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<p class="small text-muted">Faixas: <?php foreach (faixas() as $f): ?><span class="selo <?= e($f['cor']) ?>"><?= e($f['rotulo']) ?></span> <?php endforeach; ?>
 <span class="selo cinza">Sem visita no ciclo</span> · Visitas incompletas têm nota só sobre as dimensões avaliadas e não entram na média geral.</p>
