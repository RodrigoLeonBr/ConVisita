<?php
use PHPUnit\Framework\TestCase;

final class AutoloadTest extends TestCase
{
    public function test_carrega_classe_do_src(): void
    {
        require __DIR__ . '/../bootstrap.php';
        $this->assertTrue(class_exists('Convisita\\Core\\Router'));
    }
}
