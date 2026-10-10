<?php
// Instalador: cria o banco "convisita" e carrega estrutura, referencias e historico da planilha.
// O DDL roda com credencial administrativa informada no formulario (o usuario de runtime em
// config.php tem so DML). Exigir a senha admin do banco e o controle de acesso da reinstalacao.
declare(strict_types=1);
require __DIR__ . '/inc/base.php';

$msgs = [];
$ok = false;
$senhaGerada = null;

// Estado atual lido com o usuario de runtime (so leitura); pode falhar se o banco ainda nao existe.
$instalado = false;
try {
    $pdoApp = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', cfg('db_host'), cfg('db_porta'), cfg('db_nome')),
        cfg('db_usuario'), cfg('db_senha'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $st = $pdoApp->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = 'unidade'");
    $st->execute([(string)cfg('db_nome')]);
    $instalado = (int)$st->fetchColumn() > 0;
} catch (Throwable $ignorar) { /* banco/credencial de runtime ainda nao prontos */ }

try {
    if ($instalado && !cfg('permitir_reinstalar')) {
        $msgs[] = 'O banco já está instalado. Para reinstalar (apagando todos os dados), defina permitir_reinstalar = true em config.php.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Conexao administrativa (DDL). Credencial informada no formulario, nunca o usuario de runtime.
        $admUser = (string)(post('db_admin_user') ?: 'root');
        $admPass = (string)post('db_admin_pass');
        $nomeBanco = str_replace('`', '', (string)cfg('db_nome'));
        $pdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', cfg('db_host'), cfg('db_porta')),
            $admUser, $admPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        // No XAMPP o banco e criado aqui; em cPanel ele ja deve existir (o CREATE falha e e ignorado)
        try { $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $nomeBanco . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'); }
        catch (Throwable $ignorar) { }
        $pdo->exec('USE `' . $nomeBanco . '`');
        foreach (['01_estrutura.sql', '02_referencia.sql', '03_carga_historica.sql'] as $arq) {
            $caminhoSql = __DIR__ . '/sql/' . $arq;
            if (!is_file($caminhoSql)) { $msgs[] = "$arq: ausente, ignorado."; continue; }
            $sql = file_get_contents($caminhoSql);
            $sql = preg_replace('/^\s*--.*$/m', '', $sql);
            $sql = preg_replace('/^\s*(CREATE DATABASE|USE)\b[^;]*;/mi', '', $sql);
            $n = 0;
            foreach (preg_split('/;\s*\n/', $sql) as $cmd) {
                if (trim($cmd) === '') continue;
                $pdo->exec($cmd);
                $n++;
            }
            $msgs[] = "$arq: $n comandos executados.";
        }
        // Senha inicial do admin: aleatoria, exibida uma unica vez (nenhuma senha padrao versionada).
        $senhaGerada = bin2hex(random_bytes(6));
        $up = $pdo->prepare("UPDATE usuario SET senha_hash = ? WHERE login = 'admin'");
        $up->execute([password_hash($senhaGerada, PASSWORD_DEFAULT)]);
        $ok = true;
    }
} catch (Throwable $ex) {
    error_log('instalar.php: ' . $ex->getMessage());
    $msgs[] = e_local() ? 'Erro: ' . $ex->getMessage() : 'Erro ao instalar. Verifique a credencial administrativa do banco e tente novamente.';
}
$titulo = 'Instalação';
require __DIR__ . '/inc/topo.php';
?>
<div class="cartao" style="max-width:720px;margin:auto">
  <h1>Instalação do banco de dados</h1>
  <p class="sub">Cria o banco <b><?= e(cfg('db_nome')) ?></b> e carrega o histórico. O DDL exige a senha de um usuário administrativo do MySQL (ex.: <code>root</code>); o sistema no dia a dia usa o usuário de <code>config.php</code>, com privilégio mínimo.</p>
  <?php foreach ($msgs as $m): ?><div class="aviso <?= $ok ? 'ok' : 'info' ?>"><?= e($m) ?></div><?php endforeach; ?>
  <?php if ($ok): ?>
    <div class="aviso ok"><b>Guarde agora:</b> usuário <b>admin</b>, senha <b><?= e($senhaGerada) ?></b>. Esta senha não será mostrada de novo — troque-a após o primeiro acesso em <i>Instrumento e cadastros › Usuários</i>.</div>
    <a class="btn" href="login.php">Ir para o sistema</a>
  <?php elseif (!$instalado || cfg('permitir_reinstalar')): ?>
    <form method="post">
      <label>Usuário administrativo do MySQL</label><input type="text" name="db_admin_user" value="root" required>
      <br><br><label>Senha do usuário administrativo</label><input type="password" name="db_admin_pass">
      <br><br><button class="btn" <?= $instalado ? 'data-confirma="Todos os dados atuais serão apagados. Continuar?"' : '' ?>>Instalar agora</button>
    </form>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/inc/rodape.php';
