<?php
require __DIR__ . '/inc/base.php';
require __DIR__ . '/inc/consultas.php';
exige_login();

$cicloId = (int)get('ciclo', ciclo_padrao());
$tipoId = (int)get('tipo', 0) ?: null;
$ciclo = linha('SELECT * FROM ciclo WHERE id = ?', [$cicloId]);
$res = $cicloId ? resultados_ciclo($cicloId, $tipoId) : [];
$completas = array_filter($res, fn($r) => $r['situacao'] === 'concluida');
$exibir = in_array(get('exibir'), ['visitadas', 'sem_visita'], true) ? get('exibir') : 'todas';

// Unidades ativas sem visita valida no ciclo (respeitam o filtro de tipo)
$visitadas = array_column($res, 'unidade_id');
$faltantes = array_values(array_filter(consulta(
    "SELECT u.id AS unidade_id, u.nome AS unidade, t.nome AS tipo_unidade, a.ultima_visita, a.visita_id, a.nota_final
       FROM unidade u LEFT JOIN tipo_unidade t ON t.id = u.tipo_id LEFT JOIN vw_agenda a ON a.unidade_id = u.id
      WHERE u.situacao = 'ativa'" . ($tipoId ? ' AND u.tipo_id = ' . $tipoId : '') . ' ORDER BY u.nome'),
    fn($u) => !in_array($u['unidade_id'], $visitadas)));

$totUnid = (int)linha("SELECT COUNT(*) n FROM unidade WHERE situacao = 'ativa'" . ($tipoId ? ' AND tipo_id = ' . $tipoId : ''))['n'];
$media = $completas ? array_sum(array_column($completas, 'nota_final')) / count($completas) : null;
$dist = ['verde' => 0, 'amarelo' => 0, 'vermelho' => 0];
foreach ($completas as $r) { $c = farol($r['nota_final']); if (isset($dist[$c])) $dist[$c]++; }
$medDim = [];
foreach (['perc_infra_fisica' => 'Infraestrutura Física', 'perc_infra_humana' => 'Infraestrutura Humana',
          'perc_plano_municipal' => 'Plano Municipal de Saúde', 'perc_percepcao_usuario' => 'Percepção do Usuário'] as $k => $rot) {
    $v = array_filter(array_column($res, $k), fn($x) => $x !== null);
    $medDim[$rot] = $v ? array_sum($v) / count($v) : null;
}
$agenda = linha("SELECT SUM(situacao_agenda='atrasada') atrasadas, SUM(situacao_agenda='sem_visita') sem_visita,
                        SUM(situacao_agenda='proximos_30_dias') proximas FROM vw_agenda a JOIN unidade u ON u.id = a.unidade_id"
                 . ($tipoId ? ' WHERE u.tipo_id = ' . $tipoId : ''));
$pend = (int)linha("SELECT COUNT(*) n FROM pendencia p JOIN unidade u ON u.id = p.unidade_id WHERE p.situacao IN ('aberta','em_andamento')"
                 . ($tipoId ? ' AND u.tipo_id = ' . $tipoId : ''))['n'];
$itens = $cicloId ? medias_itens($cicloId, $tipoId) : [];
usort($itens, fn($a, $b) => ($a['media'] ?? 9) <=> ($b['media'] ?? 9));

$titulo = 'Painel'; $menu = 'painel';
require __DIR__ . '/inc/topo.php';
?>
<div class="cab">
  <div><h1>Painel de desempenho das unidades</h1>
  <p class="sub">Resultado da visita mais recente de cada unidade no ciclo selecionado. Nota final = média ponderada das quatro dimensões (peso 25% cada), com redistribuição do peso quando uma dimensão não se aplica.</p></div>
  <form class="filtros nao-imprimir" method="get">
    <div><label>Ciclo</label><select name="ciclo" onchange="this.form.submit()"><?= opcoes(ciclos(), 'id', 'nome', $cicloId) ?></select></div>
    <div><label>Tipo de unidade</label><select name="tipo" onchange="this.form.submit()"><?= opcoes(consulta('SELECT * FROM tipo_unidade ORDER BY nome'), 'id', 'nome', $tipoId, 'Todos') ?></select></div>
    <div><label>Exibir na classificação</label><select name="exibir" onchange="this.form.submit()">
      <?php foreach (['todas' => 'Todas as unidades', 'visitadas' => 'Somente visitadas', 'sem_visita' => 'Somente sem visita no ciclo'] as $k => $r): ?>
        <option value="<?= $k ?>" <?= $exibir === $k ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?></select></div>
  </form>
</div>

<div class="grade g4">
  <div class="cartao kpi"><div class="rot">Unidades visitadas em <?= e($ciclo['nome'] ?? '') ?></div><div class="valor"><?= count($res) ?> <small class="mini">de <?= $totUnid ?></small></div><div class="det"><?= count($res) - count($completas) ?> com avaliação incompleta · <a href="?ciclo=<?= $cicloId ?>&tipo=<?= (int)$tipoId ?>&exibir=sem_visita#classificacao"><?= count($faltantes) ?> sem visita</a></div></div>
  <div class="cartao kpi"><div class="rot">Nota média (avaliações completas)</div><div class="valor"><?= selo($media) ?></div><div class="det"><?= count($completas) ?> unidades consideradas</div></div>
  <div class="cartao kpi"><div class="rot">Classificação</div><div class="valor" style="font-size:18px">
    <span class="selo verde"><?= $dist['verde'] ?> adequadas</span> <span class="selo amarelo"><?= $dist['amarelo'] ?> atenção</span> <span class="selo vermelho"><?= $dist['vermelho'] ?> críticas</span></div></div>
  <div class="cartao kpi"><div class="rot">Agenda de visitas</div><div class="valor"><?= (int)$agenda['atrasadas'] ?> <small class="mini">atrasadas</small></div>
    <div class="det"><?= (int)$agenda['proximas'] ?> nos próximos 30 dias · <?= (int)$agenda['sem_visita'] ?> sem visita registrada · <a href="pendencias.php"><?= $pend ?> pendências abertas</a></div></div>
</div>

<div class="grade g2">
  <div class="cartao"><h3 style="margin-top:0">Média por dimensão</h3>
    <table><?php foreach ($medDim as $rot => $v): ?><tr><td style="width:45%"><?= e($rot) ?></td><td><?= barra($v) ?></td></tr><?php endforeach; ?></table>
  </div>
  <div class="cartao"><h3 style="margin-top:0">Itens com pior desempenho no ciclo</h3>
    <table><tr><th>Item</th><th class="num">Média (0 a 2)</th><th class="num">Unidades com 0</th></tr>
    <?php foreach (array_slice($itens, 0, 5) as $i): ?>
      <tr><td><b><?= e($i['codigo']) ?></b> <?= e($i['nome']) ?></td><td class="num"><?= $i['media'] === null ? '–' : number_format((float)$i['media'], 2, ',', '') ?></td><td class="num"><?= (int)$i['zeros'] ?></td></tr>
    <?php endforeach; ?></table>
  </div>
</div>

<h2 id="classificacao">Classificação das unidades</h2>
<div class="tabela"><table>
  <tr><th>#</th><th>Unidade</th><th>Tipo</th><th>Visita</th><th>Infra. física</th><th>Infra. humana</th><th>Plano Municipal</th><th>Percepção usuário</th><th class="num">Nota final</th><th></th></tr>
  <?php if ($exibir !== 'sem_visita'): foreach ($res as $k => $r): ?>
  <tr>
    <td><?= $k + 1 ?></td>
    <td><a href="unidade.php?id=<?= $r['unidade_id'] ?>"><?= e($r['unidade']) ?></a></td>
    <td class="mini"><?= e($r['tipo_unidade']) ?></td>
    <td><a href="visita.php?id=<?= $r['visita_id'] ?>"><?= data_br($r['data_visita']) ?></a><?= $r['data_aproximada'] ? ' <span class="mini">(aprox.)</span>' : '' ?></td>
    <td><?= barra($r['perc_infra_fisica']) ?></td><td><?= barra($r['perc_infra_humana']) ?></td>
    <td><?= barra($r['perc_plano_municipal']) ?></td><td><?= barra($r['perc_percepcao_usuario']) ?></td>
    <td class="num"><?= selo($r['nota_final']) ?></td>
    <td><?= $r['situacao'] !== 'concluida' ? '<span class="tag ' . e($r['situacao']) . '">' . rotulo($r['situacao']) . '</span>' : '' ?></td>
  </tr>
  <?php endforeach; endif; ?>
  <?php if ($exibir !== 'visitadas'): foreach ($faltantes as $f): ?>
  <tr class="sem-visita">
    <td>–</td>
    <td><a href="unidade.php?id=<?= $f['unidade_id'] ?>"><?= e($f['unidade']) ?></a></td>
    <td class="mini"><?= e($f['tipo_unidade']) ?></td>
    <td colspan="5" class="mini">Sem visita em <?= e($ciclo['nome'] ?? '') ?><?php if ($f['visita_id']): ?> · última visita em <a href="visita.php?id=<?= $f['visita_id'] ?>"><?= data_br($f['ultima_visita']) ?></a> (<?= pct($f['nota_final']) ?>)<?php else: ?> · nenhuma visita registrada<?php endif; ?></td>
    <td class="num"><span class="selo cinza">sem visita</span></td>
    <td class="nao-imprimir"><?php if (pode_editar()): ?><a class="btn peq sec" href="visita_form.php?unidade=<?= $f['unidade_id'] ?>">Registrar</a><?php endif; ?></td>
  </tr>
  <?php endforeach; endif; ?>
  <?php if (($exibir === 'sem_visita' || !$res) && ($exibir === 'visitadas' || !$faltantes)): ?><tr><td colspan="10">Nenhuma unidade nesta seleção.</td></tr><?php endif; ?>
</table></div>
<p class="legenda">Faixas: <?php foreach (faixas() as $f): ?><span class="selo <?= e($f['cor']) ?>"><?= e($f['rotulo']) ?></span> <?php endforeach; ?>
 <span class="selo cinza">Sem visita no ciclo</span> · Visitas incompletas têm nota calculada apenas sobre as dimensões avaliadas e não entram na média geral.</p>
<?php require __DIR__ . '/inc/rodape.php';
