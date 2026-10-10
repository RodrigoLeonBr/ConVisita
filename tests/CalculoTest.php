<?php
use PHPUnit\Framework\TestCase;
use Convisita\Models\Visita;

final class CalculoTest extends TestCase
{
    public static function setUpBeforeClass(): void { require_once __DIR__ . '/../bootstrap.php'; }

    public function test_resultado_tem_nota_final_coerente(): void
    {
        $r = Visita::resultado(1); // visita_id 1 da carga histórica
        $this->assertNotNull($r, 'Banco convisita deve estar instalado com a carga histórica local.');
        $dims = array_filter(
            [$r['perc_infra_fisica'], $r['perc_infra_humana'], $r['perc_plano_municipal'], $r['perc_percepcao_usuario']],
            fn($x) => $x !== null
        );
        if ($dims) {
            $this->assertEqualsWithDelta(array_sum($dims) / count($dims), (float)$r['nota_final'], 0.0001);
        }
    }
}
