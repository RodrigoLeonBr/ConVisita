<div class="cab">
  <div><h1 class="h3">Backup e sincronização</h1>
  <p class="sub">Guarde cópias de segurança e troque informações entre computadores que usam o CONVISITA.</p></div>
  <a class="btn sec" href="<?= BASE ?>/instrumento">Voltar a Instrumento e cadastros</a>
</div>

<?php if ($analise): $b = $analise['b']; $pl = $analise['plano']; ?>
<div class="cartao" style="border:2px solid var(--primaria)">
  <h2 style="margin-top:0">Conferência antes de <?= $analise['modo'] === 'mesclar' ? 'mesclar' : 'restaurar' ?></h2>
  <table>
    <tr><th>Arquivo</th><td><?= e($analise['arquivo']) ?></td><th>Gerado em</th><td><?= e(date('d/m/Y H:i', strtotime($b['gerado_em']))) ?></td></tr>
    <tr><th>Computador de origem</th><td><?= e($b['computador'] ?? '–') ?></td><th>Gerado por</th><td><?= e($b['usuario'] ?? '–') ?></td></tr>
    <tr><th>Visitas no arquivo</th><td><?= count($b['tabelas']['visita']) ?></td><th>Pendências no arquivo</th><td><?= count($b['tabelas']['pendencia'] ?? []) ?></td></tr>
  </table>
  <?php if ($analise['modo'] === 'mesclar'): ?>
    <h3>O que será trazido para este computador</h3>
    <div class="grade g4">
      <div class="kpi"><div class="rot">Visitas novas</div><div class="valor"><?= $pl['visitas_novas'] ?></div></div>
      <div class="kpi"><div class="rot">Visitas atualizadas</div><div class="valor"><?= $pl['visitas_atualizadas'] ?></div></div>
      <div class="kpi"><div class="rot">Pendências novas / atualizadas</div><div class="valor"><?= $pl['pendencias_novas'] ?> / <?= $pl['pendencias_atualizadas'] ?></div></div>
      <div class="kpi"><div class="rot">Sem mudança (já iguais ou mais recentes aqui)</div><div class="valor"><?= $pl['ignorados'] ?></div></div>
    </div>
    <?php if ($pl['novos'] || $pl['atualizados']): ?>
      <table style="margin-top:10px"><tr><th>Registro</th><th>Ação</th></tr>
      <?php foreach ($pl['novos'] as $n): ?><tr><td><?= e($n) ?></td><td><span class="tag em_dia">incluir</span></td></tr><?php endforeach; ?>
      <?php foreach ($pl['atualizados'] as $n): ?><tr><td><?= e($n) ?></td><td><span class="tag em_andamento">atualizar</span></td></tr><?php endforeach; ?>
      </table>
    <?php else: ?><p class="aviso info">Este computador já tem todas as informações do arquivo. Nada será alterado.</p><?php endif; ?>
    <p class="mini">Nada é apagado na mesclagem. Visitas e pendências excluídas no outro computador continuam existindo aqui.</p>
  <?php else: ?>
    <div class="aviso erro"><b>Atenção:</b> restaurar apaga todas as visitas, unidades, pendências e cadastros deste computador
      (hoje são <?= (int)$cont['Visitas'] ?> visita(s) e <?= (int)$cont['Pendências'] ?> pendência(s)) e grava no lugar o conteúdo do arquivo.
      Antes disso, o sistema guarda automaticamente uma cópia dos dados atuais.</div>
  <?php endif; ?>
  <form method="post" class="acoes" style="margin-top:12px" action="<?= BASE ?>/backup/salvar">
    <?= csrf() ?><input type="hidden" name="acao" value="executar"><input type="hidden" name="arquivo" value="<?= e($analise['arquivo']) ?>"><input type="hidden" name="modo" value="<?= e($analise['modo']) ?>">
    <?php if ($analise['modo'] === 'restaurar'): ?>
      <label style="color:inherit;margin:0"><input type="checkbox" name="usuarios" value="1"> Substituir também os usuários e senhas</label>
      <label style="color:inherit;margin:0"><input type="checkbox" name="confirmo" value="1" required> Entendo que os dados atuais serão substituídos</label>
      <button class="btn perigo">Restaurar backup</button>
    <?php elseif ($pl['novos'] || $pl['atualizados']): ?>
      <button class="btn">Confirmar mesclagem</button>
    <?php endif; ?>
    <a class="btn sec" href="<?= BASE ?>/backup">Cancelar</a>
  </form>
</div>
<?php endif; ?>

<div class="grade g2">
  <div class="cartao">
    <h3 style="margin-top:0">1. Este computador</h3>
    <form method="post" class="filtros" action="<?= BASE ?>/backup/salvar"><?= csrf() ?><input type="hidden" name="acao" value="nome_computador">
      <div style="min-width:240px"><label>Nome deste computador</label><input type="text" name="nome" value="<?= e(nome_computador()) ?>" maxlength="60"></div>
      <div><button class="btn peq sec">Gravar nome</button></div>
    </form>
    <p class="mini">O nome identifica de onde veio cada backup (por exemplo: "Secretaria COMSAÚDE" ou "Notebook da comissão").</p>
    <table><?php foreach ($cont as $k => $v): ?><tr><td><?= e($k) ?></td><td class="num"><?= $v ?></td></tr><?php endforeach; ?></table>
  </div>
  <div class="cartao">
    <h3 style="margin-top:0">2. Gerar backup</h3>
    <p>Baixa um arquivo com todos os dados deste computador: unidades, visitas, notas, comentários, pendências, conselheiros, usuários e configurações do instrumento. Uma cópia também fica guardada na lista abaixo.</p>
    <a class="btn" href="<?= BASE ?>/backup?baixar=1">Baixar backup agora</a>
    <h3>3. Receber um backup</h3>
    <form method="post" enctype="multipart/form-data" action="<?= BASE ?>/backup/salvar"><?= csrf() ?><input type="hidden" name="acao" value="enviar">
      <label>Arquivo de backup (.json)</label><input type="file" name="arquivo" accept=".json,application/json" required>
      <div style="margin:10px 0">
        <?php foreach ($MODOS as $k => $r): ?>
          <label style="color:inherit;display:block;margin-bottom:4px"><input type="radio" name="modo" value="<?= $k ?>" <?= $k === 'mesclar' ? 'checked' : '' ?>> <?= e($r) ?></label>
        <?php endforeach; ?>
      </div>
      <button class="btn">Analisar arquivo</button> <span class="mini">Nada é gravado antes da sua confirmação.</span>
    </form>
  </div>
</div>

<h2>4. Backups guardados neste computador</h2>
<p class="sub">Inclui os backups gerados aqui, os arquivos recebidos de outros computadores e as cópias automáticas feitas antes de cada mesclagem ou restauração. Ficam na pasta <code>convisita/backups</code>.</p>
<div class="table-responsive"><table class="table table-striped align-middle">
  <thead><tr><th scope="col">Arquivo</th><th scope="col">Gerado em</th><th scope="col">Computador de origem</th><th scope="col" class="text-end">Visitas</th><th scope="col" class="text-end">Tamanho</th><th scope="col"></th></tr></thead>
  <tbody>
  <?php foreach (lista_backups() as $l): ?>
  <tr>
    <td class="small"><?= e($l['arquivo']) ?></td>
    <td><?= $l['gerado_em'] ? e(date('d/m/Y H:i', strtotime($l['gerado_em']))) : '–' ?></td>
    <td><?= e($l['computador'] ?: '–') ?></td>
    <td class="text-end"><?= $l['visitas'] ?? '–' ?></td>
    <td class="text-end"><?= number_format($l['tamanho'] / 1024, 0, ',', '.') ?> KB</td>
    <td class="acoes">
      <a class="btn peq sec" href="<?= BASE ?>/backup?download=<?= urlencode($l['arquivo']) ?>">Baixar</a>
      <a class="btn peq sec" href="<?= BASE ?>/backup?analisar=<?= urlencode($l['arquivo']) ?>&modo=mesclar">Mesclar</a>
      <a class="btn peq sec" href="<?= BASE ?>/backup?analisar=<?= urlencode($l['arquivo']) ?>&modo=restaurar">Restaurar</a>
      <form method="post" style="display:inline" action="<?= BASE ?>/backup/salvar"><?= csrf() ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="arquivo" value="<?= e($l['arquivo']) ?>">
        <button class="btn peq perigo" data-confirma="Excluir este arquivo de backup?">Excluir</button></form>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!lista_backups()): ?><tr><td colspan="6">Nenhum backup guardado ainda.</td></tr><?php endif; ?>
  </tbody>
</table></div>
