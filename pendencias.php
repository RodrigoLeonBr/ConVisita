<?php
require __DIR__ . '/inc/base.php';
exige_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    confere_csrf();
    exige_edicao();
    $pid = (int)post('id');
    if (post('acao') === 'excluir' && $pid) {
        executa('DELETE FROM pendencia WHERE id = ?', [$pid]);
        aviso('Pendência excluída.');
        redireciona('pendencias.php');
    }
    $sit = in_array(post('situacao'), ['aberta', 'em_andamento', 'resolvida', 'cancelada'], true) ? post('situacao') : 'aberta';
    $visita = (int)post('visita_id') ?: null;
    $unid = (int)post('unidade_id');
    if ($visita && !$unid) $unid = (int)linha('SELECT unidade_id FROM visita WHERE id = ?', [$visita])['unidade_id'];
    $d = [$visita, $unid, nulo(post('dimensao_id')), post('descricao'), nulo(post('responsavel')), nulo(post('prazo')), $sit,
          $sit === 'resolvida' ? (nulo(post('resolvida_em')) ?? date('Y-m-d')) : null, nulo(post('observacao'))];
    if (!$unid || !$d[3]) { aviso('Informe a unidade e a descrição.', 'erro'); }
    else {
        if ($pid) executa('UPDATE pendencia SET visita_id=?, unidade_id=?, dimensao_id=?, descricao=?, responsavel=?, prazo=?, situacao=?, resolvida_em=?, observacao=?, atualizado_em=CURRENT_TIMESTAMP WHERE id=?', [...$d, $pid]);
        else executa('INSERT INTO pendencia (uuid, visita_id, unidade_id, dimensao_id, descricao, responsavel, prazo, situacao, resolvida_em, observacao) VALUES (?,?,?,?,?,?,?,?,?,?)', [novo_uuid(), ...$d]);
        aviso('Pendência gravada.');
        redireciona($visita ? 'visita.php?id=' . $visita : 'pendencias.php');
    }
}

$edit = null;
if (get('editar')) $edit = linha('SELECT * FROM pendencia WHERE id = ?', [(int)get('editar')]);
elseif (get('nova')) {
    $vis = (int)get('visita', 0);
    $edit = ['id' => 0, 'visita_id' => $vis ?: null, 'unidade_id' => $vis ? linha('SELECT unidade_id FROM visita WHERE id=?', [$vis])['unidade_id'] : (int)get('unidade', 0),
             'dimensao_id' => null, 'descricao' => '', 'responsavel' => '', 'prazo' => null, 'situacao' => 'aberta', 'resolvida_em' => null, 'observacao' => ''];
}
$filtro = (string)get('situacao', 'pendentes');
$w = $filtro === 'pendentes' ? "WHERE p.situacao IN ('aberta','em_andamento')" : ($filtro === 'todas' ? '' : 'WHERE p.situacao = ' . db()->quote($filtro));
$lista = consulta("SELECT p.*, u.nome AS unidade, d.nome_curto AS dim, v.data_visita FROM pendencia p
                    JOIN unidade u ON u.id = p.unidade_id LEFT JOIN dimensao d ON d.id = p.dimensao_id
                    LEFT JOIN visita v ON v.id = p.visita_id $w ORDER BY (p.prazo IS NULL), p.prazo, u.nome");
$titulo = 'Pendências'; $menu = 'pendencias';
require __DIR__ . '/inc/topo.php';
?>
<div class="cab">
  <div><h1>Pendências e encaminhamentos</h1><p class="sub">Oportunidades de melhoria apontadas nas visitas, com responsável, prazo e acompanhamento.</p></div>
  <?php if (pode_editar()): ?><a class="btn nao-imprimir" href="?nova=1">Nova pendência</a><?php endif; ?>
</div>

<?php if ($edit && pode_editar()): ?>
<form method="post" class="cartao">
  <?= csrf() ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><input type="hidden" name="visita_id" value="<?= e($edit['visita_id']) ?>">
  <div class="form-grade">
    <div style="grid-column:span 2"><label>Unidade</label><select name="unidade_id" required><?= opcoes(consulta('SELECT id, nome FROM unidade ORDER BY nome'), 'id', 'nome', $edit['unidade_id'], 'Selecione...') ?></select></div>
    <div><label>Dimensão</label><select name="dimensao_id"><?= opcoes(consulta('SELECT id, nome_curto FROM dimensao ORDER BY ordem'), 'id', 'nome_curto', $edit['dimensao_id'], 'Geral') ?></select></div>
    <div style="grid-column:1/-1"><label>Descrição</label><textarea name="descricao" required><?= e($edit['descricao']) ?></textarea></div>
    <div><label>Responsável</label><input type="text" name="responsavel" value="<?= e($edit['responsavel']) ?>"></div>
    <div><label>Prazo</label><input type="date" name="prazo" value="<?= e($edit['prazo']) ?>"></div>
    <div><label>Situação</label><select name="situacao"><?php foreach (['aberta', 'em_andamento', 'resolvida', 'cancelada'] as $s): ?><option value="<?= $s ?>" <?= $edit['situacao'] === $s ? 'selected' : '' ?>><?= rotulo($s) ?></option><?php endforeach; ?></select></div>
    <div><label>Resolvida em</label><input type="date" name="resolvida_em" value="<?= e($edit['resolvida_em']) ?>"></div>
    <div style="grid-column:1/-1"><label>Observação</label><input type="text" name="observacao" value="<?= e($edit['observacao']) ?>"></div>
  </div><br>
  <button class="btn">Gravar</button> <a class="btn sec" href="pendencias.php">Cancelar</a>
</form>
<?php if ($edit['id']): ?><form method="post"><?= csrf() ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><input type="hidden" name="acao" value="excluir"><button class="btn perigo peq" data-confirma="Excluir esta pendência?">Excluir</button></form><br><?php endif; ?>
<?php endif; ?>

<div class="acoes nao-imprimir" style="margin-bottom:12px">
  <?php foreach (['pendentes' => 'Abertas e em andamento', 'resolvida' => 'Resolvidas', 'cancelada' => 'Canceladas', 'todas' => 'Todas'] as $k => $r): ?>
    <a class="btn peq <?= $filtro === $k ? '' : 'sec' ?>" href="?situacao=<?= $k ?>"><?= e($r) ?></a>
  <?php endforeach; ?>
</div>
<div class="tabela"><table>
  <tr><th>Unidade</th><th>Descrição</th><th>Dimensão</th><th>Visita</th><th>Responsável</th><th>Prazo</th><th>Situação</th></tr>
  <?php foreach ($lista as $p): $venc = $p['prazo'] && $p['prazo'] < date('Y-m-d') && in_array($p['situacao'], ['aberta', 'em_andamento']); ?>
  <tr><td><a href="unidade.php?id=<?= $p['unidade_id'] ?>"><?= e($p['unidade']) ?></a></td>
    <td><a href="?editar=<?= $p['id'] ?>"><?= e($p['descricao']) ?></a></td><td class="mini"><?= e($p['dim'] ?: 'Geral') ?></td>
    <td><?= $p['visita_id'] ? '<a href="visita.php?id=' . $p['visita_id'] . '">' . data_br($p['data_visita']) . '</a>' : '–' ?></td>
    <td><?= e($p['responsavel']) ?></td><td><?= data_br($p['prazo']) ?><?= $venc ? ' <span class="tag atrasada">vencida</span>' : '' ?></td>
    <td><span class="tag <?= e($p['situacao']) ?>"><?= rotulo($p['situacao']) ?></span></td></tr>
  <?php endforeach; ?>
  <?php if (!$lista): ?><tr><td colspan="7">Nenhuma pendência nesta seleção.</td></tr><?php endif; ?>
</table></div>
<?php require __DIR__ . '/inc/rodape.php';
