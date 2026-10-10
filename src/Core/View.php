<?php
declare(strict_types=1);
namespace Convisita\Core;

final class View
{
    public static function render(string $template, array $dados = [], string $menu = ''): void
    {
        $arq = __DIR__ . '/../../views/' . $template . '.php';
        if (!is_file($arq)) { http_response_code(500); exit('View não encontrada: ' . e($template)); }
        $titulo = $dados['titulo'] ?? '';
        extract($dados, EXTR_SKIP);
        ob_start();
        require $arq;              // a view usa $titulo, $menu e as variáveis de $dados; escapa com e()
        $conteudo = ob_get_clean();
        require __DIR__ . '/../../views/layout.php'; // layout imprime $conteudo
    }
}
