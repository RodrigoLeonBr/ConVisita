<?php
// Consultas reutilizadas pelo painel, comparativo e exportacao

/** Resultado da visita mais recente de cada unidade dentro de um ciclo. */
function resultados_ciclo(int $cicloId, ?int $tipoId = null): array
{
    $sql = "SELECT r.*, u.tipo_id
              FROM vw_resultado_visita r
              JOIN unidade u ON u.id = r.unidade_id
             WHERE r.ciclo_id = :c AND r.situacao IN ('concluida','incompleta')
               AND r.visita_id = (SELECT v2.id FROM visita v2
                                   WHERE v2.unidade_id = r.unidade_id AND v2.ciclo_id = :c2
                                     AND v2.situacao IN ('concluida','incompleta')
                                   ORDER BY v2.data_visita DESC, v2.id DESC LIMIT 1)";
    $p = [':c' => $cicloId, ':c2' => $cicloId];
    if ($tipoId) { $sql .= ' AND u.tipo_id = :t'; $p[':t'] = $tipoId; }
    $sql .= " ORDER BY (r.situacao = 'incompleta'), r.nota_final DESC, r.unidade";
    return consulta($sql, $p);
}

/** Media de cada item no ciclo (considerando a ultima visita de cada unidade). */
function medias_itens(int $cicloId, ?int $tipoId = null): array
{
    $filtroTipo = $tipoId ? ' AND v.unidade_id IN (SELECT id FROM unidade WHERE tipo_id = ' . (int)$tipoId . ')' : '';
    return consulta("SELECT i.codigo, i.nome, d.nome_curto AS dimensao,
                            COUNT(a.pontuacao) AS n, AVG(a.pontuacao) AS media,
                            SUM(a.pontuacao = 0) AS zeros
                       FROM item_indicador i
                       JOIN dimensao d ON d.id = i.dimensao_id
                       LEFT JOIN avaliacao_item a ON a.item_id = i.id AND a.nao_se_aplica = 0
                            AND a.visita_id IN (SELECT v.id FROM visita v
                                                 WHERE v.ciclo_id = :c AND v.situacao IN ('concluida','incompleta')
                                                   AND v.id = (SELECT v2.id FROM visita v2
                                                                WHERE v2.unidade_id = v.unidade_id AND v2.ciclo_id = :c2
                                                                  AND v2.situacao IN ('concluida','incompleta')
                                                                ORDER BY v2.data_visita DESC, v2.id DESC LIMIT 1)" . $filtroTipo . ")
                      WHERE i.ativo = 1
                      GROUP BY i.id, i.codigo, i.nome, d.nome_curto, i.ordem
                      ORDER BY i.ordem", [':c' => $cicloId, ':c2' => $cicloId]);
}

function ciclo_padrao(): int
{
    $c = linha("SELECT c.id FROM ciclo c JOIN visita v ON v.ciclo_id = c.id
                 WHERE v.situacao IN ('concluida','incompleta')
                 ORDER BY c.data_inicio DESC LIMIT 1");
    return (int)($c['id'] ?? 0);
}
