<h1 class="h3">Usuários e senhas</h1>
<form method="post" class="cartao filtros" action="<?= BASE ?>/usuarios/salvar"><?= csrf() ?><input type="hidden" name="acao" value="minha_senha">
  <div><label>Minha senha atual</label><input type="password" name="atual" required></div>
  <div><label>Nova senha</label><input type="password" name="nova" required></div>
  <div><button class="btn">Trocar minha senha</button></div>
</form>
<?php if (e_admin()): ?>
<h2>Usuários do sistema</h2>
<form method="post" class="cartao filtros" action="<?= BASE ?>/usuarios/salvar"><?= csrf() ?>
  <div><label>Nome</label><input type="text" name="nome" required></div><div><label>Login</label><input type="text" name="login" required></div>
  <div><label>Senha inicial</label><input type="password" name="senha" required></div>
  <div><label>Perfil</label><select name="perfil"><?php foreach ($perfis as $k => $r): ?><option value="<?= $k ?>"><?= e($r) ?></option><?php endforeach; ?></select></div>
  <div><button class="btn">Criar usuário</button></div>
</form>
<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th scope="col">Nome</th><th scope="col">Login</th><th scope="col">Perfil</th><th scope="col">Nova senha</th><th scope="col">Ativo</th><th scope="col"></th></tr></thead><tbody>
<?php foreach ($usuarios as $u): ?>
  <tr>
    <td><form id="f<?= $u['id'] ?>" method="post" action="<?= BASE ?>/usuarios/salvar"><?= csrf() ?><input type="hidden" name="id" value="<?= $u['id'] ?>"></form><input form="f<?= $u['id'] ?>" type="text" name="nome" value="<?= e($u['nome']) ?>"></td><td><?= e($u['login']) ?></td>
    <td><select form="f<?= $u['id'] ?>" name="perfil"><?php foreach ($perfis as $k => $r): ?><option value="<?= $k ?>" <?= $u['perfil'] === $k ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?></select></td>
    <td><input form="f<?= $u['id'] ?>" type="password" name="senha" placeholder="manter"></td>
    <td><input form="f<?= $u['id'] ?>" type="checkbox" name="ativo" value="1" <?= $u['ativo'] ? 'checked' : '' ?>></td>
    <td><button form="f<?= $u['id'] ?>" class="btn peq sec">Gravar</button></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
