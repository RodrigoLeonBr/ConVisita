<?php
declare(strict_types=1);
namespace Convisita\Controllers;
use Convisita\Core\Controller;
use Convisita\Models\Visita;

final class PainelController extends Controller
{
    public function index(array $p): void
    {
        $this->exigeLogin();

        $cicloId = (int) get('ciclo', ciclo_padrao());
        $tipoId  = (int) get('tipo', 0) ?: null;
        $ciclo   = linha('SELECT * FROM ciclo WHERE id = ?', [$cicloId]);
        $res     = $cicloId ? Visita::doCiclo($cicloId, $tipoId) : [];
        $completas = array_filter($res, fn($r) => $r['situacao'] === 'concluida');
        $exibir = in_array(get('exibir'), ['visitadas', 'sem_visita'], true) ? get('exibir') : 'todas';

        // $tipoId já é int, seguro concatenar.
        $filtroTipo = fn(string $conector, string $alias = 'u.') => $tipoId ? " $conector {$alias}tipo_id = $tipoId" : '';

        // Unidades ativas sem visita válida no ciclo (respeitam o filtro de tipo)
        $visitadas = array_column($res, 'unidade_id');
        $faltantes = array_values(array_filter(consulta(
            "SELECT u.id AS unidade_id, u.nome AS unidade, t.nome AS tipo_unidade, a.ultima_visita, a.visita_id, a.nota_final
               FROM unidade u LEFT JOIN tipo_unidade t ON t.id = u.tipo_id LEFT JOIN vw_agenda a ON a.unidade_id = u.id
              WHERE u.situacao = 'ativa'" . $filtroTipo('AND') . ' ORDER BY u.nome'),
            fn($u) => !in_array($u['unidade_id'], $visitadas)));

        $totUnid = (int) linha("SELECT COUNT(*) n FROM unidade WHERE situacao = 'ativa'" . $filtroTipo('AND', ''))['n'];
        $media = $completas ? array_sum(array_column($completas, 'nota_final')) / count($completas) : null;
        $dist = ['verde' => 0, 'amarelo' => 0, 'vermelho' => 0];
        foreach ($completas as $r) { $c = farol($r['nota_final']); if (isset($dist[$c])) $dist[$c]++; }
        $medDim = [];
        foreach (['perc_infra_fisica' => 'Infraestrutura Física', 'perc_infra_humana' => 'Infraestrutura Humana',
                  'perc_plano_municipal' => 'Plano Municipal de Saúde', 'perc_percepcao_usuario' => 'Percepção do Usuário'] as $k => $rot) {
            $v = array_filter(array_column($res, $k), fn($x) => $x !== null);
            $medDim[$rot] = $v ? array_sum($v) / count($v) : null;
        }
        $agenda = linha("SELECT SUM(situacao_agenda='atrasada') atrasadas, SUM(situacao_agenda='sem_visita') sem_visita,
                                SUM(situacao_agenda='proximos_30_dias') proximas FROM vw_agenda a JOIN unidade u ON u.id = a.unidade_id"
                         . $filtroTipo('WHERE'));
        $pend = (int) linha("SELECT COUNT(*) n FROM pendencia p JOIN unidade u ON u.id = p.unidade_id WHERE p.situacao IN ('aberta','em_andamento')"
                         . $filtroTipo('AND'))['n'];
        $itens = $cicloId ? medias_itens($cicloId, $tipoId) : [];
        usort($itens, fn($a, $b) => ($a['media'] ?? 9) <=> ($b['media'] ?? 9));

        $this->render('painel/index', [
            'titulo' => 'Painel',
            'ciclo' => $ciclo, 'cicloId' => $cicloId, 'tipoId' => $tipoId, 'exibir' => $exibir,
            'res' => $res, 'completas' => $completas, 'faltantes' => $faltantes, 'totUnid' => $totUnid,
            'media' => $media, 'dist' => $dist, 'medDim' => $medDim, 'agenda' => $agenda, 'pend' => $pend, 'itens' => $itens,
        ], 'painel');
    }
}
