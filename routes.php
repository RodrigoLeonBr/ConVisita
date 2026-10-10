<?php
use Convisita\Controllers\{PainelController, AuthController};
return [
    'GET /'        => [PainelController::class, 'index'],
    'GET /painel'  => [PainelController::class, 'index'],
    'GET /login'   => [AuthController::class, 'formulario'],
    'POST /login'  => [AuthController::class, 'entrar'],
    'GET /sair'    => [AuthController::class, 'sair'],
];
