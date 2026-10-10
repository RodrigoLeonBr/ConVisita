<?php
require __DIR__ . '/inc/base.php';
exige_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    confere_csrf();
    exige_admin();
    $acao = post('acao');
    if ($acao === 'pesos') {
        $p = array_map('floatval', (array)$_POST['peso']);
        if (abs(array_sum($p) - 1) > 0.0001) aviso('A soma dos pesos deve ser 100%. Nada foi alterado.', 'erro');
        else { foreach ($p as $id => $v) executa('UPDATE dimensao SET peso = ? WHERE id = ?', [$v, (int)$id]); aviso('Pesos atualizados.'); }
    } elseif ($acao === 'faixas') {
        foreach ((array)$_POST['li'] as $id => $li) executa('UPDATE faixa_classificacao SET limite_inferior=?, limite_superior=?, rotulo=? WHERE id=?',
            [(float)$li, (float)$_POST['ls'][$id], trim((string)$_POST['rot'][$id]), (int)$id]);
        aviso('Faixas atualizadas.');
    } elseif ($acao === 'item') {
        executa('UPDATE item_indicador SET nome=?, meta=?, fonte=?, periodicidade=?, ativo=? WHERE id=?',
            [post('nome'), post('meta'), nulo(post('fonte')), nulo(post('periodicidade')), post('ativo') ? 1 : 0, (int)post('id')]);
        aviso('Item atualizado.');
    } elseif ($acao === 'ciclo') {
        try { executa('INSERT INTO ciclo (nome, data_inicio, data_fim) VALUES (?,?,?)', [post('nome'), post('inicio'), post('fim')]); aviso('Ciclo criado.'); }
        catch (PDOException $e) { aviso('Não foi possível criar o ciclo (nome repetido?).', 'erro'); }
    } elseif ($acao === 'tipo') {
        try { executa('INSERT INTO tipo_unidade (nome) VALUES (?)', [post('nome')]); aviso('Tipo criado.'); }
        catch (PDOException $e) { aviso('Tipo já existe.', 'erro'); }
    }
    redireciona('instrumento.php');
}
$dims = consulta('SELECT * FROM dimensao ORDER BY ordem');
$itens = consulta('SELECT * FROM item_indicador ORDER BY ordem');
$adm = e_admin();
$editItem = get('item') ? linha('SELECT * FROM item_indicador WHERE id = ?', [(int)get('item')]) : null;
$titulo = 'Instrumento e cadastros'; $menu = 'cadastros';
require __DIR__ . '/inc/topo.php';
?>
<div class="cab"><div><h1>Instrumento de avaliação e cadastros</h1>
  <p class="sub">Dimensões, itens, pesos e faixas usados no cálculo. Alterações valem para todas as visitas, inclusive as já registradas.</p></div>
  <div class="acoes"><a class="btn sec" href="conselheiros.php">Conselheiros</a><?php if ($adm): ?><a class="btn sec" href="usuarios.php">Usuários</a><a class="btn" href="backup.php">Backup e sincronização</a><?php endif; ?></div></div>

<?php if ($editItem && $adm): ?>
<form method="post" class="cartao"><?= csrf() ?><input type="hidden" name="acao" value="item"><input type="hidden" name="id" value="<?= $editItem['id'] ?>">
  <h3 style="margin-top:0">Editar item <?= e($editItem['codigo']) ?></h3>
  <label>Nome</label><input type="text" name="nome" value="<?= e($editItem['nome']) ?>"><br><br>
  <label>Meta esperada</label><textarea name="meta"><?= e($editItem['meta']) ?></textarea><br><br>
  <label>Fonte de observação</label><textarea name="fonte"><?= e($editItem['fonte']) ?></textarea><br><br>
  <label>Periodicidade</label><input type="text" name="periodicidade" value="<?= e($editItem['periodicidade']) ?>"><br><br>
  <label style="color:inherit"><input type="checkbox" name="ativo" value="1" <?= $editItem['ativo'] ? 'checked' : '' ?>> Item ativo</label><br>
  <button class="btn">Gravar</button> <a class="btn sec" href="instrumento.php">Cancelar</a></form>
<?php endif; ?>

<div class="grade g2">
<form method="post" class="cartao"><?= csrf() ?><input type="hidden" name="acao" value="pesos">
  <h3 style="margin-top:0">Dimensões e pesos</h3>
  <table><tr><th>Dimensão</th><th class="num">Itens</th><th class="num">Pontos máx.</th><th class="num">Peso</th></tr>
  <?php foreach ($dims as $d): $its = array_filter($itens, fn($i) => $i['dimensao_id'] == $d['id'] && $i['ativo']); ?>
    <tr><td><?= e($d['codigo'] . ' · ' . $d['nome']) ?></td><td class="num"><?= count($its) ?></td><td class="num"><?= array_sum(array_column($its, 'pontuacao_max')) ?></td>
      <td class="num"><?php if ($adm): ?><input type="number" step="0.01" min="0" max="1" name="peso[<?= $d['id'] ?>]" value="<?= (float)$d['peso'] ?>" style="width:80px"><?php else: ?><?= pct($d['peso'], 0) ?><?php endif; ?></td></tr>
  <?php endforeach; ?></table>
  <?php if ($adm): ?><br><button class="btn peq">Gravar pesos</button> <span class="mini">Informe em fração (0,25 = 25%). A soma deve ser 1.</span><?php endif; ?>
</form>
<form method="post" class="cartao"><?= csrf() ?><input type="hidden" name="acao" value="faixas">
  <h3 style="margin-top:0">Faixas de classificação (farol)</h3>
  <table><tr><th>Cor</th><th>Rótulo</th><th class="num">De (inclusive)</th><th class="num">Até (exclusive)</th></tr>
  <?php foreach (faixas() as $f): ?>
    <tr><td><span class="selo <?= e($f['cor']) ?>"><?= e($f['cor']) ?></span></td>
    <?php if ($adm): ?><td><input type="text" name="rot[<?= $f['id'] ?>]" value="<?= e($f['rotulo']) ?>"></td>
      <td><input type="number" step="0.0001" name="li[<?= $f['id'] ?>]" value="<?= (float)$f['limite_inferior'] ?>"></td>
      <td><input type="number" step="0.0001" name="ls[<?= $f['id'] ?>]" value="<?= (float)$f['limite_superior'] ?>"></td>
    <?php else: ?><td><?= e($f['rotulo']) ?></td><td class="num"><?= pct($f['limite_inferior']) ?></td><td class="num"><?= pct(min(1, $f['limite_superior'])) ?></td><?php endif; ?></tr>
  <?php endforeach; ?></table>
  <?php if ($adm): ?><br><button class="btn peq">Gravar faixas</button><?php endif; ?>
</form>
</div>

<h2>Itens do instrumento</h2>
<div class="tabela"><table>
  <tr><th>Item</th><th>Nome</th><th>Meta esperada</th><th>Fonte de observação</th><th>Periodicidade</th><th></th></tr>
  <?php foreach ($itens as $i): ?>
  <tr><td><b><?= e($i['codigo']) ?></b></td><td><?= e($i['nome']) ?><?= $i['ativo'] ? '' : ' <span class="tag">inativo</span>' ?></td><td class="mini"><?= e($i['meta']) ?></td><td class="mini"><?= e($i['fonte']) ?></td><td class="mini"><?= e($i['periodicidade']) ?></td>
    <td><?php if ($adm): ?><a class="btn peq sec" href="?item=<?= $i['id'] ?>">Editar</a><?php endif; ?></td></tr>
  <?php endforeach; ?>
</table></div>

<div class="grade g2" style="margin-top:16px">
  <div class="cartao"><h3 style="margin-top:0">Ciclos de avaliação</h3>
    <table><?php foreach (ciclos() as $c): ?><tr><td><?= e($c['nome']) ?></td><td><?= data_br($c['data_inicio']) ?> a <?= data_br($c['data_fim']) ?></td></tr><?php endforeach; ?></table>
    <?php if ($adm): ?><form method="post" class="filtros" style="margin-top:10px"><?= csrf() ?><input type="hidden" name="acao" value="ciclo">
      <div><label>Nome</label><input type="text" name="nome" required placeholder="ex.: 2027"></div><div><label>Início</label><input type="date" name="inicio" required></div><div><label>Fim</label><input type="date" name="fim" required></div><div><button class="btn peq">Criar</button></div></form><?php endif; ?>
  </div>
  <div class="cartao"><h3 style="margin-top:0">Tipos de unidade</h3>
    <p class="mini"><?= e(implode(' · ', array_column(consulta('SELECT nome FROM tipo_unidade ORDER BY nome'), 'nome'))) ?></p>
    <?php if ($adm): ?><form method="post" class="filtros"><?= csrf() ?><input type="hidden" name="acao" value="tipo"><div style="min-width:260px"><label>Novo tipo</label><input type="text" name="nome" required></div><div><button class="btn peq">Incluir</button></div></form><?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/inc/rodape.php';
