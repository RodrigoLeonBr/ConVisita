<?php
declare(strict_types=1);
namespace Convisita\Controllers;
use Convisita\Core\Controller;

final class ExportController extends Controller
{
    public function csv(array $p): void
    {
        $this->exigeLogin();
        $tipo = (string)get('tipo', 'resultados');
        $num = fn($x) => $x === null ? '' : str_replace('.', ',', (string)round((float)$x, 4));

        switch ($tipo) {
            case 'itens':
                $cab = ['visita_id', 'ciclo', 'data_visita', 'unidade', 'tipo_unidade', 'dimensao', 'item', 'nome_item', 'pontuacao', 'nao_se_aplica', 'observacao', 'situacao_visita'];
                $rows = consulta("SELECT v.id, c.nome ciclo, v.data_visita, u.nome unidade, t.nome tipo, d.nome_curto dim, i.codigo, i.nome,
                                         a.pontuacao, a.nao_se_aplica, a.observacao, v.situacao
                                    FROM avaliacao_item a JOIN visita v ON v.id = a.visita_id JOIN unidade u ON u.id = v.unidade_id
                                    LEFT JOIN tipo_unidade t ON t.id = u.tipo_id JOIN ciclo c ON c.id = v.ciclo_id
                                    JOIN item_indicador i ON i.id = a.item_id JOIN dimensao d ON d.id = i.dimensao_id
                                   ORDER BY v.data_visita, u.nome, i.ordem");
                $rows = array_map(fn($r) => array_values($r), $rows);
                break;
            case 'agenda':
                $cab = ['unidade', 'tipo_unidade', 'situacao_unidade', 'periodicidade_meses', 'ultima_visita', 'nota_final', 'proxima_prevista', 'situacao_agenda'];
                $rows = array_map(fn($r) => [$r['unidade'], $r['tipo_unidade'], $r['situacao_unidade'], $r['periodicidade_meses'], $r['ultima_visita'],
                    $num($r['nota_final']), $r['proxima_prevista'], $r['situacao_agenda']], consulta('SELECT * FROM vw_agenda ORDER BY unidade'));
                break;
            default:
                $tipo = 'resultados';
                $cab = ['visita_id', 'ciclo', 'data_visita', 'unidade', 'tipo_unidade', 'situacao', 'pontos_totais', 'pontos_possiveis',
                        'perc_infra_fisica', 'perc_infra_humana', 'perc_plano_municipal', 'perc_percepcao_usuario', 'nota_final', 'nota_metodo_planilha', 'classificacao'];
                $rows = array_map(fn($r) => [$r['visita_id'], $r['ciclo'], $r['data_visita'], $r['unidade'], $r['tipo_unidade'], $r['situacao'],
                    $r['pontos_totais'], $r['pontos_possiveis'], $num($r['perc_infra_fisica']), $num($r['perc_infra_humana']),
                    $num($r['perc_plano_municipal']), $num($r['perc_percepcao_usuario']), $num($r['nota_final']), $num($r['nota_metodo_planilha']),
                    farol($r['nota_final'])], consulta('SELECT * FROM vw_resultado_visita ORDER BY data_visita, unidade'));
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="convisita_' . $tipo . '_' . date('Ymd') . '.csv"');
        $saida = fopen('php://output', 'w');
        fwrite($saida, "\xEF\xBB\xBF");
        fputcsv($saida, $cab, ';');
        foreach ($rows as $r) fputcsv($saida, $r, ';');
        fclose($saida);
    }
}
