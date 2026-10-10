<?php
require __DIR__ . '/inc/base.php';
exige_login();
$tipo = (int)get('tipo', 0);
$lista = consulta('SELECT a.*, u.gestao, u.nome_planilha, (SELECT COUNT(*) FROM visita v WHERE v.unidade_id = a.unidade_id AND v.situacao IN (\'concluida\',\'incompleta\')) AS qtd_visitas
                     FROM vw_agenda a JOIN unidade u ON u.id = a.unidade_id'
    . ($tipo ? ' WHERE u.tipo_id = ' . $tipo : '') . ' ORDER BY a.tipo_unidade, a.unidade');
$titulo = 'Unidades'; $menu = 'unidades';
require __DIR__ . '/inc/topo.php';
?>
<div class="cab">
  <div><h1>Unidades de saúde</h1><p class="sub"><?= count($lista) ?> estabelecimentos cadastrados</p></div>
  <div class="acoes nao-imprimir"><?php if (pode_editar()): ?><a class="btn" href="unidade_form.php">Nova unidade</a><?php endif; ?></div>
</div>
<form class="filtros nao-imprimir"><div style="min-width:280px"><label>Tipo</label><select name="tipo" onchange="this.form.submit()"><?= opcoes(consulta('SELECT * FROM tipo_unidade ORDER BY nome'), 'id', 'nome', $tipo, 'Todos') ?></select></div></form>
<div class="tabela"><table>
  <tr><th>Unidade</th><th>Tipo</th><th>Gestão</th><th class="num">Visitas</th><th>Última visita</th><th class="num">Última nota</th><th>Próxima prevista</th><th>Agenda</th></tr>
  <?php foreach ($lista as $u): ?>
  <tr>
    <td><a href="unidade.php?id=<?= $u['unidade_id'] ?>"><?= e($u['unidade']) ?></a><?= $u['situacao_unidade'] !== 'ativa' ? ' <span class="tag">' . rotulo($u['situacao_unidade']) . '</span>' : '' ?></td>
    <td class="mini"><?= e($u['tipo_unidade']) ?></td><td class="mini"><?= e($u['gestao'] ?: '–') ?></td>
    <td class="num"><?= (int)$u['qtd_visitas'] ?></td>
    <td><?= $u['visita_id'] ? '<a href="visita.php?id=' . $u['visita_id'] . '">' . data_br($u['ultima_visita']) . '</a>' : '–' ?></td>
    <td class="num"><?= $u['visita_id'] ? selo($u['nota_final']) : '–' ?></td>
    <td><?= data_br($u['proxima_prevista']) ?></td>
    <td><span class="tag <?= e($u['situacao_agenda']) ?>"><?= rotulo($u['situacao_agenda']) ?></span></td>
  </tr>
  <?php endforeach; ?>
</table></div>
<?php require __DIR__ . '/inc/rodape.php';
