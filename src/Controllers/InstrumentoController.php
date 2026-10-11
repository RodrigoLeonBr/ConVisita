<?php
declare(strict_types=1);
namespace Convisita\Controllers;
use Convisita\Core\Controller;

final class InstrumentoController extends Controller
{
    public function index(array $p): void
    {
        $this->exigeLogin();
        $this->render('instrumento/index', [
            'titulo' => 'Instrumento e cadastros',
            'dims' => consulta('SELECT * FROM dimensao ORDER BY ordem'),
            'itens' => consulta('SELECT * FROM item_indicador ORDER BY ordem'),
            'adm' => e_admin(),
            'editItem' => get('item') ? linha('SELECT * FROM item_indicador WHERE id = ?', [(int)get('item')]) : null,
        ], 'cadastros');
    }

    public function salvar(array $p): void
    {
        $this->exigeLogin();
        confere_csrf();
        $this->exigeAdmin();
        $acao = post('acao');
        if ($acao === 'pesos') {
            $pesos = array_map('floatval', (array)($_POST['peso'] ?? []));
            if (abs(array_sum($pesos) - 1) > 0.0001) aviso('A soma dos pesos deve ser 100%. Nada foi alterado.', 'erro');
            else { foreach ($pesos as $id => $v) executa('UPDATE dimensao SET peso = ? WHERE id = ?', [$v, (int)$id]); aviso('Pesos atualizados.'); }
        } elseif ($acao === 'faixas') {
            foreach ((array)($_POST['li'] ?? []) as $id => $li) executa('UPDATE faixa_classificacao SET limite_inferior=?, limite_superior=?, rotulo=? WHERE id=?',
                [(float)$li, (float)$_POST['ls'][$id], trim((string)$_POST['rot'][$id]), (int)$id]);
            aviso('Faixas atualizadas.');
        } elseif ($acao === 'item') {
            executa('UPDATE item_indicador SET nome=?, meta=?, fonte=?, periodicidade=?, ativo=? WHERE id=?',
                [post('nome'), post('meta'), nulo(post('fonte')), nulo(post('periodicidade')), post('ativo') ? 1 : 0, (int)post('id')]);
            aviso('Item atualizado.');
        } elseif ($acao === 'ciclo') {
            try { executa('INSERT INTO ciclo (nome, data_inicio, data_fim) VALUES (?,?,?)', [post('nome'), post('inicio'), post('fim')]); aviso('Ciclo criado.'); }
            catch (\PDOException $e) { aviso('Não foi possível criar o ciclo (nome repetido?).', 'erro'); }
        } elseif ($acao === 'tipo') {
            try { executa('INSERT INTO tipo_unidade (nome) VALUES (?)', [post('nome')]); aviso('Tipo criado.'); }
            catch (\PDOException $e) { aviso('Tipo já existe.', 'erro'); }
        }
        $this->redireciona(BASE . '/instrumento');
    }
}
