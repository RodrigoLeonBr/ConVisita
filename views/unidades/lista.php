<div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
  <div><h1 class="h3 mb-1">Unidades de saúde</h1><p class="text-muted mb-0"><?= count($lista) ?> estabelecimentos cadastrados</p></div>
  <div class="d-print-none"><?php if (pode_editar()): ?><a class="btn btn-primary btn-sm" href="<?= BASE ?>/unidade/nova">Nova unidade</a><?php endif; ?></div>
</div>
<form class="row g-2 align-items-end mb-3 d-print-none" method="get" action="<?= BASE ?>/unidades"><div class="col-auto" style="min-width:260px"><label class="form-label mb-0">Tipo</label><select class="form-select form-select-sm" name="tipo" onchange="this.form.submit()"><?= opcoes(consulta('SELECT * FROM tipo_unidade ORDER BY nome'), 'id', 'nome', $tipo, 'Todos') ?></select></div></form>
<div class="table-responsive"><table class="table table-striped align-middle">
  <thead><tr><th scope="col">Unidade</th><th scope="col" class="d-none d-md-table-cell">Tipo</th><th scope="col" class="d-none d-md-table-cell">Gestão</th><th scope="col" class="text-end">Visitas</th><th scope="col">Última visita</th><th scope="col" class="text-end">Última nota</th><th scope="col" class="d-none d-lg-table-cell">Próxima prevista</th><th scope="col">Agenda</th></tr></thead>
  <tbody>
  <?php foreach ($lista as $u): ?>
  <tr>
    <td><a href="<?= BASE ?>/unidade/<?= $u['unidade_id'] ?>"><?= e($u['unidade']) ?></a><?= $u['situacao_unidade'] !== 'ativa' ? ' <span class="badge text-bg-secondary">' . rotulo($u['situacao_unidade']) . '</span>' : '' ?></td>
    <td class="d-none d-md-table-cell small"><?= e($u['tipo_unidade']) ?></td><td class="d-none d-md-table-cell small"><?= e($u['gestao'] ?: '–') ?></td>
    <td class="text-end"><?= (int)$u['qtd_visitas'] ?></td>
    <td><?= $u['visita_id'] ? '<a href="' . BASE . '/visita/' . $u['visita_id'] . '">' . data_br($u['ultima_visita']) . '</a>' : '–' ?></td>
    <td class="text-end"><?= $u['visita_id'] ? selo($u['nota_final']) : '–' ?></td>
    <td class="d-none d-lg-table-cell"><?= data_br($u['proxima_prevista']) ?></td>
    <td><span class="badge text-bg-light border"><?= rotulo($u['situacao_agenda']) ?></span></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
