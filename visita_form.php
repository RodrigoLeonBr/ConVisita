<?php
require __DIR__ . '/inc/base.php';
exige_login();
exige_edicao();

$id = (int)get('id', post('id', 0));
$v = $id ? linha('SELECT * FROM visita WHERE id = ?', [$id]) : null;
if ($id && !$v) exit('Visita não encontrada.');
$itens = consulta('SELECT i.*, d.codigo AS dcod FROM item_indicador i JOIN dimensao d ON d.id = i.dimensao_id WHERE i.ativo = 1 ORDER BY i.ordem');
$dims = consulta('SELECT * FROM dimensao ORDER BY ordem');

// ------------------------------------------------------------ exclusao
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('acao') === 'excluir' && $v) {
    confere_csrf();
    exige_admin();
    executa('DELETE FROM visita WHERE id = ?', [$id]);
    aviso('Visita excluída.');
    redireciona('visitas.php');
}

// ------------------------------------------------------------ gravacao
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('acao') === 'salvar') {
    confere_csrf();
    $data = post('data_visita');
    $unid = (int)post('unidade_id');
    $erros = [];
    if (!$unid) $erros[] = 'Selecione a unidade.';
    if (!$data || !strtotime($data)) $erros[] = 'Informe a data da visita.';
    if (!$erros) {
        $cicloId = (int)post('ciclo_id');
        if (!$cicloId) {
            $c = linha('SELECT id FROM ciclo WHERE ? BETWEEN data_inicio AND data_fim ORDER BY data_inicio DESC LIMIT 1', [$data]);
            if ($c) $cicloId = (int)$c['id'];
            else {
                $ano = substr($data, 0, 4);
                executa('INSERT INTO ciclo (nome, data_inicio, data_fim) VALUES (?, ?, ?)', [$ano, "$ano-01-01", "$ano-12-31"]);
                $cicloId = (int)db()->lastInsertId();
            }
        }
        $notas = $_POST['nota'] ?? [];
        $faltam = 0;
        foreach ($itens as $i) { if (($notas[$i['id']] ?? '') === '') $faltam++; }
        $sit = post('situacao', 'finalizar');
        if ($sit === 'finalizar') $sit = $faltam ? 'incompleta' : 'concluida';
        if (!in_array($sit, ['rascunho', 'concluida', 'incompleta', 'em_conferencia'], true)) $sit = 'rascunho';

        $pdo = db();
        $pdo->beginTransaction();
        $campos = [$unid, $cicloId, $data, post('data_aproximada') ? 1 : 0, post('tipo') === 'extraordinaria' ? 'extraordinaria' : 'ordinaria', $sit, nulo(post('relato'))];
        if ($v) {
            executa('UPDATE visita SET unidade_id=?, ciclo_id=?, data_visita=?, data_aproximada=?, tipo=?, situacao=?, relato=?, atualizado_em=CURRENT_TIMESTAMP WHERE id=?', [...$campos, $id]);
        } else {
            executa("INSERT INTO visita (uuid, unidade_id, ciclo_id, data_visita, data_aproximada, tipo, situacao, relato, origem) VALUES (?,?,?,?,?,?,?,?, 'sistema')", [novo_uuid(), ...$campos]);
            $id = (int)$pdo->lastInsertId();
        }
        executa('DELETE FROM avaliacao_item WHERE visita_id = ?', [$id]);
        foreach ($itens as $i) {
            $n = (string)($notas[$i['id']] ?? '');
            $na = $n === 'NA' ? 1 : 0;
            $p = ($n === '' || $na) ? null : max(0, min((int)$i['pontuacao_max'], (int)$n));
            executa('INSERT INTO avaliacao_item (visita_id, item_id, pontuacao, nao_se_aplica, observacao) VALUES (?,?,?,?,?)',
                [$id, $i['id'], $p, $na, nulo(trim((string)($_POST['obs'][$i['id']] ?? '')))]);
        }
        executa('DELETE FROM comentario_dimensao WHERE visita_id = ?', [$id]);
        foreach ($dims as $d) {
            $t = trim((string)($_POST['comentario'][$d['id']] ?? ''));
            if ($t !== '') executa('INSERT INTO comentario_dimensao (visita_id, dimensao_id, texto) VALUES (?,?,?)', [$id, $d['id'], $t]);
        }
        executa('DELETE FROM visita_conselheiro WHERE visita_id = ?', [$id]);
        foreach ((array)($_POST['conselheiros'] ?? []) as $c) {
            executa('INSERT INTO visita_conselheiro (visita_id, conselheiro_id) VALUES (?,?)', [$id, (int)$c]);
        }
        $pdo->commit();
        aviso('Visita gravada' . ($sit === 'incompleta' ? " como incompleta ($faltam item(ns) sem pontuação)." : '.'));
        redireciona('visita.php?id=' . $id);
    }
    foreach ($erros as $er) aviso($er, 'erro');
}

// ------------------------------------------------------------ dados do formulario
$aval = [];
$coment = [];
$selCons = [];
if ($v) {
    foreach (consulta('SELECT * FROM avaliacao_item WHERE visita_id = ?', [$id]) as $a) $aval[$a['item_id']] = $a;
    foreach (consulta('SELECT * FROM comentario_dimensao WHERE visita_id = ?', [$id]) as $c) $coment[$c['dimensao_id']] = $c['texto'];
    $selCons = array_column(consulta('SELECT conselheiro_id FROM visita_conselheiro WHERE visita_id = ?', [$id]), 'conselheiro_id');
}
$v = $v ?: ['unidade_id' => (int)get('unidade', 0), 'ciclo_id' => 0, 'data_visita' => date('Y-m-d'), 'data_aproximada' => 0,
            'tipo' => 'ordinaria', 'situacao' => 'rascunho', 'relato' => ''];
$pesos = [];
foreach ($dims as $d) $pesos[$d['id']] = (float)$d['peso'];
$fx = array_map(fn($f) => ['cor' => $f['cor'], 'li' => (float)$f['limite_inferior'], 'ls' => (float)$f['limite_superior']], faixas());
$conselheiros = consulta('SELECT * FROM conselheiro WHERE ativo = 1 ORDER BY nome');

$titulo = $id ? 'Editar visita' : 'Nova visita'; $menu = 'visitas';
require __DIR__ . '/inc/topo.php';
?>
<h1><?= $id ? 'Editar visita' : 'Registrar visita' ?></h1>
<p class="sub">Pontue cada item: <b>0</b> não atende, <b>1</b> atende parcialmente, <b>2</b> atende plenamente. Use <b>N/A</b> quando o item não se aplica à unidade (o peso é redistribuído) e deixe em branco o que não foi avaliado.</p>

<form method="post" id="form-visita" data-pesos='<?= e(json_encode($pesos)) ?>' data-faixas='<?= e(json_encode($fx)) ?>'>
  <?= csrf() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="acao" value="salvar">
  <div class="cartao form-grade">
    <div style="grid-column:span 2"><label>Unidade de saúde</label><select name="unidade_id" required><?= opcoes(consulta('SELECT id, nome FROM unidade ORDER BY nome'), 'id', 'nome', $v['unidade_id'], 'Selecione...') ?></select></div>
    <div><label>Data da visita</label><input type="date" name="data_visita" value="<?= e($v['data_visita']) ?>" required>
      <label style="margin-top:4px"><input type="checkbox" name="data_aproximada" value="1" <?= $v['data_aproximada'] ? 'checked' : '' ?>> data aproximada</label></div>
    <div><label>Ciclo</label><select name="ciclo_id"><?= opcoes(ciclos(), 'id', 'nome', $v['ciclo_id'], 'Automático (pelo ano da data)') ?></select></div>
    <div><label>Tipo de visita</label><select name="tipo">
      <option value="ordinaria">Ordinária</option><option value="extraordinaria" <?= $v['tipo'] === 'extraordinaria' ? 'selected' : '' ?>>Extraordinária</option></select></div>
    <div style="grid-column:1/-1"><label>Conselheiros participantes</label>
      <?php if ($conselheiros): foreach ($conselheiros as $c): ?>
        <label style="display:inline-block;margin-right:14px;color:inherit"><input type="checkbox" name="conselheiros[]" value="<?= $c['id'] ?>" <?= in_array($c['id'], $selCons) ? 'checked' : '' ?>> <?= e($c['nome']) ?></label>
      <?php endforeach; else: ?><span class="mini">Nenhum conselheiro cadastrado (<a href="conselheiros.php">cadastrar</a>).</span><?php endif; ?>
    </div>
  </div>

  <div class="cartao">
    <div class="cab" style="margin:0"><h3 style="margin:0">Avaliação por item</h3><div>Nota final estimada: <span id="calc-total" class="selo cinza">–</span></div></div>
    <?php foreach ($dims as $d): ?>
      <div class="dim-cab"><b><?= e($d['codigo'] . ' · ' . $d['nome']) ?></b><span class="calc" id="calc-dim-<?= $d['id'] ?>"></span></div>
      <?php foreach ($itens as $i): if ($i['dimensao_id'] != $d['id']) continue;
        $a = $aval[$i['id']] ?? null;
        $cur = $a ? ($a['nao_se_aplica'] ? 'NA' : ($a['pontuacao'] === null ? '' : (string)$a['pontuacao'])) : ''; ?>
        <div class="item-aval" data-dim="<?= $d['id'] ?>" data-max="<?= (int)$i['pontuacao_max'] ?>">
          <div class="cod"><?= e($i['codigo']) ?></div>
          <div><b><?= e($i['nome']) ?></b><div class="meta">Meta: <?= e($i['meta']) ?></div><div class="meta">Fonte: <?= e($i['fonte']) ?></div></div>
          <div>
            <div class="notas">
              <?php foreach (['' => 'Em branco', '0' => '0', '1' => '1', '2' => '2', 'NA' => 'N/A'] as $val => $rot): ?>
                <label><input type="radio" name="nota[<?= $i['id'] ?>]" value="<?= $val ?>" <?= $cur === (string)$val ? 'checked' : '' ?>><span><?= $rot ?></span></label>
              <?php endforeach; ?>
            </div>
            <input type="text" name="obs[<?= $i['id'] ?>]" placeholder="Observação do item (opcional)" value="<?= e($a['observacao'] ?? '') ?>" style="margin-top:6px">
          </div>
        </div>
      <?php endforeach; ?>
      <label style="margin-top:10px">Comentários e oportunidades de melhoria · <?= e($d['nome_curto']) ?></label>
      <textarea name="comentario[<?= $d['id'] ?>]"><?= e($coment[$d['id']] ?? '') ?></textarea>
    <?php endforeach; ?>
  </div>

  <div class="cartao">
    <label>Relato geral da visita</label><textarea name="relato" style="min-height:110px"><?= e($v['relato']) ?></textarea>
    <br><br>
    <div class="acoes">
      <label style="margin:0;color:inherit">Gravar como:</label>
      <select name="situacao" style="width:auto">
        <option value="finalizar">Finalizada (concluída ou incompleta, conforme preenchimento)</option>
        <option value="rascunho" <?= $v['situacao'] === 'rascunho' && $id ? 'selected' : '' ?>>Rascunho (não entra no painel)</option>
        <?php if (e_admin()): ?><option value="em_conferencia" <?= $v['situacao'] === 'em_conferencia' ? 'selected' : '' ?>>Em conferência (não entra no painel)</option><?php endif; ?>
      </select>
      <button class="btn">Gravar visita</button>
      <a class="btn sec" href="<?= $id ? 'visita.php?id=' . $id : 'visitas.php' ?>">Cancelar</a>
    </div>
  </div>
</form>
<?php if ($id && e_admin()): ?>
<form method="post" class="nao-imprimir"><?= csrf() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="acao" value="excluir">
  <button class="btn perigo peq" data-confirma="Excluir definitivamente esta visita e suas notas?">Excluir visita</button></form>
<?php endif; ?>
<?php require __DIR__ . '/inc/rodape.php';
