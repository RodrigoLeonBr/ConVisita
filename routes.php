<?php
use Convisita\Controllers\{PainelController, AuthController, VisitaController};
return [
    'GET /'        => [PainelController::class, 'index'],
    'GET /painel'  => [PainelController::class, 'index'],
    'GET /login'   => [AuthController::class, 'formulario'],
    'POST /login'  => [AuthController::class, 'entrar'],
    'GET /sair'    => [AuthController::class, 'sair'],

    'GET /visitas'          => [VisitaController::class, 'lista'],
    'GET /visita/nova'      => [VisitaController::class, 'novo'],
    'POST /visita/salvar'   => [VisitaController::class, 'salvar'],
    'GET /visita/{id}/editar' => [VisitaController::class, 'editar'],
    'GET /visita/{id}'      => [VisitaController::class, 'ver'],
];
