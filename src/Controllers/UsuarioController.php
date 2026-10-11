<?php
declare(strict_types=1);
namespace Convisita\Controllers;
use Convisita\Core\Controller;

final class UsuarioController extends Controller
{
    private const PERFIS = ['admin' => 'Administrador', 'conselheiro' => 'Conselheiro (lança visitas)', 'consulta' => 'Consulta'];

    public function index(array $p): void
    {
        $this->exigeLogin();
        $this->render('cadastros/usuarios', [
            'titulo' => 'Usuários', 'perfis' => self::PERFIS,
            'usuarios' => consulta('SELECT * FROM usuario ORDER BY nome'),
        ], 'cadastros');
    }

    public function salvar(array $p): void
    {
        $this->exigeLogin();
        confere_csrf();
        $eu = usuario();
        $acao = post('acao');
        if ($acao === 'minha_senha') {
            $u = linha('SELECT * FROM usuario WHERE id = ?', [$eu['id']]);
            if (!password_verify((string)post('atual'), $u['senha_hash'])) aviso('Senha atual incorreta.', 'erro');
            elseif (strlen((string)post('nova')) < 6) aviso('A nova senha deve ter ao menos 6 caracteres.', 'erro');
            else { executa('UPDATE usuario SET senha_hash = ? WHERE id = ?', [password_hash((string)post('nova'), PASSWORD_DEFAULT), $eu['id']]); aviso('Senha alterada.'); }
        } else {
            $this->exigeAdmin();
            $perfil = in_array(post('perfil'), ['admin', 'conselheiro', 'consulta'], true) ? post('perfil') : 'consulta';
            $id = (int)post('id');
            try {
                if ($id) {
                    executa('UPDATE usuario SET nome=?, perfil=?, ativo=? WHERE id=?', [post('nome'), $perfil, post('ativo') ? 1 : 0, $id]);
                    if (post('senha')) executa('UPDATE usuario SET senha_hash=? WHERE id=?', [password_hash((string)post('senha'), PASSWORD_DEFAULT), $id]);
                } else {
                    executa('INSERT INTO usuario (nome, login, senha_hash, perfil) VALUES (?,?,?,?)', [post('nome'), post('login'), password_hash((string)post('senha'), PASSWORD_DEFAULT), $perfil]);
                }
                aviso('Usuário gravado.');
            } catch (\PDOException $e) { aviso('Login já utilizado.', 'erro'); }
        }
        $this->redireciona(BASE . '/usuarios');
    }
}
