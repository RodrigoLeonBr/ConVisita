<?php
/** @var string $titulo */
/** @var string $menu */
$usuTopo = usuario();
$itensMenu = [
    'painel' => ['index.php', 'Painel'],
    'visitas' => ['visitas.php', 'Visitas'],
    'unidades' => ['unidades.php', 'Unidades'],
    'agenda' => ['agenda.php', 'Agenda'],
    'pendencias' => ['pendencias.php', 'Pendências'],
    'comparativo' => ['comparativo.php', 'Comparativo'],
    'cadastros' => ['instrumento.php', 'Instrumento e cadastros'],
];
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($titulo ?? '') . ' | CONVISITA') ?></title>
<link rel="stylesheet" href="assets/estilo.css">
</head>
<body>
<header class="topo">
  <div class="marca"><b>CONVISITA</b><span><?= e(cfg('titulo')) ?></span></div>
  <?php if ($usuTopo): ?>
  <nav>
    <?php foreach ($itensMenu as $k => [$href, $rot]): ?>
      <a href="<?= $href ?>" class="<?= ($menu ?? '') === $k ? 'ativo' : '' ?>"><?= e($rot) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="usuario"><?= e($usuTopo['nome']) ?> <small>(<?= e($usuTopo['perfil']) ?>)</small> · <a href="usuarios.php">Senha</a> · <a href="sair.php">Sair</a></div>
  <?php endif; ?>
</header>
<main>
<?php foreach ($_SESSION['avisos'] ?? [] as [$t, $m]): ?>
  <div class="aviso <?= e($t) ?>"><?= e($m) ?></div>
<?php endforeach; unset($_SESSION['avisos']); ?>
