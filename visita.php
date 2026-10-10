<?php
require __DIR__ . '/inc/base.php';
exige_login();

$id = (int)get('id');
$r = linha('SELECT r.*, v.relato, v.origem, v.observacao_importacao, v.unidade_id
              FROM vw_resultado_visita r JOIN visita v ON v.id = r.visita_id WHERE r.visita_id = ?', [$id]);
if (!$r) { http_response_code(404); exit('Visita não encontrada.'); }
$dims = consulta('SELECT dv.*, cd.texto AS comentario FROM vw_dimensao_visita dv
                   LEFT JOIN comentario_dimensao cd ON cd.visita_id = dv.visita_id AND cd.dimensao_id = dv.dimensao_id
                  WHERE dv.visita_id = ? ORDER BY dv.dimensao_id', [$id]);
$itens = consulta('SELECT i.*, a.pontuacao, a.nao_se_aplica, a.observacao FROM item_indicador i
                    LEFT JOIN avaliacao_item a ON a.item_id = i.id AND a.visita_id = ?
                   WHERE i.ativo = 1 ORDER BY i.ordem', [$id]);
$porDim = [];
foreach ($itens as $i) $porDim[$i['dimensao_id']][] = $i;
$conselheiros = consulta('SELECT c.nome FROM visita_conselheiro vc JOIN conselheiro c ON c.id = vc.conselheiro_id WHERE vc.visita_id = ? ORDER BY c.nome', [$id]);
$pend = consulta('SELECT * FROM pendencia WHERE visita_id = ? ORDER BY id', [$id]);
$ant = linha("SELECT r.* FROM vw_resultado_visita r WHERE r.unidade_id = ? AND r.data_visita < ? AND r.situacao IN ('concluida','incompleta')
               ORDER BY r.data_visita DESC LIMIT 1", [$r['unidade_id'], $r['data_visita']]);

$titulo = 'Visita ' . $r['unidade']; $menu = 'visitas';
require __DIR__ . '/inc/topo.php';
?>
<div class="cab">
  <div>
    <h1><?= e($r['unidade']) ?></h1>
    <p class="sub">Visita <?= rotulo($r['tipo']) ?> de <?= data_br($r['data_visita']) ?><?= $r['data_aproximada'] ? ' (data aproximada)' : '' ?> · ciclo <?= e($r['ciclo']) ?> ·
      <span class="tag <?= e($r['situacao']) ?>"><?= rotulo($r['situacao']) ?></span>
      <?php if ($conselheiros): ?><br>Conselheiros: <?= e(implode(', ', array_column($conselheiros, 'nome'))) ?><?php endif; ?></p>
  </div>
  <div class="acoes nao-imprimir">
    <?php if (pode_editar()): ?><a class="btn" href="visita_form.php?id=<?= $id ?>">Editar</a><?php endif; ?>
    <a class="btn sec" href="#" onclick="window.print();return false">Imprimir</a>
  </div>
</div>

<div class="grade g4">
  <div class="cartao kpi"><div class="rot">Nota final</div><div class="valor"><?= selo($r['nota_final']) ?></div>
    <div class="det"><?= (int)$r['pontos_totais'] ?> de <?= (int)$r['pontos_possiveis'] ?> pontos possíveis · <?= (int)$r['dimensoes_avaliadas'] ?> dimensões avaliadas</div></div>
  <div class="cartao kpi"><div class="rot">Visita anterior</div>
    <div class="valor"><?= $ant ? selo($ant['nota_final']) : '–' ?></div>
    <div class="det"><?php if ($ant): $dif = (float)$r['nota_final'] - (float)$ant['nota_final']; ?>
      <?= data_br($ant['data_visita']) ?> · variação de <?= ($dif >= 0 ? '+' : '') . number_format($dif * 100, 1, ',', '') ?> p.p.
      <?php else: ?>Sem visita anterior registrada<?php endif; ?></div></div>
  <div class="cartao kpi"><div class="rot">Cálculo pelo método da planilha</div><div class="valor"><?= selo($r['nota_metodo_planilha']) ?></div>
    <div class="det">Soma de peso × percentual, sem redistribuir o peso de dimensões não avaliadas</div></div>
</div>

<?php if ($r['observacao_importacao']): ?><p class="obs"><b>Nota da importação:</b> <?= e($r['observacao_importacao']) ?></p><?php endif; ?>

<?php foreach ($dims as $d): ?>
<div class="cartao">
  <div class="cab" style="margin:0">
    <h3 style="margin:0"><?= e($d['dimensao_codigo'] . ' · ' . $d['dimensao']) ?> <span class="mini">(peso <?= pct($d['peso'], 0) ?>)</span></h3>
    <div><?= (int)$d['pontos'] ?> / <?= (int)$d['pontos_possiveis'] ?> pts &nbsp; <?= selo($d['percentual']) ?></div>
  </div>
  <table>
    <tr><th style="width:60px">Item</th><th>Indicador e meta esperada</th><th class="num" style="width:110px">Pontuação</th><th>Observação</th></tr>
    <?php foreach ($porDim[$d['dimensao_id']] ?? [] as $i): ?>
    <tr><td><b><?= e($i['codigo']) ?></b></td><td><?= e($i['nome']) ?><div class="mini"><?= e($i['meta']) ?></div></td>
      <td class="num"><?php if ($i['nao_se_aplica']): ?><span class="tag">Não se aplica</span>
        <?php elseif ($i['pontuacao'] === null): ?><span class="tag">Não avaliado</span>
        <?php else: ?><span class="selo <?= farol($i['pontuacao'] / $i['pontuacao_max']) ?>"><?= (int)$i['pontuacao'] ?> / <?= (int)$i['pontuacao_max'] ?></span><?php endif; ?></td>
      <td class="mini"><?= e($i['observacao']) ?></td></tr>
    <?php endforeach; ?>
  </table>
  <?php if ($d['comentario']): ?><p><b>Comentários e oportunidades de melhoria:</b> <?= nl2br(e($d['comentario'])) ?></p><?php endif; ?>
</div>
<?php endforeach; ?>

<?php if ($r['relato']): ?><div class="cartao"><h3 style="margin-top:0">Relato da visita</h3><?= nl2br(e($r['relato'])) ?></div><?php endif; ?>

<div class="cartao">
  <div class="cab" style="margin:0"><h3 style="margin:0">Pendências e encaminhamentos</h3>
    <?php if (pode_editar()): ?><a class="btn peq nao-imprimir" href="pendencias.php?nova=1&visita=<?= $id ?>">Nova pendência</a><?php endif; ?></div>
  <?php if ($pend): ?><table><tr><th>Descrição</th><th>Responsável</th><th>Prazo</th><th>Situação</th></tr>
    <?php foreach ($pend as $p): ?><tr><td><?= e($p['descricao']) ?></td><td><?= e($p['responsavel']) ?></td><td><?= data_br($p['prazo']) ?></td><td><span class="tag <?= e($p['situacao']) ?>"><?= rotulo($p['situacao']) ?></span></td></tr><?php endforeach; ?>
  </table><?php else: ?><p class="mini">Nenhuma pendência registrada para esta visita.</p><?php endif; ?>
</div>
<p class="mini">Origem do registro: <?= e($r['origem'] ?: 'sistema') ?></p>
<?php require __DIR__ . '/inc/rodape.php';
