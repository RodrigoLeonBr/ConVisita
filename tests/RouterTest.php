<?php
use PHPUnit\Framework\TestCase;
use Convisita\Core\Router;

final class RouterTest extends TestCase
{
    public function test_resolve_rota_existente(): void
    {
        $r = new Router(['GET /painel' => ['X', 'index']]);
        $this->assertSame(['X', 'index', []], $r->resolve('GET', '/painel'));
    }

    public function test_rota_inexistente_retorna_null(): void
    {
        $r = new Router([]);
        $this->assertNull($r->resolve('GET', '/nao-existe'));
    }

    public function test_parametro_de_rota(): void
    {
        $r = new Router(['GET /unidade/{id}' => ['U', 'ver']]);
        $this->assertSame(['U', 'ver', ['id' => '7']], $r->resolve('GET', '/unidade/7'));
    }
}
