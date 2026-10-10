<?php
declare(strict_types=1);
namespace Convisita\Models;

final class Visita
{
    public static function resultado(int $id): ?array
    { return linha('SELECT * FROM vw_resultado_visita WHERE visita_id = ?', [$id]); }

    public static function doCiclo(int $cicloId, ?int $tipoId = null): array
    { return resultados_ciclo($cicloId, $tipoId); } // reutiliza inc/consultas.php

    public static function daUnidade(int $unidadeId): array
    { return consulta('SELECT * FROM vw_resultado_visita WHERE unidade_id = ? ORDER BY data_visita DESC', [$unidadeId]); }
}
