<?php
declare(strict_types=1);
namespace Convisita\Controllers;
use Convisita\Core\Controller;

final class ConselheiroController extends Controller
{
    private const SEGS = ['usuario' => 'Usuários', 'trabalhador' => 'Trabalhadores', 'gestor_prestador' => 'Gestores/Prestadores'];

    public function index(array $p): void
    {
        $this->exigeLogin();
        $lista = consulta('SELECT c.*, (SELECT COUNT(*) FROM visita_conselheiro vc WHERE vc.conselheiro_id = c.id) AS visitas FROM conselheiro c ORDER BY c.ativo DESC, c.nome');
        $this->render('cadastros/conselheiros', ['titulo' => 'Conselheiros', 'lista' => $lista, 'segs' => self::SEGS], 'cadastros');
    }

    public function salvar(array $p): void
    {
        $this->exigeLogin();
        confere_csrf();
        $this->exigeEdicao();
        $id = (int)post('id');
        $seg = in_array(post('segmento'), ['usuario', 'trabalhador', 'gestor_prestador'], true) ? post('segmento') : null;
        if ($id) executa('UPDATE conselheiro SET nome=?, segmento=?, ativo=? WHERE id=?', [post('nome'), $seg, post('ativo') ? 1 : 0, $id]);
        elseif (post('nome')) executa('INSERT INTO conselheiro (nome, segmento) VALUES (?,?)', [post('nome'), $seg]);
        aviso('Conselheiro gravado.');
        $this->redireciona(BASE . '/conselheiros');
    }
}
