<?php
declare(strict_types=1);
namespace Convisita\Controllers;
use Convisita\Core\Controller;

final class ComparativoController extends Controller
{
    public function index(array $p): void
    {
        $this->exigeLogin();
        $cs = ciclos();
        $cB = (int)get('b', $cs[0]['id'] ?? 0);
        $cA = (int)get('a', $cs[1]['id'] ?? 0);
        $A = []; foreach (resultados_ciclo($cA) as $r) $A[$r['unidade_id']] = $r;
        $B = []; foreach (resultados_ciclo($cB) as $r) $B[$r['unidade_id']] = $r;
        $ids = array_unique(array_merge(array_keys($A), array_keys($B)));
        $linhas = [];
        foreach ($ids as $i) {
            $a = $A[$i] ?? null; $b = $B[$i] ?? null;
            $linhas[] = ['id' => $i, 'nome' => ($b ?? $a)['unidade'], 'a' => $a, 'b' => $b,
                         'dif' => ($a && $b) ? (float)$b['nota_final'] - (float)$a['nota_final'] : null];
        }
        usort($linhas, fn($x, $y) => [($x['dif'] === null), -($x['dif'] ?? 0)] <=> [($y['dif'] === null), -($y['dif'] ?? 0)]);
        $IA = []; foreach (medias_itens($cA) as $r) $IA[$r['codigo']] = $r;
        $IB = medias_itens($cB);
        $nome = fn($id) => current(array_filter($cs, fn($c) => (int)$c['id'] === $id))['nome'] ?? '';
        $this->render('comparativo/index', [
            'titulo' => 'Comparativo', 'cs' => $cs, 'cA' => $cA, 'cB' => $cB,
            'linhas' => $linhas, 'IA' => $IA, 'IB' => $IB, 'nomeA' => $nome($cA), 'nomeB' => $nome($cB),
        ], 'comparativo');
    }
}
