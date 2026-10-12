<!doctype html><html lang="pt-BR"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($titulo ?? '') . ' | CONVISITA') ?></title>
<script>(function(){try{var t=localStorage.getItem('tema');if(!t)t=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';document.documentElement.setAttribute('data-bs-theme',t);}catch(e){}})();</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/estilo.css">
</head><body>
<?php if (usuario()): ?>
<div class="app">
  <aside class="barra-lat" id="barraLat">
    <a class="marca" href="<?= BASE ?>/painel">
      <img src="<?= BASE ?>/logo_sistema.png" alt="CONVISITA — Conselho Municipal de Saúde">
    </a>
    <nav aria-label="Navegação principal">
      <?php
      $itens = [
        'painel'=>['/painel','Painel'],'visitas'=>['/visitas','Visitas'],'unidades'=>['/unidades','Unidades'],
        'agenda'=>['/agenda','Agenda'],'pendencias'=>['/pendencias','Pendências'],'comparativo'=>['/comparativo','Comparativo'],
      ];
      foreach ($itens as $k=>[$href,$rot]): $at = ($menu ?? '')===$k; ?>
        <a href="<?= BASE . $href ?>" class="<?= $at?'ativo':'' ?>"<?= $at?' aria-current="page"':'' ?>><?= e($rot) ?></a>
      <?php endforeach; ?>
      <?php
      // Páginas de cadastro compartilham $menu='cadastros'; destaca o item pela URL atual.
      $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
      $cad = [
        '/instrumento'=>'Instrumento','/conselheiros'=>'Conselheiros',
        '/usuarios'=>'Usuários','/backup'=>'Backup e sincronização',
      ];
      $cadAtivo = false;
      foreach ($cad as $href=>$rot) if (str_starts_with($uri, BASE . $href)) { $cadAtivo = true; break; }
      ?>
      <details class="submenu"<?= $cadAtivo?' open':'' ?>>
        <summary>Instrumento e cadastros</summary>
        <div class="links">
          <?php foreach ($cad as $href=>$rot): $at = str_starts_with($uri, BASE . $href); ?>
            <a href="<?= BASE . $href ?>" class="<?= $at?'ativo':'' ?>"<?= $at?' aria-current="page"':'' ?>><?= e($rot) ?></a>
          <?php endforeach; ?>
        </div>
      </details>
    </nav>
    <div class="rodape-lat">
      <button type="button" id="btn-tema" class="btn-tema" aria-pressed="false" aria-label="Alternar tema claro ou escuro">
        <span class="ico-tema"></span> Tema
      </button>
    </div>
  </aside>
  <div class="overlay" id="overlay" tabindex="-1"></div>
  <div class="conteudo">
    <header class="topbar">
      <button type="button" id="btn-menu" class="hamburguer" aria-label="Alternar menu" aria-expanded="true" aria-controls="barraLat">☰</button>
      <a class="marca-topo" href="<?= BASE ?>/painel">
        <img src="<?= BASE ?>/logo.png" alt="Logo do Conselho de Saúde">
        <span class="titulo-topo">Conselho Municipal de Saúde — Visitas das Unidades</span>
      </a>
      <?php
      $nome = usuario()['nome'];
      $partes = preg_split('/\s+/', trim($nome)) ?: [$nome];
      $ini = mb_strtoupper(mb_substr($partes[0], 0, 1) . (count($partes) > 1 ? mb_substr(end($partes), 0, 1) : ''));
      ?>
      <details class="usuario-menu" id="menuUsuario">
        <summary aria-label="Menu do usuário">
          <span class="avatar" aria-hidden="true"><?= e($ini) ?></span>
          <span class="u-nome"><?= e($nome) ?> <small>(<?= e(usuario()['perfil']) ?>)</small></span>
        </summary>
        <div class="menu">
          <a href="<?= BASE ?>/usuarios">Alterar senha</a>
          <a href="<?= BASE ?>/sair">Sair</a>
        </div>
      </details>
    </header>
    <main>
<?php foreach ($_SESSION['avisos'] ?? [] as [$t,$m]): ?>
      <div class="alert alert-<?= $t==='erro'?'danger':($t==='ok'?'success':'info') ?>"><?= e($m) ?></div>
<?php endforeach; unset($_SESSION['avisos']); ?>
<?= $conteudo ?>
    </main>
  </div>
</div>
<?php else: ?>
<main class="main-sozinho">
<?php foreach ($_SESSION['avisos'] ?? [] as [$t,$m]): ?>
  <div class="alert alert-<?= $t==='erro'?'danger':($t==='ok'?'success':'info') ?>"><?= e($m) ?></div>
<?php endforeach; unset($_SESSION['avisos']); ?>
<?= $conteudo ?>
</main>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE ?>/assets/app.js"></script>
</body></html>
