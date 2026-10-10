<!doctype html><html lang="pt-BR"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($titulo ?? '') . ' | CONVISITA') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/estilo.css">
</head><body>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= BASE ?>/painel"><b>CONVISITA</b></a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="Menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <?php if (usuario()): ?>
      <ul class="navbar-nav me-auto">
        <?php foreach ([
          'painel'=>['/painel','Painel'],'visitas'=>['/visitas','Visitas'],'unidades'=>['/unidades','Unidades'],
          'agenda'=>['/agenda','Agenda'],'pendencias'=>['/pendencias','Pendências'],'comparativo'=>['/comparativo','Comparativo'],
        ] as $k=>[$href,$rot]): ?>
          <li class="nav-item"><a class="nav-link <?= ($menu ?? '')===$k?'active':'' ?>" href="<?= BASE . $href ?>"><?= e($rot) ?></a></li>
        <?php endforeach; ?>
        <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">Instrumento e cadastros</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= BASE ?>/instrumento">Instrumento</a></li>
            <li><a class="dropdown-item" href="<?= BASE ?>/conselheiros">Conselheiros</a></li>
            <li><a class="dropdown-item" href="<?= BASE ?>/usuarios">Usuários</a></li>
            <li><a class="dropdown-item" href="<?= BASE ?>/backup">Backup e sincronização</a></li>
          </ul></li>
      </ul>
      <span class="navbar-text"><?= e(usuario()['nome']) ?> <small>(<?= e(usuario()['perfil']) ?>)</small> · <a href="<?= BASE ?>/usuarios">Senha</a> · <a href="<?= BASE ?>/sair">Sair</a></span>
      <?php endif; ?>
    </div>
  </div>
</nav>
<main class="container-fluid py-3">
<?php foreach ($_SESSION['avisos'] ?? [] as [$t,$m]): ?>
  <div class="alert alert-<?= $t==='erro'?'danger':($t==='ok'?'success':'info') ?>"><?= e($m) ?></div>
<?php endforeach; unset($_SESSION['avisos']); ?>
<?= $conteudo ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE ?>/assets/app.js"></script>
</body></html>
