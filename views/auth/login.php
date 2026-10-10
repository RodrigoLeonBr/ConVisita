<div class="row justify-content-center"><div class="col-md-4">
  <h1 class="h4 mb-3">Acesso ao sistema</h1>
  <p class="text-muted">Visitas do Conselho Municipal de Saúde</p>
  <?php if (!empty($erro)): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>
  <form method="post" action="<?= BASE ?>/login">
    <div class="mb-3"><label for="login" class="form-label">Usuário</label><input class="form-control" id="login" name="login" autofocus required></div>
    <div class="mb-3"><label for="senha" class="form-label">Senha</label><input type="password" class="form-control" id="senha" name="senha" required></div>
    <button class="btn btn-primary">Entrar</button>
  </form>
</div></div>
