<?php
declare(strict_types=1);
namespace Convisita\Controllers;
use Convisita\Core\Controller;

final class UnidadeController extends Controller
{
    public function lista(array $p): void
    {
        $this->exigeLogin();
        $tipo = (int)get('tipo', 0);
        $lista = consulta('SELECT a.*, u.gestao, u.nome_planilha, (SELECT COUNT(*) FROM visita v WHERE v.unidade_id = a.unidade_id AND v.situacao IN (\'concluida\',\'incompleta\')) AS qtd_visitas
                             FROM vw_agenda a JOIN unidade u ON u.id = a.unidade_id'
            . ($tipo ? ' WHERE u.tipo_id = ' . $tipo : '') . ' ORDER BY a.tipo_unidade, a.unidade');
        $this->render('unidades/lista', ['titulo' => 'Unidades', 'lista' => $lista, 'tipo' => $tipo], 'unidades');
    }

    public function ver(array $p): void
    {
        $this->exigeLogin();
        $id = (int)($p['id'] ?? 0);
        $u = linha('SELECT u.*, t.nome AS tipo FROM unidade u LEFT JOIN tipo_unidade t ON t.id = u.tipo_id WHERE u.id = ?', [$id]);
        if (!$u) { http_response_code(404); require dirname(__DIR__, 2) . '/views/404.php'; return; }
        $ag = linha('SELECT * FROM vw_agenda WHERE unidade_id = ?', [$id]);
        $hist = consulta('SELECT * FROM vw_resultado_visita WHERE unidade_id = ? ORDER BY data_visita DESC', [$id]);
        $validas = array_values(array_filter(array_reverse($hist), fn($h) => in_array($h['situacao'], ['concluida', 'incompleta'], true) && $h['nota_final'] !== null));
        $pend = consulta('SELECT p.*, d.nome_curto AS dim FROM pendencia p LEFT JOIN dimensao d ON d.id = p.dimensao_id WHERE p.unidade_id = ? ORDER BY FIELD(p.situacao,\'aberta\',\'em_andamento\',\'resolvida\',\'cancelada\'), p.prazo', [$id]);
        $this->render('unidades/ficha', [
            'titulo' => $u['nome'], 'id' => $id, 'u' => $u, 'ag' => $ag, 'hist' => $hist, 'pend' => $pend,
            'grafico' => self::grafico($validas),
        ], 'unidades');
    }

    public function novo(array $p): void   { $this->exigeLogin(); $this->exigeEdicao(); $this->formulario(0); }
    public function editar(array $p): void  { $this->exigeLogin(); $this->exigeEdicao(); $this->formulario((int)($p['id'] ?? 0)); }

    public function salvar(array $p): void
    {
        $this->exigeLogin();
        $this->exigeEdicao();
        confere_csrf();
        $id = (int)post('id');
        $u = $id ? linha('SELECT * FROM unidade WHERE id = ?', [$id]) : null;
        $dados = [post('nome'), nulo(post('nome_planilha')), nulo(post('tipo_id')), nulo(post('gestao')), nulo(post('endereco')),
                  in_array(post('situacao'), ['ativa', 'em_reforma', 'inativa'], true) ? post('situacao') : 'ativa',
                  max(1, (int)post('periodicidade_meses', 6)), nulo(post('observacao'))];
        if (!$dados[0]) {
            aviso('Informe o nome da unidade.', 'erro');
            $this->redireciona($id ? BASE . '/unidade/' . $id . '/editar' : BASE . '/unidade/nova');
        }
        try {
            if ($u) {
                executa('UPDATE unidade SET nome=?, nome_planilha=?, tipo_id=?, gestao=?, endereco=?, situacao=?, periodicidade_meses=?, observacao=? WHERE id=?', [...$dados, $id]);
            } else {
                executa('INSERT INTO unidade (nome, nome_planilha, tipo_id, gestao, endereco, situacao, periodicidade_meses, observacao) VALUES (?,?,?,?,?,?,?,?)', $dados);
                $id = (int)db()->lastInsertId();
            }
            aviso('Cadastro gravado.');
            $this->redireciona(BASE . '/unidade/' . $id);
        } catch (\PDOException $ex) {
            aviso('Não foi possível gravar: já existe unidade com esse nome?', 'erro');
            $this->redireciona($id ? BASE . '/unidade/' . $id . '/editar' : BASE . '/unidade/nova');
        }
    }

    private function formulario(int $id): void
    {
        $u = $id ? linha('SELECT * FROM unidade WHERE id = ?', [$id]) : null;
        if ($id && !$u) { http_response_code(404); require dirname(__DIR__, 2) . '/views/404.php'; return; }
        $u = $u ?: ['nome' => '', 'nome_planilha' => '', 'tipo_id' => '', 'gestao' => '', 'endereco' => '', 'situacao' => 'ativa', 'periodicidade_meses' => 6, 'observacao' => ''];
        $this->render('unidades/form', ['titulo' => 'Cadastro de unidade', 'id' => $id, 'u' => $u], 'unidades');
    }

    /** Gráfico de evolução da nota (SVG). */
    private static function grafico(array $pts): string
    {
        if (count($pts) < 2) return '<p class="mini">A evolução aparece a partir da segunda visita registrada.</p>';
        $w = 1000; $h = 220; $m = 34; $ml = 70; $mr = 50;
        $n = count($pts);
        $x = fn($i) => $ml + ($w - $ml - $mr) * $i / ($n - 1);
        $y = fn($v) => $h - $m + 6 - ($h - 2 * $m) * $v;
        $s = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" style="width:100%">';
        foreach ([0, .4, .8, 1] as $g) {
            $s .= '<line x1="' . $ml . '" x2="' . ($w - $mr) . '" y1="' . $y($g) . '" y2="' . $y($g) . '" stroke="#dfe4ea" />'
                . '<text x="2" y="' . ($y($g) + 4) . '" font-size="10" fill="#5b6878">' . ($g * 100) . '%</text>';
        }
        $l = '';
        foreach ($pts as $i => $pp) $l .= ($i ? ' L' : 'M') . $x($i) . ' ' . $y((float)$pp['nota_final']);
        $s .= '<path d="' . $l . '" fill="none" stroke="#0f5c8c" stroke-width="2.5" />';
        $cores = ['verde' => '#2e8b57', 'amarelo' => '#c99400', 'vermelho' => '#c0392b', 'cinza' => '#8a96a3'];
        foreach ($pts as $i => $pp) {
            $s .= '<circle cx="' . $x($i) . '" cy="' . $y((float)$pp['nota_final']) . '" r="5" fill="' . $cores[farol($pp['nota_final'])] . '" />'
                . '<text x="' . $x($i) . '" y="' . ($y((float)$pp['nota_final']) - 10) . '" font-size="13" font-weight="600" text-anchor="middle">' . pct($pp['nota_final'], 0) . '</text>'
                . '<text x="' . $x($i) . '" y="' . ($h - 6) . '" font-size="10" text-anchor="middle" fill="#5b6878">' . date('m/Y', strtotime($pp['data_visita'])) . '</text>';
        }
        return $s . '</svg>';
    }
}
