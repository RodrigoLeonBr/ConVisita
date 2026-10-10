<?php
declare(strict_types=1);
namespace Convisita\Core;

abstract class Controller
{
    protected function render(string $template, array $dados = [], string $menu = ''): void
    { View::render($template, $dados, $menu); }

    protected function redireciona(string $url): void { redireciona($url); } // reutiliza helper de inc/base.php

    protected function exigeLogin(): void { exige_login(); }
    protected function exigeEdicao(): void { exige_edicao(); }
    protected function exigeAdmin(): void { exige_admin(); }
}
