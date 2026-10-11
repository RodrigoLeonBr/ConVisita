<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
use Convisita\Core\Router;

// Base path = diretório do front controller (ex.: /convisita). Vazio se estiver na raiz do host.
define('BASE', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/'));

$metodo  = $_SERVER['REQUEST_METHOD'];
$caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (BASE !== '' && str_starts_with($caminho, BASE)) $caminho = substr($caminho, strlen(BASE));
$caminho = '/' . trim(rawurldecode($caminho), '/');

$router = new Router(require __DIR__ . '/routes.php');
$r = $router->resolve($metodo, $caminho);
if ($r === null) { http_response_code(404); require __DIR__ . '/views/404.php'; exit; }
[$classe, $acao, $params] = $r;
(new $classe())->$acao($params);
