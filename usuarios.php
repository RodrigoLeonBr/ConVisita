<?php
require __DIR__ . '/inc/base.php';
exige_login();
$eu = usuario();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    confere_csrf();
    $acao = post('acao');
    if ($acao === 'minha_senha') {
        $u = linha('SELECT * FROM usuario WHERE id = ?', [$eu['id']]);
        if (!password_verify((string)post('atual'), $u['senha_hash'])) aviso('Senha atual incorreta.', 'erro');
        elseif (strlen((string)post('nova')) < 6) aviso('A nova senha deve ter ao menos 6 caracteres.', 'erro');
        else { executa('UPDATE usuario SET senha_hash = ? WHERE id = ?', [password_hash((string)post('nova'), PASSWORD_DEFAULT), $eu['id']]); aviso('Senha alterada.'); }
    } else {
        exige_admin();
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
        } catch (PDOException $e) { aviso('Login já utilizado.', 'erro'); }
    }
    redireciona('usuarios.php');
}
$perfis = ['admin' => 'Administrador', 'conselheiro' => 'Conselheiro (lança visitas)', 'consulta' => 'Consulta'];
$titulo = 'Usuários'; $menu = 'cadastros';
require __DIR__ . '/inc/topo.php';
?>
<h1>Usuários e senhas</h1>
<form method="post" class="cartao filtros"><?= csrf() ?><input type="hidden" name="acao" value="minha_senha">
  <div><label>Minha senha atual</label><input type="password" name="atual" required></div>
  <div><label>Nova senha</label><input type="password" name="nova" required></div>
  <div><button class="btn">Trocar minha senha</button></div>
</form>
<?php if (e_admin()): ?>
<h2>Usuários do sistema</h2>
<form method="post" class="cartao filtros"><?= csrf() ?>
  <div><label>Nome</label><input type="text" name="nome" required></div><div><label>Login</label><input type="text" name="login" required></div>
  <div><label>Senha inicial</label><input type="password" name="senha" required></div>
  <div><label>Perfil</label><select name="perfil"><?php foreach ($perfis as $k => $r): ?><option value="<?= $k ?>"><?= e($r) ?></option><?php endforeach; ?></select></div>
  <div><button class="btn">Criar usuário</button></div>
</form>
<div class="tabela"><table><tr><th>Nome</th><th>Login</th><th>Perfil</th><th>Nova senha</th><th>Ativo</th><th></th></tr>
<?php foreach (consulta('SELECT * FROM usuario ORDER BY nome') as $u): ?>
  <tr>
    <td><form id="f<?= $u['id'] ?>" method="post"><?= csrf() ?><input type="hidden" name="id" value="<?= $u['id'] ?>"></form><input form="f<?= $u['id'] ?>" type="text" name="nome" value="<?= e($u['nome']) ?>"></td><td><?= e($u['login']) ?></td>
    <td><select form="f<?= $u['id'] ?>" name="perfil"><?php foreach ($perfis as $k => $r): ?><option value="<?= $k ?>" <?= $u['perfil'] === $k ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?></select></td>
    <td><input form="f<?= $u['id'] ?>" type="password" name="senha" placeholder="manter"></td>
    <td><input form="f<?= $u['id'] ?>" type="checkbox" name="ativo" value="1" <?= $u['ativo'] ? 'checked' : '' ?>></td>
    <td><button form="f<?= $u['id'] ?>" class="btn peq sec">Gravar</button></td></tr>
<?php endforeach; ?>
</table></div>
<?php endif; ?>
<?php require __DIR__ . '/inc/rodape.php';
