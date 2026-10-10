<?php
declare(strict_types=1);
namespace Convisita\Controllers;
use Convisita\Core\Controller;

final class VisitaController extends Controller
{
    public function lista(array $p): void
    {
        $this->exigeLogin();
        $f = ['ciclo' => (int)get('ciclo', 0), 'unidade' => (int)get('unidade', 0), 'situacao' => (string)get('situacao', '')];
        $w = []; $q = [];
        if ($f['ciclo'])    { $w[] = 'r.ciclo_id = ?';   $q[] = $f['ciclo']; }
        if ($f['unidade'])  { $w[] = 'r.unidade_id = ?'; $q[] = $f['unidade']; }
        if ($f['situacao'] !== '') { $w[] = 'r.situacao = ?'; $q[] = $f['situacao']; }
        $lista = consulta('SELECT r.* FROM vw_resultado_visita r' . ($w ? ' WHERE ' . implode(' AND ', $w) : '')
            . ' ORDER BY r.data_visita DESC, r.unidade', $q);
        $this->render('visitas/lista', ['titulo' => 'Visitas', 'lista' => $lista, 'f' => $f], 'visitas');
    }

    public function ver(array $p): void
    {
        $this->exigeLogin();
        $id = (int)($p['id'] ?? 0);
        $r = linha('SELECT r.*, v.relato, v.origem, v.observacao_importacao, v.unidade_id
                      FROM vw_resultado_visita r JOIN visita v ON v.id = r.visita_id WHERE r.visita_id = ?', [$id]);
        if (!$r) { http_response_code(404); require dirname(__DIR__, 2) . '/views/404.php'; return; }
        $dims = consulta('SELECT dv.*, cd.texto AS comentario FROM vw_dimensao_visita dv
                           LEFT JOIN comentario_dimensao cd ON cd.visita_id = dv.visita_id AND cd.dimensao_id = dv.dimensao_id
                          WHERE dv.visita_id = ? ORDER BY dv.dimensao_id', [$id]);
        $itens = consulta('SELECT i.*, a.pontuacao, a.nao_se_aplica, a.observacao FROM item_indicador i
                            LEFT JOIN avaliacao_item a ON a.item_id = i.id AND a.visita_id = ?
                           WHERE i.ativo = 1 ORDER BY i.ordem', [$id]);
        $porDim = [];
        foreach ($itens as $i) $porDim[$i['dimensao_id']][] = $i;
        $conselheiros = consulta('SELECT c.nome FROM visita_conselheiro vc JOIN conselheiro c ON c.id = vc.conselheiro_id WHERE vc.visita_id = ? ORDER BY c.nome', [$id]);
        $pend = consulta('SELECT * FROM pendencia WHERE visita_id = ? ORDER BY id', [$id]);
        $ant = linha("SELECT r.* FROM vw_resultado_visita r WHERE r.unidade_id = ? AND r.data_visita < ? AND r.situacao IN ('concluida','incompleta')
                       ORDER BY r.data_visita DESC LIMIT 1", [$r['unidade_id'], $r['data_visita']]);
        $this->render('visitas/ficha', [
            'titulo' => 'Visita ' . $r['unidade'], 'id' => $id, 'r' => $r, 'dims' => $dims,
            'porDim' => $porDim, 'conselheiros' => $conselheiros, 'pend' => $pend, 'ant' => $ant,
        ], 'visitas');
    }

    public function novo(array $p): void   { $this->exigeLogin(); $this->exigeEdicao(); $this->formulario(0); }
    public function editar(array $p): void  { $this->exigeLogin(); $this->exigeEdicao(); $this->formulario((int)($p['id'] ?? 0)); }

    public function salvar(array $p): void
    {
        $this->exigeLogin();
        $this->exigeEdicao();
        confere_csrf();
        $id = (int)post('id');
        $v = $id ? linha('SELECT * FROM visita WHERE id = ?', [$id]) : null;

        if (post('acao') === 'excluir' && $v) {
            exige_admin();
            executa('DELETE FROM visita WHERE id = ?', [$id]);
            aviso('Visita excluída.');
            $this->redireciona(BASE . '/visitas');
        }

        $itens = consulta('SELECT i.* FROM item_indicador i WHERE i.ativo = 1 ORDER BY i.ordem');
        $dims  = consulta('SELECT * FROM dimensao ORDER BY ordem');
        $data = post('data_visita');
        $unid = (int)post('unidade_id');
        $erros = [];
        if (!$unid) $erros[] = 'Selecione a unidade.';
        if (!$data || !strtotime($data)) $erros[] = 'Informe a data da visita.';
        if ($erros) {
            foreach ($erros as $er) aviso($er, 'erro');
            $this->redireciona($id ? BASE . '/visita/' . $id . '/editar' : BASE . '/visita/nova');
        }

        $cicloId = (int)post('ciclo_id');
        if (!$cicloId) {
            $c = linha('SELECT id FROM ciclo WHERE ? BETWEEN data_inicio AND data_fim ORDER BY data_inicio DESC LIMIT 1', [$data]);
            if ($c) $cicloId = (int)$c['id'];
            else {
                $ano = substr($data, 0, 4);
                executa('INSERT INTO ciclo (nome, data_inicio, data_fim) VALUES (?, ?, ?)', [$ano, "$ano-01-01", "$ano-12-31"]);
                $cicloId = (int)db()->lastInsertId();
            }
        }
        $notas = $_POST['nota'] ?? [];
        $faltam = 0;
        foreach ($itens as $i) { if (($notas[$i['id']] ?? '') === '') $faltam++; }
        $sit = post('situacao', 'finalizar');
        if ($sit === 'finalizar') $sit = $faltam ? 'incompleta' : 'concluida';
        if (!in_array($sit, ['rascunho', 'concluida', 'incompleta', 'em_conferencia'], true)) $sit = 'rascunho';

        $pdo = db();
        $pdo->beginTransaction();
        $campos = [$unid, $cicloId, $data, post('data_aproximada') ? 1 : 0, post('tipo') === 'extraordinaria' ? 'extraordinaria' : 'ordinaria', $sit, nulo(post('relato'))];
        if ($v) {
            executa('UPDATE visita SET unidade_id=?, ciclo_id=?, data_visita=?, data_aproximada=?, tipo=?, situacao=?, relato=?, atualizado_em=CURRENT_TIMESTAMP WHERE id=?', [...$campos, $id]);
        } else {
            executa("INSERT INTO visita (uuid, unidade_id, ciclo_id, data_visita, data_aproximada, tipo, situacao, relato, origem) VALUES (?,?,?,?,?,?,?,?, 'sistema')", [novo_uuid(), ...$campos]);
            $id = (int)$pdo->lastInsertId();
        }
        executa('DELETE FROM avaliacao_item WHERE visita_id = ?', [$id]);
        foreach ($itens as $i) {
            $n = (string)($notas[$i['id']] ?? '');
            $na = $n === 'NA' ? 1 : 0;
            $pt = ($n === '' || $na) ? null : max(0, min((int)$i['pontuacao_max'], (int)$n));
            executa('INSERT INTO avaliacao_item (visita_id, item_id, pontuacao, nao_se_aplica, observacao) VALUES (?,?,?,?,?)',
                [$id, $i['id'], $pt, $na, nulo(trim((string)($_POST['obs'][$i['id']] ?? '')))]);
        }
        executa('DELETE FROM comentario_dimensao WHERE visita_id = ?', [$id]);
        foreach ($dims as $d) {
            $t = trim((string)($_POST['comentario'][$d['id']] ?? ''));
            if ($t !== '') executa('INSERT INTO comentario_dimensao (visita_id, dimensao_id, texto) VALUES (?,?,?)', [$id, $d['id'], $t]);
        }
        executa('DELETE FROM visita_conselheiro WHERE visita_id = ?', [$id]);
        foreach ((array)($_POST['conselheiros'] ?? []) as $c) {
            executa('INSERT INTO visita_conselheiro (visita_id, conselheiro_id) VALUES (?,?)', [$id, (int)$c]);
        }
        $pdo->commit();
        aviso('Visita gravada' . ($sit === 'incompleta' ? " como incompleta ($faltam item(ns) sem pontuação)." : '.'));
        $this->redireciona(BASE . '/visita/' . $id);
    }

    private function formulario(int $id): void
    {
        $v = $id ? linha('SELECT * FROM visita WHERE id = ?', [$id]) : null;
        if ($id && !$v) { http_response_code(404); require dirname(__DIR__, 2) . '/views/404.php'; return; }
        $itens = consulta('SELECT i.*, d.codigo AS dcod FROM item_indicador i JOIN dimensao d ON d.id = i.dimensao_id WHERE i.ativo = 1 ORDER BY i.ordem');
        $dims  = consulta('SELECT * FROM dimensao ORDER BY ordem');
        $aval = []; $coment = []; $selCons = [];
        if ($v) {
            foreach (consulta('SELECT * FROM avaliacao_item WHERE visita_id = ?', [$id]) as $a) $aval[$a['item_id']] = $a;
            foreach (consulta('SELECT * FROM comentario_dimensao WHERE visita_id = ?', [$id]) as $c) $coment[$c['dimensao_id']] = $c['texto'];
            $selCons = array_column(consulta('SELECT conselheiro_id FROM visita_conselheiro WHERE visita_id = ?', [$id]), 'conselheiro_id');
        }
        $v = $v ?: ['unidade_id' => (int)get('unidade', 0), 'ciclo_id' => 0, 'data_visita' => date('Y-m-d'), 'data_aproximada' => 0,
                    'tipo' => 'ordinaria', 'situacao' => 'rascunho', 'relato' => ''];
        $pesos = [];
        foreach ($dims as $d) $pesos[$d['id']] = (float)$d['peso'];
        $fx = array_map(fn($f) => ['cor' => $f['cor'], 'li' => (float)$f['limite_inferior'], 'ls' => (float)$f['limite_superior']], faixas());
        $conselheiros = consulta('SELECT * FROM conselheiro WHERE ativo = 1 ORDER BY nome');
        $this->render('visitas/form', [
            'titulo' => $id ? 'Editar visita' : 'Nova visita', 'id' => $id, 'v' => $v, 'itens' => $itens, 'dims' => $dims,
            'aval' => $aval, 'coment' => $coment, 'selCons' => $selCons, 'pesos' => $pesos, 'fx' => $fx, 'conselheiros' => $conselheiros,
        ], 'visitas');
    }
}
