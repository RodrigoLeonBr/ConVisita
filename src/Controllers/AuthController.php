<?php
declare(strict_types=1);
namespace Convisita\Controllers;
use Convisita\Core\Controller;

final class AuthController extends Controller
{
    public function formulario(array $p): void
    {
        $erro = $_SESSION['erro_login'] ?? '';
        unset($_SESSION['erro_login']);
        $this->render('auth/login', ['titulo' => 'Entrar', 'erro' => $erro], '');
    }

    public function entrar(array $p): void
    {
        $JANELA = 900; $MAX = 5; // 5 tentativas por 15 minutos
        $falhas = $_SESSION['login_falhas'] ?? ['n' => 0, 'ts' => 0];
        if (time() - $falhas['ts'] > $JANELA) $falhas = ['n' => 0, 'ts' => 0];
        if ($falhas['n'] >= $MAX) {
            $_SESSION['erro_login'] = 'Muitas tentativas. Aguarde alguns minutos e tente novamente.';
            $this->redireciona(BASE . '/login');
        }
        $u = linha('SELECT * FROM usuario WHERE login = ? AND ativo = 1', [post('login')]);
        if ($u && password_verify((string)post('senha'), $u['senha_hash'])) {
            unset($_SESSION['login_falhas']);
            session_regenerate_id(true);
            $_SESSION['usuario'] = ['id' => $u['id'], 'nome' => $u['nome'], 'login' => $u['login'], 'perfil' => $u['perfil']];
            $volta = (string)get('volta', '');
            $this->redireciona($volta !== '' && str_starts_with($volta, '/') ? $volta : BASE . '/painel');
        }
        sleep(1);
        $_SESSION['login_falhas'] = ['n' => $falhas['n'] + 1, 'ts' => time()];
        $_SESSION['erro_login'] = 'Usuário ou senha inválidos.';
        $this->redireciona(BASE . '/login');
    }

    public function sair(array $p): void
    {
        $_SESSION = [];
        session_destroy();
        $this->redireciona(BASE . '/login');
    }
}
