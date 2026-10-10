<h1 class="h3">Agenda de visitas</h1>
<p class="sub">Próxima visita prevista = data da última visita válida + periodicidade da unidade (semestral por padrão, conforme o instrumento).</p>
<div class="mb-3 d-print-none">
  <a class="btn btn-sm <?= $filtro === '' ? 'btn-secondary' : 'btn-outline-secondary' ?>" href="<?= BASE ?>/agenda">Todas</a>
  <?php foreach (['atrasada', 'sem_visita', 'proximos_30_dias', 'em_dia', 'suspensa'] as $s): ?>
    <a class="btn btn-sm <?= $filtro === $s ? 'btn-secondary' : 'btn-outline-secondary' ?>" href="<?= BASE ?>/agenda?situacao=<?= $s ?>"><?= rotulo($s) ?> (<?= (int)($cont[$s] ?? 0) ?>)</a>
  <?php endforeach; ?>
</div>
<div class="table-responsive"><table class="table table-striped align-middle">
  <thead><tr><th scope="col">Unidade</th><th scope="col" class="d-none d-md-table-cell">Tipo</th><th scope="col">Última visita</th><th scope="col" class="text-end">Nota</th><th scope="col">Próxima prevista</th><th scope="col" class="text-end">Dias</th><th scope="col">Situação</th><th scope="col"></th></tr></thead>
  <tbody>
  <?php foreach ($lista as $a):
    $dias = $a['proxima_prevista'] ? (int)floor((strtotime($a['proxima_prevista']) - strtotime(date('Y-m-d'))) / 86400) : null; ?>
  <tr>
    <td><a href="<?= BASE ?>/unidade/<?= $a['unidade_id'] ?>"><?= e($a['unidade']) ?></a></td>
    <td class="d-none d-md-table-cell small"><?= e($a['tipo_unidade']) ?></td>
    <td><?= data_br($a['ultima_visita']) ?></td>
    <td class="text-end"><?= $a['visita_id'] ? selo($a['nota_final']) : '–' ?></td>
    <td><?= data_br($a['proxima_prevista']) ?></td>
    <td class="text-end"><?= $dias === null ? '–' : $dias ?></td>
    <td><span class="badge text-bg-light border"><?= rotulo($a['situacao_agenda']) ?></span></td>
    <td class="d-print-none"><?php if (pode_editar()): ?><a class="btn btn-sm btn-outline-secondary" href="<?= BASE ?>/visita/nova?unidade=<?= $a['unidade_id'] ?>">Registrar visita</a><?php endif; ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<p class="small text-muted">Dias negativos indicam atraso em relação à data prevista. <a href="<?= BASE ?>/exportar?tipo=agenda">Exportar agenda (CSV)</a></p>
