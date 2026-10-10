<?php
declare(strict_types=1);
namespace Convisita\Core;

final class Router
{
    /** @param array<string,array{0:string,1:string}> $rotas  ex.: 'GET /unidade/{id}' => [Controller, 'acao'] */
    public function __construct(private array $rotas) {}

    /** @return array{0:string,1:string,2:array<string,string>}|null [classe, acao, params] */
    public function resolve(string $metodo, string $caminho): ?array
    {
        $caminho = '/' . trim($caminho, '/');
        foreach ($this->rotas as $chave => [$classe, $acao]) {
            [$m, $padrao] = explode(' ', $chave, 2);
            if ($m !== $metodo) continue;
            $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', rtrim($padrao, '/') ?: '/') . '$#';
            if (preg_match($regex, $caminho, $m2)) {
                $params = array_filter($m2, 'is_string', ARRAY_FILTER_USE_KEY);
                return [$classe, $acao, $params];
            }
        }
        return null;
    }
}
