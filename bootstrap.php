<?php
declare(strict_types=1);
require __DIR__ . '/inc/base.php'; // reutiliza núcleo existente
spl_autoload_register(function (string $classe): void {
    $prefixo = 'Convisita\\';
    if (!str_starts_with($classe, $prefixo)) return;
    $rel = str_replace('\\', '/', substr($classe, strlen($prefixo)));
    $arq = __DIR__ . '/src/' . $rel . '.php';
    if (is_file($arq)) require $arq;
});
