<?php
require __DIR__ . '/inc/base.php';
require __DIR__ . '/inc/consultas.php';
exige_login();
$cs = ciclos();
$cB = (int)get('b', $cs[0]['id'] ?? 0);
$cA = (int)get('a', $cs[1]['id'] ?? 0);
$A = []; foreach (resultados_ciclo($cA) as $r) $A[$r['unidade_id']] = $r;
$B = []; foreach (resultados_ciclo($cB) as $r) $B[$r['unidade_id']] = $r;
$ids = array_unique(array_merge(array_keys($A), array_keys($B)));
$linhas = [];
foreach ($ids as $i) {
    $a = $A[$i] ?? null; $b = $B[$i] ?? null;
    $linhas[] = ['id' => $i, 'nome' => ($b ?? $a)['unidade'], 'a' => $a, 'b' => $b,
                 'dif' => ($a && $b) ? (float)$b['nota_final'] - (float)$a['nota_final'] : null];
}
usort($linhas, fn($x, $y) => [($x['dif'] === null), -($x['dif'] ?? 0)] <=> [($y['dif'] === null), -($y['dif'] ?? 0)]);
$IA = []; foreach (medias_itens($cA) as $r) $IA[$r['codigo']] = $r;
$IB = medias_itens($cB);
$nome = fn($id) => current(array_filter($cs, fn($c) => (int)$c['id'] === $id))['nome'] ?? '';
$titulo = 'Comparativo'; $menu = 'comparativo';
require __DIR__ . '/inc/topo.php';
?>
<div class="cab">
  <div><h1>Comparativo entre ciclos</h1><p class="sub">Evolução das unidades avaliadas nos dois ciclos (visita mais recente de cada ciclo).</p></div>
  <form class="filtros nao-imprimir"><div><label>Ciclo base</label><select name="a"><?= opcoes($cs, 'id', 'nome', $cA) ?></select></div>
    <div><label>Ciclo comparado</label><select name="b"><?= opcoes($cs, 'id', 'nome', $cB) ?></select></div><div><button class="btn">Comparar</button></div></form>
</div>
<div class="tabela"><table>
  <tr><th>Unidade</th><th class="num">Nota <?= e($nome($cA)) ?></th><th class="num">Nota <?= e($nome($cB)) ?></th><th class="num">Variação (p.p.)</th><th>Física</th><th>Humana</th><th>Plano</th><th>Usuário</th></tr>
  <?php foreach ($linhas as $l): ?>
  <tr><td><a href="unidade.php?id=<?= $l['id'] ?>"><?= e($l['nome']) ?></a></td>
    <td class="num"><?= $l['a'] ? selo($l['a']['nota_final']) : '–' ?></td>
    <td class="num"><?= $l['b'] ? selo($l['b']['nota_final']) : '–' ?></td>
    <td class="num" style="font-weight:600;color:<?= $l['dif'] === null ? 'inherit' : ($l['dif'] >= 0 ? 'var(--verde)' : 'var(--vermelho)') ?>"><?= $l['dif'] === null ? '–' : ($l['dif'] >= 0 ? '+' : '') . number_format($l['dif'] * 100, 1, ',', '') ?></td>
    <?php foreach (['perc_infra_fisica', 'perc_infra_humana', 'perc_plano_municipal', 'perc_percepcao_usuario'] as $k): ?>
      <td class="mini"><?= $l['a'] ? pct($l['a'][$k], 0) : '–' ?> → <?= $l['b'] ? pct($l['b'][$k], 0) : '–' ?></td>
    <?php endforeach; ?></tr>
  <?php endforeach; ?>
</table></div>
<h2>Média por item do instrumento</h2>
<div class="tabela"><table>
  <tr><th>Item</th><th>Dimensão</th><th class="num">Média <?= e($nome($cA)) ?></th><th class="num">Média <?= e($nome($cB)) ?></th><th class="num">Unidades com nota 0 (<?= e($nome($cB)) ?>)</th></tr>
  <?php foreach ($IB as $i): $a = $IA[$i['codigo']] ?? null; ?>
  <tr><td><b><?= e($i['codigo']) ?></b> <?= e($i['nome']) ?></td><td class="mini"><?= e($i['dimensao']) ?></td>
    <td class="num"><?= $a && $a['media'] !== null ? number_format((float)$a['media'], 2, ',', '') : '–' ?></td>
    <td class="num"><?= $i['media'] !== null ? number_format((float)$i['media'], 2, ',', '') : '–' ?></td>
    <td class="num"><?= (int)$i['zeros'] ?></td></tr>
  <?php endforeach; ?>
</table></div>
<?php require __DIR__ . '/inc/rodape.php';
