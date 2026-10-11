<?php
declare(strict_types=1);
require __DIR__ . '/inc/base.php';       // núcleo: conexão, helpers, auth, csrf
require __DIR__ . '/inc/consultas.php';  // consultas compartilhadas (painel, comparativo, export)
spl_autoload_register(function (string $classe): void {
    $prefixo = 'Convisita\\';
    if (!str_starts_with($classe, $prefixo)) return;
    $rel = str_replace('\\', '/', substr($classe, strlen($prefixo)));
    $arq = __DIR__ . '/src/' . $rel . '.php';
    if (is_file($arq)) require $arq;
});
