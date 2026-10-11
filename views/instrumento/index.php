<div class="cab"><div><h1 class="h3">Instrumento de avaliação e cadastros</h1>
  <p class="sub">Dimensões, itens, pesos e faixas usados no cálculo. Alterações valem para todas as visitas, inclusive as já registradas.</p></div>
  <div class="acoes"><a class="btn sec" href="<?= BASE ?>/conselheiros">Conselheiros</a><?php if ($adm): ?><a class="btn sec" href="<?= BASE ?>/usuarios">Usuários</a><a class="btn" href="<?= BASE ?>/backup">Backup e sincronização</a><?php endif; ?></div></div>

<?php if ($editItem && $adm): ?>
<form method="post" class="cartao" action="<?= BASE ?>/instrumento/salvar"><?= csrf() ?><input type="hidden" name="acao" value="item"><input type="hidden" name="id" value="<?= $editItem['id'] ?>">
  <h3 style="margin-top:0">Editar item <?= e($editItem['codigo']) ?></h3>
  <label>Nome</label><input type="text" name="nome" value="<?= e($editItem['nome']) ?>"><br><br>
  <label>Meta esperada</label><textarea name="meta"><?= e($editItem['meta']) ?></textarea><br><br>
  <label>Fonte de observação</label><textarea name="fonte"><?= e($editItem['fonte']) ?></textarea><br><br>
  <label>Periodicidade</label><input type="text" name="periodicidade" value="<?= e($editItem['periodicidade']) ?>"><br><br>
  <label style="color:inherit"><input type="checkbox" name="ativo" value="1" <?= $editItem['ativo'] ? 'checked' : '' ?>> Item ativo</label><br>
  <button class="btn">Gravar</button> <a class="btn sec" href="<?= BASE ?>/instrumento">Cancelar</a></form>
<?php endif; ?>

<div class="grade g2">
<form method="post" class="cartao" action="<?= BASE ?>/instrumento/salvar"><?= csrf() ?><input type="hidden" name="acao" value="pesos">
  <h3 style="margin-top:0">Dimensões e pesos</h3>
  <table><tr><th>Dimensão</th><th class="num">Itens</th><th class="num">Pontos máx.</th><th class="num">Peso</th></tr>
  <?php foreach ($dims as $d): $its = array_filter($itens, fn($i) => $i['dimensao_id'] == $d['id'] && $i['ativo']); ?>
    <tr><td><?= e($d['codigo'] . ' · ' . $d['nome']) ?></td><td class="num"><?= count($its) ?></td><td class="num"><?= array_sum(array_column($its, 'pontuacao_max')) ?></td>
      <td class="num"><?php if ($adm): ?><input type="number" step="0.01" min="0" max="1" name="peso[<?= $d['id'] ?>]" value="<?= (float)$d['peso'] ?>" style="width:80px"><?php else: ?><?= pct($d['peso'], 0) ?><?php endif; ?></td></tr>
  <?php endforeach; ?></table>
  <?php if ($adm): ?><br><button class="btn peq">Gravar pesos</button> <span class="mini">Informe em fração (0,25 = 25%). A soma deve ser 1.</span><?php endif; ?>
</form>
<form method="post" class="cartao" action="<?= BASE ?>/instrumento/salvar"><?= csrf() ?><input type="hidden" name="acao" value="faixas">
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
<div class="table-responsive"><table class="table table-striped align-middle">
  <thead><tr><th scope="col">Item</th><th scope="col">Nome</th><th scope="col" class="d-none d-md-table-cell">Meta esperada</th><th scope="col" class="d-none d-lg-table-cell">Fonte de observação</th><th scope="col" class="d-none d-lg-table-cell">Periodicidade</th><th scope="col"></th></tr></thead>
  <tbody>
  <?php foreach ($itens as $i): ?>
  <tr><td><b><?= e($i['codigo']) ?></b></td><td><?= e($i['nome']) ?><?= $i['ativo'] ? '' : ' <span class="badge text-bg-secondary">inativo</span>' ?></td><td class="d-none d-md-table-cell small"><?= e($i['meta']) ?></td><td class="d-none d-lg-table-cell small"><?= e($i['fonte']) ?></td><td class="d-none d-lg-table-cell small"><?= e($i['periodicidade']) ?></td>
    <td><?php if ($adm): ?><a class="btn peq sec" href="<?= BASE ?>/instrumento?item=<?= $i['id'] ?>">Editar</a><?php endif; ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>

<div class="grade g2" style="margin-top:16px">
  <div class="cartao"><h3 style="margin-top:0">Ciclos de avaliação</h3>
    <table><?php foreach (ciclos() as $c): ?><tr><td><?= e($c['nome']) ?></td><td><?= data_br($c['data_inicio']) ?> a <?= data_br($c['data_fim']) ?></td></tr><?php endforeach; ?></table>
    <?php if ($adm): ?><form method="post" class="filtros" style="margin-top:10px" action="<?= BASE ?>/instrumento/salvar"><?= csrf() ?><input type="hidden" name="acao" value="ciclo">
      <div><label>Nome</label><input type="text" name="nome" required placeholder="ex.: 2027"></div><div><label>Início</label><input type="date" name="inicio" required></div><div><label>Fim</label><input type="date" name="fim" required></div><div><button class="btn peq">Criar</button></div></form><?php endif; ?>
  </div>
  <div class="cartao"><h3 style="margin-top:0">Tipos de unidade</h3>
    <p class="mini"><?= e(implode(' · ', array_column(consulta('SELECT nome FROM tipo_unidade ORDER BY nome'), 'nome'))) ?></p>
    <?php if ($adm): ?><form method="post" class="filtros" action="<?= BASE ?>/instrumento/salvar"><?= csrf() ?><input type="hidden" name="acao" value="tipo"><div style="min-width:260px"><label>Novo tipo</label><input type="text" name="nome" required></div><div><button class="btn peq">Incluir</button></div></form><?php endif; ?>
  </div>
</div>
