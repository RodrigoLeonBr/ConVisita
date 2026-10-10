<div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
  <div><h1 class="h3 mb-1">Visitas registradas</h1><p class="text-muted mb-0"><?= count($lista) ?> registro(s)</p></div>
  <div class="d-print-none">
    <?php if (pode_editar()): ?><a class="btn btn-primary btn-sm" href="<?= BASE ?>/visita/nova">Registrar nova visita</a><?php endif; ?>
    <a class="btn btn-outline-secondary btn-sm" href="<?= BASE ?>/exportar?tipo=resultados">Exportar resultados (CSV)</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= BASE ?>/exportar?tipo=itens">Exportar notas por item (CSV)</a>
  </div>
</div>
<form class="row g-2 align-items-end mb-3 d-print-none" method="get" action="<?= BASE ?>/visitas">
  <div class="col-auto"><label class="form-label mb-0">Ciclo</label><select class="form-select form-select-sm" name="ciclo"><?= opcoes(ciclos(), 'id', 'nome', $f['ciclo'], 'Todos') ?></select></div>
  <div class="col-auto" style="min-width:260px"><label class="form-label mb-0">Unidade</label><select class="form-select form-select-sm" name="unidade"><?= opcoes(consulta('SELECT id, nome FROM unidade ORDER BY nome'), 'id', 'nome', $f['unidade'], 'Todas') ?></select></div>
  <div class="col-auto"><label class="form-label mb-0">Situação</label><select class="form-select form-select-sm" name="situacao"><option value="">Todas</option>
    <?php foreach (['concluida', 'incompleta', 'rascunho', 'em_conferencia'] as $s): ?><option value="<?= $s ?>" <?= $f['situacao'] === $s ? 'selected' : '' ?>><?= rotulo($s) ?></option><?php endforeach; ?></select></div>
  <div class="col-auto"><button class="btn btn-secondary btn-sm">Filtrar</button></div>
</form>
<div class="table-responsive"><table class="table table-striped align-middle">
  <thead><tr><th scope="col">Data</th><th scope="col">Ciclo</th><th scope="col">Unidade</th><th scope="col" class="d-none d-md-table-cell">Tipo</th><th scope="col">Física</th><th scope="col">Humana</th><th scope="col" class="d-none d-lg-table-cell">Plano</th><th scope="col" class="d-none d-lg-table-cell">Usuário</th><th scope="col" class="d-none d-md-table-cell text-end">Pontos</th><th scope="col" class="text-end">Nota final</th><th scope="col">Situação</th></tr></thead>
  <tbody>
  <?php foreach ($lista as $r): ?>
  <tr>
    <td><a href="<?= BASE ?>/visita/<?= $r['visita_id'] ?>"><?= data_br($r['data_visita']) ?></a></td>
    <td><?= e($r['ciclo']) ?></td>
    <td><a href="<?= BASE ?>/unidade/<?= $r['unidade_id'] ?>"><?= e($r['unidade']) ?></a></td>
    <td class="d-none d-md-table-cell small"><?= e($r['tipo_unidade']) ?></td>
    <td><?= selo($r['perc_infra_fisica']) ?></td><td><?= selo($r['perc_infra_humana']) ?></td>
    <td class="d-none d-lg-table-cell"><?= selo($r['perc_plano_municipal']) ?></td><td class="d-none d-lg-table-cell"><?= selo($r['perc_percepcao_usuario']) ?></td>
    <td class="d-none d-md-table-cell text-end"><?= (int)$r['pontos_totais'] ?>/<?= (int)$r['pontos_possiveis'] ?></td>
    <td class="text-end"><?= selo($r['nota_final']) ?></td>
    <td><span class="badge text-bg-secondary"><?= rotulo($r['situacao']) ?></span></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
