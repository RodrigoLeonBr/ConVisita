<?php
require __DIR__ . '/inc/base.php';
exige_login();
$id = (int)get('id');
$u = linha('SELECT u.*, t.nome AS tipo FROM unidade u LEFT JOIN tipo_unidade t ON t.id = u.tipo_id WHERE u.id = ?', [$id]);
if (!$u) exit('Unidade não encontrada.');
$ag = linha('SELECT * FROM vw_agenda WHERE unidade_id = ?', [$id]);
$hist = consulta('SELECT * FROM vw_resultado_visita WHERE unidade_id = ? ORDER BY data_visita DESC', [$id]);
$validas = array_values(array_filter(array_reverse($hist), fn($h) => in_array($h['situacao'], ['concluida', 'incompleta'], true) && $h['nota_final'] !== null));
$pend = consulta('SELECT p.*, d.nome_curto AS dim FROM pendencia p LEFT JOIN dimensao d ON d.id = p.dimensao_id WHERE p.unidade_id = ? ORDER BY FIELD(p.situacao,\'aberta\',\'em_andamento\',\'resolvida\',\'cancelada\'), p.prazo', [$id]);

// Grafico simples de evolucao (SVG)
function grafico(array $pts): string
{
    if (count($pts) < 2) return '<p class="mini">A evolução aparece a partir da segunda visita registrada.</p>';
    $w = 1000; $h = 220; $m = 34; $ml = 70; $mr = 50;
    $n = count($pts);
    $x = fn($i) => $ml + ($w - $ml - $mr) * $i / ($n - 1);
    $y = fn($v) => $h - $m + 6 - ($h - 2 * $m) * $v;
    $s = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" style="width:100%">';
    foreach ([0, .4, .8, 1] as $g) {
        $s .= '<line x1="' . $ml . '" x2="' . ($w - $mr) . '" y1="' . $y($g) . '" y2="' . $y($g) . '" stroke="#dfe4ea" />'
            . '<text x="2" y="' . ($y($g) + 4) . '" font-size="10" fill="#5b6878">' . ($g * 100) . '%</text>';
    }
    $l = '';
    foreach ($pts as $i => $p) $l .= ($i ? ' L' : 'M') . $x($i) . ' ' . $y((float)$p['nota_final']);
    $s .= '<path d="' . $l . '" fill="none" stroke="#0f5c8c" stroke-width="2.5" />';
    $cores = ['verde' => '#2e8b57', 'amarelo' => '#c99400', 'vermelho' => '#c0392b', 'cinza' => '#8a96a3'];
    foreach ($pts as $i => $p) {
        $s .= '<circle cx="' . $x($i) . '" cy="' . $y((float)$p['nota_final']) . '" r="5" fill="' . $cores[farol($p['nota_final'])] . '" />'
            . '<text x="' . $x($i) . '" y="' . ($y((float)$p['nota_final']) - 10) . '" font-size="13" font-weight="600" text-anchor="middle">' . pct($p['nota_final'], 0) . '</text>'
            . '<text x="' . $x($i) . '" y="' . ($h - 6) . '" font-size="10" text-anchor="middle" fill="#5b6878">' . date('m/Y', strtotime($p['data_visita'])) . '</text>';
    }
    return $s . '</svg>';
}

$titulo = $u['nome']; $menu = 'unidades';
require __DIR__ . '/inc/topo.php';
?>
<div class="cab">
  <div><h1><?= e($u['nome']) ?></h1>
    <p class="sub"><?= e($u['tipo']) ?><?= $u['gestao'] ? ' · gestão ' . e($u['gestao']) : '' ?> · <?= rotulo($u['situacao']) ?> · visita a cada <?= (int)$u['periodicidade_meses'] ?> meses
    <?= $u['endereco'] ? '<br>' . e($u['endereco']) : '' ?></p></div>
  <div class="acoes nao-imprimir">
    <?php if (pode_editar()): ?><a class="btn" href="visita_form.php?unidade=<?= $id ?>">Registrar visita</a>
    <a class="btn sec" href="unidade_form.php?id=<?= $id ?>">Editar cadastro</a><?php endif; ?>
  </div>
</div>
<?php if ($u['observacao']): ?><p class="obs"><?= e($u['observacao']) ?></p><?php endif; ?>

<div class="grade g4">
  <div class="cartao kpi"><div class="rot">Última nota</div><div class="valor"><?= $ag['visita_id'] ? selo($ag['nota_final']) : '–' ?></div><div class="det"><?= data_br($ag['ultima_visita']) ?></div></div>
  <div class="cartao kpi"><div class="rot">Próxima visita prevista</div><div class="valor" style="font-size:20px"><?= data_br($ag['proxima_prevista']) ?></div><div class="det"><span class="tag <?= e($ag['situacao_agenda']) ?>"><?= rotulo($ag['situacao_agenda']) ?></span></div></div>
  <div class="cartao kpi"><div class="rot">Pendências abertas</div><div class="valor"><?= count(array_filter($pend, fn($p) => in_array($p['situacao'], ['aberta', 'em_andamento']))) ?></div></div>
</div>

<div class="cartao"><h3 style="margin-top:0">Evolução da nota final</h3><?= grafico($validas) ?></div>

<h2>Histórico de visitas</h2>
<div class="tabela"><table>
  <tr><th>Data</th><th>Ciclo</th><th>Infra. física</th><th>Infra. humana</th><th>Plano Municipal</th><th>Percepção usuário</th><th class="num">Nota final</th><th>Situação</th></tr>
  <?php foreach ($hist as $h): ?>
  <tr><td><a href="visita.php?id=<?= $h['visita_id'] ?>"><?= data_br($h['data_visita']) ?></a></td><td><?= e($h['ciclo']) ?></td>
    <td><?= barra($h['perc_infra_fisica']) ?></td><td><?= barra($h['perc_infra_humana']) ?></td><td><?= barra($h['perc_plano_municipal']) ?></td><td><?= barra($h['perc_percepcao_usuario']) ?></td>
    <td class="num"><?= selo($h['nota_final']) ?></td><td><span class="tag <?= e($h['situacao']) ?>"><?= rotulo($h['situacao']) ?></span></td></tr>
  <?php endforeach; ?>
  <?php if (!$hist): ?><tr><td colspan="8">Nenhuma visita registrada.</td></tr><?php endif; ?>
</table></div>

<h2>Pendências e encaminhamentos</h2>
<div class="tabela"><table>
  <tr><th>Descrição</th><th>Dimensão</th><th>Responsável</th><th>Prazo</th><th>Situação</th></tr>
  <?php foreach ($pend as $p): ?><tr><td><a href="pendencias.php?editar=<?= $p['id'] ?>"><?= e($p['descricao']) ?></a></td><td class="mini"><?= e($p['dim']) ?></td><td><?= e($p['responsavel']) ?></td><td><?= data_br($p['prazo']) ?></td><td><span class="tag <?= e($p['situacao']) ?>"><?= rotulo($p['situacao']) ?></span></td></tr><?php endforeach; ?>
  <?php if (!$pend): ?><tr><td colspan="5">Nenhuma pendência registrada.</td></tr><?php endif; ?>
</table></div>
<?php require __DIR__ . '/inc/rodape.php';
