<h1 class="h3"><?= $id ? 'Editar unidade' : 'Nova unidade' ?></h1>
<form method="post" class="cartao" action="<?= BASE ?>/unidade/salvar">
  <?= csrf() ?><input type="hidden" name="id" value="<?= $id ?>">
  <div class="form-grade">
    <div style="grid-column:span 2"><label>Nome</label><input type="text" name="nome" value="<?= e($u['nome']) ?>" required></div>
    <div><label>Tipo</label><select name="tipo_id"><?= opcoes(consulta('SELECT * FROM tipo_unidade ORDER BY nome'), 'id', 'nome', $u['tipo_id'], 'Selecione...') ?></select></div>
    <div><label>Gestão (direta, OSS, conveniada...)</label><input type="text" name="gestao" value="<?= e($u['gestao']) ?>"></div>
    <div style="grid-column:span 2"><label>Endereço</label><input type="text" name="endereco" value="<?= e($u['endereco']) ?>"></div>
    <div><label>Situação</label><select name="situacao"><?php foreach (['ativa', 'em_reforma', 'inativa'] as $s): ?><option value="<?= $s ?>" <?= $u['situacao'] === $s ? 'selected' : '' ?>><?= rotulo($s) ?></option><?php endforeach; ?></select></div>
    <div><label>Periodicidade de visita (meses)</label><input type="number" min="1" max="24" name="periodicidade_meses" value="<?= (int)$u['periodicidade_meses'] ?>"></div>
    <div><label>Nome da aba na planilha de origem</label><input type="text" name="nome_planilha" value="<?= e($u['nome_planilha']) ?>"></div>
    <div style="grid-column:1/-1"><label>Observações</label><textarea name="observacao"><?= e($u['observacao']) ?></textarea></div>
  </div><br>
  <button class="btn">Gravar</button> <a class="btn sec" href="<?= $id ? BASE . '/unidade/' . $id : BASE . '/unidades' ?>">Cancelar</a>
</form>
