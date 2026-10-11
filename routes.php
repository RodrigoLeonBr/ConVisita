<?php
use Convisita\Controllers\{PainelController, AuthController, VisitaController, UnidadeController, AgendaController, PendenciaController, ComparativoController, InstrumentoController, ConselheiroController, UsuarioController, ExportController, BackupController};
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

    'GET /pendencias'          => [PendenciaController::class, 'index'],
    'GET /pendencia/nova'      => [PendenciaController::class, 'novo'],
    'POST /pendencia/salvar'   => [PendenciaController::class, 'salvar'],
    'GET /pendencia/{id}/editar' => [PendenciaController::class, 'editar'],

    'GET /comparativo'       => [ComparativoController::class, 'index'],

    'GET /instrumento'         => [InstrumentoController::class, 'index'],
    'POST /instrumento/salvar' => [InstrumentoController::class, 'salvar'],
    'GET /conselheiros'        => [ConselheiroController::class, 'index'],
    'POST /conselheiros/salvar' => [ConselheiroController::class, 'salvar'],
    'GET /usuarios'            => [UsuarioController::class, 'index'],
    'POST /usuarios/salvar'    => [UsuarioController::class, 'salvar'],
    'GET /exportar'            => [ExportController::class, 'csv'],

    'GET /backup'            => [BackupController::class, 'index'],
    'POST /backup/salvar'    => [BackupController::class, 'salvar'],
];
