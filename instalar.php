<?php
// Instalador: cria o banco "convisita" e carrega estrutura, referencias e historico da planilha.
declare(strict_types=1);
require __DIR__ . '/inc/base.php';

$msgs = [];
$ok = false;
try {
    $pdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', cfg('db_host'), cfg('db_porta')),
        cfg('db_usuario'), cfg('db_senha'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $nomeBanco = (string)cfg('db_nome');
    // No XAMPP o banco e criado aqui; em hospedagem compartilhada (cPanel) ele ja deve ter sido criado no painel
    try { $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $nomeBanco) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'); }
    catch (Throwable $ignorar) { }
    $pdo->exec('USE `' . str_replace('`', '', $nomeBanco) . '`');
    $existe = (bool)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = "
        . $pdo->quote($nomeBanco) . " AND table_name = 'unidade'")->fetchColumn();
    if ($existe && !cfg('permitir_reinstalar')) {
        $msgs[] = 'O banco já está instalado. Para reinstalar (apagando todos os dados), defina permitir_reinstalar = true em config.php.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach (['01_estrutura.sql', '02_referencia.sql', '03_carga_historica.sql'] as $arq) {
            $sql = file_get_contents(__DIR__ . '/sql/' . $arq);
            $sql = preg_replace('/^\s*--.*$/m', '', $sql);
            // usa sempre o banco configurado em config.php (em cPanel o nome tem prefixo da conta)
            $sql = preg_replace('/^\s*(CREATE DATABASE|USE)\b[^;]*;/mi', '', $sql);
            $n = 0;
            foreach (preg_split('/;\s*\n/', $sql) as $cmd) {
                if (trim($cmd) === '') continue;
                $pdo->exec($cmd);
                $n++;
            }
            $msgs[] = "$arq: $n comandos executados.";
        }
        $ok = true;
    }
} catch (Throwable $ex) {
    $msgs[] = 'Erro: ' . $ex->getMessage();
}
$titulo = 'Instalação';
require __DIR__ . '/inc/topo.php';
?>
<div class="cartao" style="max-width:720px;margin:auto">
  <h1>Instalação do banco de dados</h1>
  <p class="sub">Cria o banco <b><?= e(cfg('db_nome')) ?></b> no MySQL/MariaDB (XAMPP ou hospedagem) e carrega o histórico extraído da planilha de pontuação (ciclos 2023 e 2026).</p>
  <?php foreach ($msgs as $m): ?><div class="aviso <?= $ok ? 'ok' : 'info' ?>"><?= e($m) ?></div><?php endforeach; ?>
  <?php if ($ok): ?>
    <p>Instalação concluída. Acesse com usuário <b>admin</b> e senha <b>comsaude</b> e troque a senha em <i>Instrumento e cadastros › Usuários</i>.</p>
    <a class="btn" href="login.php">Ir para o sistema</a>
  <?php elseif (empty($existe) || cfg('permitir_reinstalar')): ?>
    <form method="post"><button class="btn" <?= !empty($existe) ? 'data-confirma="Todos os dados atuais serão apagados. Continuar?"' : '' ?>>Instalar agora</button></form>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/inc/rodape.php';
