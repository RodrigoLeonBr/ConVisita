<?php
use Convisita\Controllers\{PainelController, AuthController, VisitaController, UnidadeController, AgendaController};
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

    'GET /unidades'          => [UnidadeController::class, 'lista'],
    'GET /unidade/nova'      => [UnidadeController::class, 'novo'],
    'POST /unidade/salvar'   => [UnidadeController::class, 'salvar'],
    'GET /unidade/{id}/editar' => [UnidadeController::class, 'editar'],
    'GET /unidade/{id}'      => [UnidadeController::class, 'ver'],

    'GET /agenda'            => [AgendaController::class, 'index'],
];
