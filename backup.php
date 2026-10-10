<?php
require __DIR__ . '/inc/base.php';
require __DIR__ . '/inc/backup.php';
exige_login();
exige_admin();

$MODOS = ['mesclar' => 'Mesclar (trazer novidades de outro computador)', 'restaurar' => 'Restaurar (substituir todos os dados deste computador)'];

// ------------------------------------------------------------ downloads
if (get('baixar')) {
    $dados = gera_backup();
    $nome = salva_backup($dados, 'manual');
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nome . '"');
    readfile(pasta_backups() . '/' . $nome);
    exit;
}
if ($arq = get('download')) {
    $p = caminho_backup((string)$arq);
    if (!$p) { http_response_code(404); exit('Arquivo não encontrado.'); }
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . basename($p) . '"');
    readfile($p);
    exit;
}

// ------------------------------------------------------------ acoes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    confere_csrf();
    $acao = post('acao');
    try {
        if ($acao === 'nome_computador') {
            $n = trim((string)post('nome'));
            if ($n !== '') { grava_parametro('nome_computador', mb_substr($n, 0, 60)); aviso('Nome do computador gravado.'); }
            redireciona('backup.php');
        }
        if ($acao === 'enviar') {
            $f = $_FILES['arquivo'] ?? null;
            if (!$f || $f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Selecione um arquivo de backup (.json) para enviar.');
            $tmp = $f['tmp_name'];
            $b = le_backup($tmp);
            $nome = sprintf('convisita_%s_recebido_%s.json', date('Ymd_His'), nome_arquivo_seguro($b['computador'] ?? 'outro'));
            move_uploaded_file($tmp, pasta_backups() . '/' . $nome);
            $modo = isset($MODOS[post('modo')]) ? post('modo') : 'mesclar';
            redireciona('backup.php?analisar=' . urlencode($nome) . '&modo=' . $modo);
        }
        if ($acao === 'executar') {
            $p = caminho_backup((string)post('arquivo'));
            if (!$p) throw new RuntimeException('Arquivo de backup não encontrado.');
            $b = le_backup($p);
            $modo = post('modo');
            if ($modo === 'restaurar' && post('confirmo') !== '1') throw new RuntimeException('Marque a confirmação para restaurar o backup.');
            $seg = salva_backup(gera_backup(), 'antes-de-' . $modo);
            if ($modo === 'restaurar') {
                $c = restaura_backup($b, post('usuarios') === '1');
                aviso(sprintf('Backup restaurado: %d visitas, %d unidades e %d pendências. Os dados anteriores foram guardados em %s.',
                    $c['visita'], $c['unidade'], $c['pendencia'] ?? 0, $seg));
            } else {
                $r = mescla_backup($b, true);
                aviso(sprintf('Mesclagem concluída: %d visita(s) nova(s), %d atualizada(s), %d pendência(s) nova(s), %d atualizada(s) e %d outro(s) cadastro(s) novo(s). Cópia de segurança anterior: %s.',
                    $r['visitas_novas'], $r['visitas_atualizadas'], $r['pendencias_novas'], $r['pendencias_atualizadas'],
                    count($r['novos']) - $r['visitas_novas'] - $r['pendencias_novas'], $seg));
                foreach (array_slice($r['avisos'], 0, 10) as $a) aviso($a, 'erro');
            }
            unset($_SESSION['esquema_v2']);
            redireciona('backup.php');
        }
        if ($acao === 'excluir') {
            $p = caminho_backup((string)post('arquivo'));
            if ($p) { unlink($p); aviso('Arquivo excluído.'); }
            redireciona('backup.php');
        }
    } catch (Throwable $e) {
        aviso('Não foi possível concluir: ' . $e->getMessage(), 'erro');
        redireciona('backup.php');
    }
}

// ------------------------------------------------------------ analise (pre-visualizacao)
$analise = null;
if ($arq = get('analisar')) {
    $p = caminho_backup((string)$arq);
    $modo = isset($MODOS[get('modo')]) ? get('modo') : 'mesclar';
    try {
        if (!$p) throw new RuntimeException('Arquivo não encontrado.');
        $b = le_backup($p);
        $analise = ['arquivo' => basename($p), 'modo' => $modo, 'b' => $b, 'plano' => $modo === 'mesclar' ? mescla_backup($b, false) : null];
    } catch (Throwable $e) {
        aviso('Não foi possível ler o arquivo: ' . $e->getMessage(), 'erro');
    }
}

$titulo = 'Backup e sincronização'; $menu = 'cadastros';
require __DIR__ . '/inc/topo.php';
$cont = contagem_atual();
?>
<div class="cab">
  <div><h1>Backup e sincronização</h1>
  <p class="sub">Guarde cópias de segurança e troque informações entre computadores que usam o CONVISITA.</p></div>
  <a class="btn sec" href="instrumento.php">Voltar a Instrumento e cadastros</a>
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
  <form method="post" class="acoes" style="margin-top:12px">
    <?= csrf() ?><input type="hidden" name="acao" value="executar"><input type="hidden" name="arquivo" value="<?= e($analise['arquivo']) ?>"><input type="hidden" name="modo" value="<?= e($analise['modo']) ?>">
    <?php if ($analise['modo'] === 'restaurar'): ?>
      <label style="color:inherit;margin:0"><input type="checkbox" name="usuarios" value="1"> Substituir também os usuários e senhas</label>
      <label style="color:inherit;margin:0"><input type="checkbox" name="confirmo" value="1" required> Entendo que os dados atuais serão substituídos</label>
      <button class="btn perigo">Restaurar backup</button>
    <?php elseif ($pl['novos'] || $pl['atualizados']): ?>
      <button class="btn">Confirmar mesclagem</button>
    <?php endif; ?>
    <a class="btn sec" href="backup.php">Cancelar</a>
  </form>
</div>
<?php endif; ?>

<div class="grade g2">
  <div class="cartao">
    <h3 style="margin-top:0">1. Este computador</h3>
    <form method="post" class="filtros"><?= csrf() ?><input type="hidden" name="acao" value="nome_computador">
      <div style="min-width:240px"><label>Nome deste computador</label><input type="text" name="nome" value="<?= e(nome_computador()) ?>" maxlength="60"></div>
      <div><button class="btn peq sec">Gravar nome</button></div>
    </form>
    <p class="mini">O nome identifica de onde veio cada backup (por exemplo: "Secretaria COMSAÚDE" ou "Notebook da comissão").</p>
    <table><?php foreach ($cont as $k => $v): ?><tr><td><?= e($k) ?></td><td class="num"><?= $v ?></td></tr><?php endforeach; ?></table>
  </div>
  <div class="cartao">
    <h3 style="margin-top:0">2. Gerar backup</h3>
    <p>Baixa um arquivo com todos os dados deste computador: unidades, visitas, notas, comentários, pendências, conselheiros, usuários e configurações do instrumento. Uma cópia também fica guardada na lista abaixo.</p>
    <a class="btn" href="backup.php?baixar=1">Baixar backup agora</a>
    <h3>3. Receber um backup</h3>
    <form method="post" enctype="multipart/form-data"><?= csrf() ?><input type="hidden" name="acao" value="enviar">
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
<div class="tabela"><table>
  <tr><th>Arquivo</th><th>Gerado em</th><th>Computador de origem</th><th class="num">Visitas</th><th class="num">Tamanho</th><th></th></tr>
  <?php foreach (lista_backups() as $l): ?>
  <tr>
    <td class="mini"><?= e($l['arquivo']) ?></td>
    <td><?= $l['gerado_em'] ? e(date('d/m/Y H:i', strtotime($l['gerado_em']))) : '–' ?></td>
    <td><?= e($l['computador'] ?: '–') ?></td>
    <td class="num"><?= $l['visitas'] ?? '–' ?></td>
    <td class="num"><?= number_format($l['tamanho'] / 1024, 0, ',', '.') ?> KB</td>
    <td class="acoes">
      <a class="btn peq sec" href="backup.php?download=<?= urlencode($l['arquivo']) ?>">Baixar</a>
      <a class="btn peq sec" href="backup.php?analisar=<?= urlencode($l['arquivo']) ?>&modo=mesclar">Mesclar</a>
      <a class="btn peq sec" href="backup.php?analisar=<?= urlencode($l['arquivo']) ?>&modo=restaurar">Restaurar</a>
      <form method="post" style="display:inline"><?= csrf() ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="arquivo" value="<?= e($l['arquivo']) ?>">
        <button class="btn peq perigo" data-confirma="Excluir este arquivo de backup?">Excluir</button></form>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!lista_backups()): ?><tr><td colspan="6">Nenhum backup guardado ainda.</td></tr><?php endif; ?>
</table></div>
<?php require __DIR__ . '/inc/rodape.php';
