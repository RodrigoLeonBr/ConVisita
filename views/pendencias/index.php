<div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
  <div><h1 class="h3 mb-1">Pendências e encaminhamentos</h1><p class="text-muted mb-0">Oportunidades de melhoria apontadas nas visitas, com responsável, prazo e acompanhamento.</p></div>
  <?php if (pode_editar()): ?><a class="btn btn-primary btn-sm d-print-none" href="<?= BASE ?>/pendencia/nova">Nova pendência</a><?php endif; ?>
</div>

<?php if ($edit && pode_editar()): ?>
<form method="post" class="cartao" action="<?= BASE ?>/pendencia/salvar">
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
  <button class="btn">Gravar</button> <a class="btn sec" href="<?= BASE ?>/pendencias">Cancelar</a>
</form>
<?php if ($edit['id']): ?><form method="post" action="<?= BASE ?>/pendencia/salvar"><?= csrf() ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><input type="hidden" name="acao" value="excluir"><button class="btn perigo peq" data-confirma="Excluir esta pendência?">Excluir</button></form><br><?php endif; ?>
<?php endif; ?>

<div class="mb-3 d-print-none">
  <?php foreach (['pendentes' => 'Abertas e em andamento', 'resolvida' => 'Resolvidas', 'cancelada' => 'Canceladas', 'todas' => 'Todas'] as $k => $r): ?>
    <a class="btn btn-sm <?= $filtro === $k ? 'btn-secondary' : 'btn-outline-secondary' ?>" href="<?= BASE ?>/pendencias?situacao=<?= $k ?>"><?= e($r) ?></a>
  <?php endforeach; ?>
</div>
<div class="table-responsive"><table class="table table-striped align-middle">
  <thead><tr><th scope="col">Unidade</th><th scope="col">Descrição</th><th scope="col" class="d-none d-md-table-cell">Dimensão</th><th scope="col" class="d-none d-md-table-cell">Visita</th><th scope="col">Responsável</th><th scope="col">Prazo</th><th scope="col">Situação</th></tr></thead>
  <tbody>
  <?php foreach ($lista as $p): $venc = $p['prazo'] && $p['prazo'] < date('Y-m-d') && in_array($p['situacao'], ['aberta', 'em_andamento']); ?>
  <tr><td><a href="<?= BASE ?>/unidade/<?= $p['unidade_id'] ?>"><?= e($p['unidade']) ?></a></td>
    <td><a href="<?= BASE ?>/pendencia/<?= $p['id'] ?>/editar"><?= e($p['descricao']) ?></a></td><td class="d-none d-md-table-cell small"><?= e($p['dim'] ?: 'Geral') ?></td>
    <td class="d-none d-md-table-cell"><?= $p['visita_id'] ? '<a href="' . BASE . '/visita/' . $p['visita_id'] . '">' . data_br($p['data_visita']) . '</a>' : '–' ?></td>
    <td><?= e($p['responsavel']) ?></td><td><?= data_br($p['prazo']) ?><?= $venc ? ' <span class="tag atrasada">vencida</span>' : '' ?></td>
    <td><span class="badge text-bg-secondary"><?= rotulo($p['situacao']) ?></span></td></tr>
  <?php endforeach; ?>
  <?php if (!$lista): ?><tr><td colspan="7">Nenhuma pendência nesta seleção.</td></tr><?php endif; ?>
  </tbody>
</table></div>
