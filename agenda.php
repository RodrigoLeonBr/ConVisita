<?php
require __DIR__ . '/inc/base.php';
exige_login();
$filtro = (string)get('situacao', '');
$ordem = "FIELD(situacao_agenda,'atrasada','sem_visita','proximos_30_dias','em_dia','suspensa'), proxima_prevista";
$lista = consulta('SELECT * FROM vw_agenda' . ($filtro ? ' WHERE situacao_agenda = ?' : '') . ' ORDER BY ' . $ordem, $filtro ? [$filtro] : []);
$cont = [];
foreach (consulta('SELECT situacao_agenda s, COUNT(*) n FROM vw_agenda GROUP BY situacao_agenda') as $c) $cont[$c['s']] = $c['n'];
$titulo = 'Agenda'; $menu = 'agenda';
require __DIR__ . '/inc/topo.php';
?>
<h1>Agenda de visitas</h1>
<p class="sub">Próxima visita prevista = data da última visita válida + periodicidade da unidade (semestral por padrão, conforme o instrumento).</p>
<div class="acoes nao-imprimir" style="margin-bottom:14px">
  <a class="btn <?= $filtro === '' ? '' : 'sec' ?> peq" href="agenda.php">Todas</a>
  <?php foreach (['atrasada', 'sem_visita', 'proximos_30_dias', 'em_dia', 'suspensa'] as $s): ?>
    <a class="btn <?= $filtro === $s ? '' : 'sec' ?> peq" href="?situacao=<?= $s ?>"><?= rotulo($s) ?> (<?= (int)($cont[$s] ?? 0) ?>)</a>
  <?php endforeach; ?>
</div>
<div class="tabela"><table>
  <tr><th>Unidade</th><th>Tipo</th><th>Última visita</th><th class="num">Nota</th><th>Próxima prevista</th><th class="num">Dias</th><th>Situação</th><th></th></tr>
  <?php foreach ($lista as $a):
    $dias = $a['proxima_prevista'] ? (int)floor((strtotime($a['proxima_prevista']) - strtotime(date('Y-m-d'))) / 86400) : null; ?>
  <tr>
    <td><a href="unidade.php?id=<?= $a['unidade_id'] ?>"><?= e($a['unidade']) ?></a></td>
    <td class="mini"><?= e($a['tipo_unidade']) ?></td>
    <td><?= data_br($a['ultima_visita']) ?></td>
    <td class="num"><?= $a['visita_id'] ? selo($a['nota_final']) : '–' ?></td>
    <td><?= data_br($a['proxima_prevista']) ?></td>
    <td class="num"><?= $dias === null ? '–' : $dias ?></td>
    <td><span class="tag <?= e($a['situacao_agenda']) ?>"><?= rotulo($a['situacao_agenda']) ?></span></td>
    <td class="nao-imprimir"><?php if (pode_editar()): ?><a class="btn peq sec" href="visita_form.php?unidade=<?= $a['unidade_id'] ?>">Registrar visita</a><?php endif; ?></td>
  </tr>
  <?php endforeach; ?>
</table></div>
<p class="legenda">Dias negativos indicam atraso em relação à data prevista. <a href="exportar.php?tipo=agenda">Exportar agenda (CSV)</a></p>
<?php require __DIR__ . '/inc/rodape.php';
