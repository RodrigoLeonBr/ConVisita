<?php
require __DIR__ . '/inc/base.php';
exige_login();
exige_edicao();
$id = (int)get('id', post('id', 0));
$u = $id ? linha('SELECT * FROM unidade WHERE id = ?', [$id]) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    confere_csrf();
    $dados = [post('nome'), nulo(post('nome_planilha')), nulo(post('tipo_id')), nulo(post('gestao')), nulo(post('endereco')),
              in_array(post('situacao'), ['ativa', 'em_reforma', 'inativa'], true) ? post('situacao') : 'ativa',
              max(1, (int)post('periodicidade_meses', 6)), nulo(post('observacao'))];
    if (!$dados[0]) { aviso('Informe o nome da unidade.', 'erro'); }
    else {
        try {
            if ($u) {
                executa('UPDATE unidade SET nome=?, nome_planilha=?, tipo_id=?, gestao=?, endereco=?, situacao=?, periodicidade_meses=?, observacao=? WHERE id=?', [...$dados, $id]);
            } else {
                executa('INSERT INTO unidade (nome, nome_planilha, tipo_id, gestao, endereco, situacao, periodicidade_meses, observacao) VALUES (?,?,?,?,?,?,?,?)', $dados);
                $id = (int)db()->lastInsertId();
            }
            aviso('Cadastro gravado.');
            redireciona('unidade.php?id=' . $id);
        } catch (PDOException $ex) {
            aviso('Não foi possível gravar: já existe unidade com esse nome?', 'erro');
        }
    }
}
$u = $u ?: ['nome' => '', 'nome_planilha' => '', 'tipo_id' => '', 'gestao' => '', 'endereco' => '', 'situacao' => 'ativa', 'periodicidade_meses' => 6, 'observacao' => ''];
$titulo = 'Cadastro de unidade'; $menu = 'unidades';
require __DIR__ . '/inc/topo.php';
?>
<h1><?= $id ? 'Editar unidade' : 'Nova unidade' ?></h1>
<form method="post" class="cartao">
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
  <button class="btn">Gravar</button> <a class="btn sec" href="<?= $id ? 'unidade.php?id=' . $id : 'unidades.php' ?>">Cancelar</a>
</form>
<?php require __DIR__ . '/inc/rodape.php';
