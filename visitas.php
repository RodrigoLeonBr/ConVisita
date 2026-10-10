<?php
require __DIR__ . '/inc/base.php';
exige_login();

$f = ['ciclo' => (int)get('ciclo', 0), 'unidade' => (int)get('unidade', 0), 'situacao' => (string)get('situacao', '')];
$w = []; $p = [];
if ($f['ciclo']) { $w[] = 'r.ciclo_id = ?'; $p[] = $f['ciclo']; }
if ($f['unidade']) { $w[] = 'r.unidade_id = ?'; $p[] = $f['unidade']; }
if ($f['situacao'] !== '') { $w[] = 'r.situacao = ?'; $p[] = $f['situacao']; }
$lista = consulta('SELECT r.* FROM vw_resultado_visita r' . ($w ? ' WHERE ' . implode(' AND ', $w) : '')
    . ' ORDER BY r.data_visita DESC, r.unidade', $p);

$titulo = 'Visitas'; $menu = 'visitas';
require __DIR__ . '/inc/topo.php';
?>
<div class="cab">
  <div><h1>Visitas registradas</h1><p class="sub"><?= count($lista) ?> registro(s)</p></div>
  <div class="acoes nao-imprimir">
    <?php if (pode_editar()): ?><a class="btn" href="visita_form.php">Registrar nova visita</a><?php endif; ?>
    <a class="btn sec" href="exportar.php?tipo=resultados">Exportar resultados (CSV)</a>
    <a class="btn sec" href="exportar.php?tipo=itens">Exportar notas por item (CSV)</a>
  </div>
</div>
<form class="filtros nao-imprimir" method="get">
  <div><label>Ciclo</label><select name="ciclo"><?= opcoes(ciclos(), 'id', 'nome', $f['ciclo'], 'Todos') ?></select></div>
  <div style="min-width:280px"><label>Unidade</label><select name="unidade"><?= opcoes(consulta('SELECT id, nome FROM unidade ORDER BY nome'), 'id', 'nome', $f['unidade'], 'Todas') ?></select></div>
  <div><label>Situação</label><select name="situacao"><option value="">Todas</option>
    <?php foreach (['concluida', 'incompleta', 'rascunho', 'em_conferencia'] as $s): ?><option value="<?= $s ?>" <?= $f['situacao'] === $s ? 'selected' : '' ?>><?= rotulo($s) ?></option><?php endforeach; ?></select></div>
  <div><button class="btn">Filtrar</button></div>
</form>
<div class="tabela"><table>
  <tr><th>Data</th><th>Ciclo</th><th>Unidade</th><th>Tipo</th><th class="num">Física</th><th class="num">Humana</th><th class="num">Plano</th><th class="num">Usuário</th><th class="num">Pontos</th><th class="num">Nota final</th><th>Situação</th></tr>
  <?php foreach ($lista as $r): ?>
  <tr>
    <td><a href="visita.php?id=<?= $r['visita_id'] ?>"><?= data_br($r['data_visita']) ?></a></td>
    <td><?= e($r['ciclo']) ?></td>
    <td><a href="unidade.php?id=<?= $r['unidade_id'] ?>"><?= e($r['unidade']) ?></a></td>
    <td class="mini"><?= e($r['tipo_unidade']) ?></td>
    <td class="num"><?= selo($r['perc_infra_fisica']) ?></td><td class="num"><?= selo($r['perc_infra_humana']) ?></td>
    <td class="num"><?= selo($r['perc_plano_municipal']) ?></td><td class="num"><?= selo($r['perc_percepcao_usuario']) ?></td>
    <td class="num"><?= (int)$r['pontos_totais'] ?>/<?= (int)$r['pontos_possiveis'] ?></td>
    <td class="num"><?= selo($r['nota_final']) ?></td>
    <td><span class="tag <?= e($r['situacao']) ?>"><?= rotulo($r['situacao']) ?></span></td>
  </tr>
  <?php endforeach; ?>
</table></div>
<?php require __DIR__ . '/inc/rodape.php';
