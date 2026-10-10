<h1 class="h3"><?= $id ? 'Editar visita' : 'Registrar visita' ?></h1>
<p class="sub">Pontue cada item: <b>0</b> não atende, <b>1</b> atende parcialmente, <b>2</b> atende plenamente. Use <b>N/A</b> quando o item não se aplica à unidade (o peso é redistribuído) e deixe em branco o que não foi avaliado.</p>

<form method="post" id="form-visita" action="<?= BASE ?>/visita/salvar" data-pesos='<?= e(json_encode($pesos)) ?>' data-faixas='<?= e(json_encode($fx)) ?>'>
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
      <?php endforeach; else: ?><span class="mini">Nenhum conselheiro cadastrado (<a href="<?= BASE ?>/conselheiros">cadastrar</a>).</span><?php endif; ?>
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
      <a class="btn sec" href="<?= $id ? BASE . '/visita/' . $id : BASE . '/visitas' ?>">Cancelar</a>
    </div>
  </div>
</form>
<?php if ($id && e_admin()): ?>
<form method="post" action="<?= BASE ?>/visita/salvar" class="d-print-none"><?= csrf() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="acao" value="excluir">
  <button class="btn perigo peq" data-confirma="Excluir definitivamente esta visita e suas notas?">Excluir visita</button></form>
<?php endif; ?>
