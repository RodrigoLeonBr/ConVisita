<?php
require __DIR__ . '/inc/base.php';
$erro = '';
// ponytail: trava por sessão + atraso de 1s. Suficiente para intranet; para brute force via internet, trocar por limite por IP (tabela/cache).
$JANELA = 900; $MAX = 5; // 5 tentativas por 15 minutos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $falhas = $_SESSION['login_falhas'] ?? ['n' => 0, 'ts' => 0];
    if (time() - $falhas['ts'] > $JANELA) $falhas = ['n' => 0, 'ts' => 0];
    if ($falhas['n'] >= $MAX) {
        $erro = 'Muitas tentativas. Aguarde alguns minutos e tente novamente.';
    } else {
        $u = linha('SELECT * FROM usuario WHERE login = ? AND ativo = 1', [post('login')]);
        if ($u && password_verify((string)post('senha'), $u['senha_hash'])) {
            unset($_SESSION['login_falhas']);
            session_regenerate_id(true);
            $_SESSION['usuario'] = ['id' => $u['id'], 'nome' => $u['nome'], 'login' => $u['login'], 'perfil' => $u['perfil']];
            $volta = (string)get('volta', '');
            redireciona($volta !== '' && str_starts_with($volta, '/') ? $volta : 'index.php');
        }
        sleep(1);
        $_SESSION['login_falhas'] = ['n' => $falhas['n'] + 1, 'ts' => time()];
        $erro = 'Usuário ou senha inválidos.';
    }
}
$titulo = 'Entrar';
require __DIR__ . '/inc/topo.php';
?>
<div class="cartao login">
  <h1>Acesso ao sistema</h1>
  <p class="sub">Visitas do Conselho Municipal de Saúde</p>
  <?php if ($erro): ?><div class="aviso erro"><?= e($erro) ?></div><?php endif; ?>
  <form method="post">
    <label>Usuário</label><input type="text" name="login" autofocus required>
    <br><br><label>Senha</label><input type="password" name="senha" required>
    <br><br><button class="btn">Entrar</button>
  </form>
</div>
<?php require __DIR__ . '/inc/rodape.php';
