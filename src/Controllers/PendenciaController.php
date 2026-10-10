<?php
declare(strict_types=1);
namespace Convisita\Controllers;
use Convisita\Core\Controller;

final class PendenciaController extends Controller
{
    public function index(array $p): void   { $this->exigeLogin(); $this->tela(null); }

    public function novo(array $p): void
    {
        $this->exigeLogin();
        $vis = (int)get('visita', 0);
        $edit = ['id' => 0, 'visita_id' => $vis ?: null,
                 'unidade_id' => $vis ? linha('SELECT unidade_id FROM visita WHERE id=?', [$vis])['unidade_id'] : (int)get('unidade', 0),
                 'dimensao_id' => null, 'descricao' => '', 'responsavel' => '', 'prazo' => null, 'situacao' => 'aberta', 'resolvida_em' => null, 'observacao' => ''];
        $this->tela($edit);
    }

    public function editar(array $p): void
    {
        $this->exigeLogin();
        $edit = linha('SELECT * FROM pendencia WHERE id = ?', [(int)($p['id'] ?? 0)]);
        if (!$edit) { http_response_code(404); require dirname(__DIR__, 2) . '/views/404.php'; return; }
        $this->tela($edit);
    }

    public function salvar(array $p): void
    {
        $this->exigeLogin();
        confere_csrf();
        $this->exigeEdicao();
        $pid = (int)post('id');
        if (post('acao') === 'excluir' && $pid) {
            executa('DELETE FROM pendencia WHERE id = ?', [$pid]);
            aviso('Pendência excluída.');
            $this->redireciona(BASE . '/pendencias');
        }
        $sit = in_array(post('situacao'), ['aberta', 'em_andamento', 'resolvida', 'cancelada'], true) ? post('situacao') : 'aberta';
        $visita = (int)post('visita_id') ?: null;
        $unid = (int)post('unidade_id');
        if ($visita && !$unid) $unid = (int)linha('SELECT unidade_id FROM visita WHERE id = ?', [$visita])['unidade_id'];
        $d = [$visita, $unid, nulo(post('dimensao_id')), post('descricao'), nulo(post('responsavel')), nulo(post('prazo')), $sit,
              $sit === 'resolvida' ? (nulo(post('resolvida_em')) ?? date('Y-m-d')) : null, nulo(post('observacao'))];
        if (!$unid || !$d[3]) {
            aviso('Informe a unidade e a descrição.', 'erro');
            $this->redireciona(BASE . ($pid ? '/pendencia/' . $pid . '/editar' : '/pendencia/nova'));
        }
        if ($pid) executa('UPDATE pendencia SET visita_id=?, unidade_id=?, dimensao_id=?, descricao=?, responsavel=?, prazo=?, situacao=?, resolvida_em=?, observacao=?, atualizado_em=CURRENT_TIMESTAMP WHERE id=?', [...$d, $pid]);
        else executa('INSERT INTO pendencia (uuid, visita_id, unidade_id, dimensao_id, descricao, responsavel, prazo, situacao, resolvida_em, observacao) VALUES (?,?,?,?,?,?,?,?,?,?)', [novo_uuid(), ...$d]);
        aviso('Pendência gravada.');
        $this->redireciona($visita ? BASE . '/visita/' . $visita : BASE . '/pendencias');
    }

    private function tela(?array $edit): void
    {
        $filtro = (string)get('situacao', 'pendentes');
        $w = $filtro === 'pendentes' ? "WHERE p.situacao IN ('aberta','em_andamento')" : ($filtro === 'todas' ? '' : 'WHERE p.situacao = ' . db()->quote($filtro));
        $lista = consulta("SELECT p.*, u.nome AS unidade, d.nome_curto AS dim, v.data_visita FROM pendencia p
                            JOIN unidade u ON u.id = p.unidade_id LEFT JOIN dimensao d ON d.id = p.dimensao_id
                            LEFT JOIN visita v ON v.id = p.visita_id $w ORDER BY (p.prazo IS NULL), p.prazo, u.nome");
        $this->render('pendencias/index', ['titulo' => 'Pendências', 'edit' => $edit, 'lista' => $lista, 'filtro' => $filtro], 'pendencias');
    }
}
