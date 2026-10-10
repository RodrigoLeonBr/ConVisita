<div class="cab">
  <div><h1 class="h3"><?= e($u['nome']) ?></h1>
    <p class="sub"><?= e($u['tipo']) ?><?= $u['gestao'] ? ' · gestão ' . e($u['gestao']) : '' ?> · <?= rotulo($u['situacao']) ?> · visita a cada <?= (int)$u['periodicidade_meses'] ?> meses
    <?= $u['endereco'] ? '<br>' . e($u['endereco']) : '' ?></p></div>
  <div class="acoes d-print-none">
    <?php if (pode_editar()): ?><a class="btn btn-primary btn-sm" href="<?= BASE ?>/visita/nova?unidade=<?= $id ?>">Registrar visita</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= BASE ?>/unidade/<?= $id ?>/editar">Editar cadastro</a><?php endif; ?>
  </div>
</div>
<?php if ($u['observacao']): ?><p class="obs"><?= e($u['observacao']) ?></p><?php endif; ?>

<div class="grade g4">
  <div class="cartao kpi"><div class="rot">Última nota</div><div class="valor"><?= $ag['visita_id'] ? selo($ag['nota_final']) : '–' ?></div><div class="det"><?= data_br($ag['ultima_visita']) ?></div></div>
  <div class="cartao kpi"><div class="rot">Próxima visita prevista</div><div class="valor" style="font-size:20px"><?= data_br($ag['proxima_prevista']) ?></div><div class="det"><span class="tag <?= e($ag['situacao_agenda']) ?>"><?= rotulo($ag['situacao_agenda']) ?></span></div></div>
  <div class="cartao kpi"><div class="rot">Pendências abertas</div><div class="valor"><?= count(array_filter($pend, fn($p) => in_array($p['situacao'], ['aberta', 'em_andamento']))) ?></div></div>
</div>

<div class="cartao"><h3 style="margin-top:0">Evolução da nota final</h3><?= $grafico ?></div>

<h2>Histórico de visitas</h2>
<div class="table-responsive"><table class="table table-striped align-middle">
  <thead><tr><th scope="col">Data</th><th scope="col">Ciclo</th><th scope="col">Infra. física</th><th scope="col">Infra. humana</th><th scope="col" class="d-none d-lg-table-cell">Plano Municipal</th><th scope="col" class="d-none d-lg-table-cell">Percepção usuário</th><th scope="col" class="text-end">Nota final</th><th scope="col">Situação</th></tr></thead>
  <tbody>
  <?php foreach ($hist as $h): ?>
  <tr><td><a href="<?= BASE ?>/visita/<?= $h['visita_id'] ?>"><?= data_br($h['data_visita']) ?></a></td><td><?= e($h['ciclo']) ?></td>
    <td><?= barra($h['perc_infra_fisica']) ?></td><td><?= barra($h['perc_infra_humana']) ?></td><td class="d-none d-lg-table-cell"><?= barra($h['perc_plano_municipal']) ?></td><td class="d-none d-lg-table-cell"><?= barra($h['perc_percepcao_usuario']) ?></td>
    <td class="text-end"><?= selo($h['nota_final']) ?></td><td><span class="badge text-bg-secondary"><?= rotulo($h['situacao']) ?></span></td></tr>
  <?php endforeach; ?>
  <?php if (!$hist): ?><tr><td colspan="8">Nenhuma visita registrada.</td></tr><?php endif; ?>
  </tbody>
</table></div>

<h2>Pendências e encaminhamentos</h2>
<div class="table-responsive"><table class="table table-striped align-middle">
  <thead><tr><th scope="col">Descrição</th><th scope="col" class="d-none d-md-table-cell">Dimensão</th><th scope="col">Responsável</th><th scope="col">Prazo</th><th scope="col">Situação</th></tr></thead>
  <tbody>
  <?php foreach ($pend as $p): ?><tr><td><a href="<?= BASE ?>/pendencia/<?= $p['id'] ?>/editar"><?= e($p['descricao']) ?></a></td><td class="d-none d-md-table-cell small"><?= e($p['dim']) ?></td><td><?= e($p['responsavel']) ?></td><td><?= data_br($p['prazo']) ?></td><td><span class="badge text-bg-secondary"><?= rotulo($p['situacao']) ?></span></td></tr><?php endforeach; ?>
  <?php if (!$pend): ?><tr><td colspan="5">Nenhuma pendência registrada.</td></tr><?php endif; ?>
  </tbody>
</table></div>
