<?php
// Backup, restauracao e mesclagem de dados entre computadores.
//
// Formato do arquivo: JSON com metadados e o conteudo de cada tabela.
// Restaurar  = apaga os dados deste computador e grava os do arquivo (copia fiel).
// Mesclar    = traz do arquivo apenas o que e novo ou mais recente, sem apagar nada daqui.
//              Visitas e pendencias sao reconhecidas pelo identificador unico (uuid);
//              unidades, ciclos, tipos e conselheiros, pelo nome.

const BACKUP_FORMATO = 'convisita-backup';
const BACKUP_VERSAO = 2;

// Ordem de exportacao/insercao (respeita as chaves estrangeiras)
const BACKUP_TABELAS = ['tipo_unidade', 'dimensao', 'item_indicador', 'faixa_classificacao', 'ciclo', 'unidade',
    'conselheiro', 'usuario', 'visita', 'visita_conselheiro', 'avaliacao_item', 'comentario_dimensao', 'pendencia'];

function pasta_backups(): string
{
    $dir = __DIR__ . '/../backups';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    if (!is_file($dir . '/.htaccess')) {
        file_put_contents($dir . '/.htaccess', "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Order deny,allow\n  Deny from all\n</IfModule>\n");
        file_put_contents($dir . '/index.html', '');
    }
    return realpath($dir);
}

function parametro(string $chave, ?string $padrao = null): ?string
{
    $r = linha('SELECT valor FROM sistema WHERE chave = ?', [$chave]);
    return $r['valor'] ?? $padrao;
}
function grava_parametro(string $chave, string $valor): void
{
    executa('INSERT INTO sistema (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', [$chave, $valor]);
}
function nome_computador(): string
{
    return parametro('nome_computador') ?: (gethostname() ?: 'computador');
}

function contagem_atual(): array
{
    $c = [];
    foreach (['unidade' => 'Unidades', 'visita' => 'Visitas', 'avaliacao_item' => 'Notas de itens', 'pendencia' => 'Pendências',
              'conselheiro' => 'Conselheiros', 'usuario' => 'Usuários', 'ciclo' => 'Ciclos'] as $t => $rot) {
        $c[$rot] = (int)linha("SELECT COUNT(*) n FROM `$t`")['n'];
    }
    return $c;
}

/** Monta o conteudo completo do backup. */
function gera_backup(): array
{
    $dados = ['formato' => BACKUP_FORMATO, 'versao' => BACKUP_VERSAO, 'gerado_em' => date('Y-m-d H:i:s'),
              'computador' => nome_computador(), 'usuario' => usuario()['login'] ?? '', 'tabelas' => []];
    foreach (BACKUP_TABELAS as $t) {
        $dados['tabelas'][$t] = consulta("SELECT * FROM `$t`");
    }
    $dados['contagem'] = array_map('count', $dados['tabelas']);
    return $dados;
}

function nome_arquivo_seguro(string $s): string
{
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: 'computador';
    return trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-') ?: 'computador';
}

/** Grava o backup na pasta backups/ e devolve o nome do arquivo. */
function salva_backup(array $dados, string $motivo): string
{
    $nome = sprintf('convisita_%s_%s_%s.json', date('Ymd_His'), nome_arquivo_seguro($motivo), nome_arquivo_seguro($dados['computador'] ?? ''));
    file_put_contents(pasta_backups() . '/' . $nome, json_encode($dados, JSON_UNESCAPED_UNICODE));
    return $nome;
}

function caminho_backup(string $nome): ?string
{
    $nome = basename($nome);
    if (!preg_match('/^[A-Za-z0-9_.-]+\.json$/', $nome)) return null;
    $p = pasta_backups() . '/' . $nome;
    return is_file($p) ? $p : null;
}

function lista_backups(): array
{
    $l = [];
    foreach (glob(pasta_backups() . '/*.json') as $f) {
        $meta = ['arquivo' => basename($f), 'tamanho' => filesize($f), 'data' => filemtime($f), 'computador' => '', 'gerado_em' => '', 'visitas' => null];
        $j = json_decode((string)file_get_contents($f), true);
        if (is_array($j)) {
            $meta['computador'] = $j['computador'] ?? '';
            $meta['gerado_em'] = $j['gerado_em'] ?? '';
            $meta['visitas'] = $j['contagem']['visita'] ?? null;
        }
        $l[] = $meta;
    }
    usort($l, fn($a, $b) => $b['data'] <=> $a['data']);
    return $l;
}

/** Le e valida um arquivo de backup. Lanca excecao com mensagem amigavel se for invalido. */
function le_backup(string $caminho): array
{
    $j = json_decode((string)file_get_contents($caminho), true);
    if (!is_array($j) || ($j['formato'] ?? '') !== BACKUP_FORMATO) {
        throw new RuntimeException('O arquivo não é um backup do CONVISITA.');
    }
    if ((int)($j['versao'] ?? 0) > BACKUP_VERSAO) {
        throw new RuntimeException('O backup foi gerado por uma versão mais nova do sistema. Atualize o sistema deste computador antes de importar.');
    }
    foreach (['unidade', 'visita', 'avaliacao_item'] as $t) {
        if (!isset($j['tabelas'][$t]) || !is_array($j['tabelas'][$t])) {
            throw new RuntimeException("O backup está incompleto (falta a tabela $t).");
        }
    }
    // Backups antigos, sem identificador unico: aplica a mesma regra da atualizacao do esquema
    foreach ($j['tabelas']['visita'] as &$v) {
        if (empty($v['uuid'])) {
            $v['uuid'] = str_starts_with((string)($v['origem'] ?? ''), 'Planilha') ? sprintf('hist-%04d', $v['id']) : 'sem-uuid-' . md5(json_encode($v));
        }
    }
    unset($v);
    foreach ($j['tabelas']['pendencia'] ?? [] as $k => $p) {
        if (empty($p['uuid'])) $j['tabelas']['pendencia'][$k]['uuid'] = 'sem-uuid-' . md5(json_encode($p));
    }
    return $j;
}

function colunas_tabela(string $t): array
{
    static $cache = [];
    if (!isset($cache[$t])) {
        $cache[$t] = array_column(consulta('SELECT column_name AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ?', [$t]), 'c');
    }
    return $cache[$t];
}

function insere_linha(string $t, array $linha): int
{
    $cols = array_values(array_intersect(array_keys($linha), colunas_tabela($t)));
    $sql = "INSERT INTO `$t` (`" . implode('`,`', $cols) . '`) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
    executa($sql, array_map(fn($c) => $linha[$c], $cols));
    return (int)db()->lastInsertId();
}

// ---------------------------------------------------------------- RESTAURAR (substituir tudo)
function restaura_backup(array $b, bool $incluirUsuarios): array
{
    $pdo = db();
    $tabelas = array_filter(BACKUP_TABELAS, fn($t) => $incluirUsuarios || $t !== 'usuario');
    $pdo->beginTransaction();
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (array_reverse($tabelas) as $t) $pdo->exec("DELETE FROM `$t`");
        $cont = [];
        foreach ($tabelas as $t) {
            $cont[$t] = 0;
            foreach ($b['tabelas'][$t] ?? [] as $linha) { insere_linha($t, $linha); $cont[$t]++; }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        $pdo->commit();
        return $cont;
    } catch (Throwable $e) {
        $pdo->rollBack();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        throw $e;
    }
}

// ---------------------------------------------------------------- MESCLAR (trazer novidades)
function versao_registro(array $r): string
{
    return (string)(($r['atualizado_em'] ?? null) ?: ($r['criado_em'] ?? ''));
}

/**
 * Compara o arquivo com este computador. Com $executar = false apenas descreve o que sera feito;
 * com $executar = true grava as mudancas.
 */
function mescla_backup(array $b, bool $executar): array
{
    $T = $b['tabelas'];
    $r = ['novos' => [], 'atualizados' => [], 'ignorados' => 0, 'avisos' => [],
          'visitas_novas' => 0, 'visitas_atualizadas' => 0, 'pendencias_novas' => 0, 'pendencias_atualizadas' => 0];
    $pdo = db();
    if ($executar) $pdo->beginTransaction();
    try {
        // --- cadastros reconhecidos pelo nome (ou codigo)
        $mapTipo = [];
        foreach ($T['tipo_unidade'] ?? [] as $x) {
            $l = linha('SELECT id FROM tipo_unidade WHERE nome = ?', [$x['nome']]);
            if (!$l) {
                $r['novos'][] = 'Tipo de unidade: ' . $x['nome'];
                $mapTipo[$x['id']] = $executar ? insere_linha('tipo_unidade', ['nome' => $x['nome']]) : 0;
            } else $mapTipo[$x['id']] = (int)$l['id'];
        }
        $mapCiclo = [];
        foreach ($T['ciclo'] ?? [] as $x) {
            $l = linha('SELECT id FROM ciclo WHERE nome = ?', [$x['nome']]);
            if (!$l) {
                $r['novos'][] = 'Ciclo: ' . $x['nome'];
                $mapCiclo[$x['id']] = $executar ? insere_linha('ciclo', ['nome' => $x['nome'], 'data_inicio' => $x['data_inicio'], 'data_fim' => $x['data_fim']]) : 0;
            } else $mapCiclo[$x['id']] = (int)$l['id'];
        }
        $mapUnid = [];
        foreach ($T['unidade'] ?? [] as $x) {
            $l = linha('SELECT * FROM unidade WHERE nome = ?', [$x['nome']]);
            if (!$l) {
                $r['novos'][] = 'Unidade: ' . $x['nome'];
                $novo = $x; unset($novo['id']);
                $novo['tipo_id'] = $x['tipo_id'] ? ($mapTipo[$x['tipo_id']] ?? null) : null;
                $mapUnid[$x['id']] = $executar ? insere_linha('unidade', $novo) : 0;
            } else {
                $mapUnid[$x['id']] = (int)$l['id'];
                // completa apenas campos vazios aqui e preenchidos la (nao sobrescreve o que ja existe)
                $completa = [];
                foreach (['gestao', 'endereco', 'observacao', 'nome_planilha'] as $c) {
                    if (($l[$c] ?? '') === '' && ($x[$c] ?? '') !== '') $completa[$c] = $x[$c];
                }
                if ($completa) {
                    $r['atualizados'][] = 'Cadastro da unidade ' . $x['nome'] . ' (' . implode(', ', array_keys($completa)) . ')';
                    if ($executar) {
                        $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($completa)));
                        executa("UPDATE unidade SET $set WHERE id = ?", [...array_values($completa), $l['id']]);
                    }
                }
            }
        }
        $mapCons = [];
        foreach ($T['conselheiro'] ?? [] as $x) {
            $l = linha('SELECT id FROM conselheiro WHERE nome = ?', [$x['nome']]);
            if (!$l) {
                $r['novos'][] = 'Conselheiro: ' . $x['nome'];
                $mapCons[$x['id']] = $executar ? insere_linha('conselheiro', ['nome' => $x['nome'], 'segmento' => $x['segmento'] ?? null, 'ativo' => $x['ativo'] ?? 1]) : 0;
            } else $mapCons[$x['id']] = (int)$l['id'];
        }
        $dimCod = []; foreach ($T['dimensao'] ?? [] as $x) $dimCod[$x['id']] = $x['codigo'];
        $itemCod = []; foreach ($T['item_indicador'] ?? [] as $x) $itemCod[$x['id']] = $x['codigo'];
        $locDim = []; foreach (consulta('SELECT id, codigo FROM dimensao') as $x) $locDim[$x['codigo']] = (int)$x['id'];
        $locItem = []; foreach (consulta('SELECT id, codigo FROM item_indicador') as $x) $locItem[$x['codigo']] = (int)$x['id'];
        $nomeUnid = []; foreach ($T['unidade'] ?? [] as $x) $nomeUnid[$x['id']] = $x['nome'];

        // agrupa filhos das visitas do arquivo
        $filhos = ['avaliacao_item' => [], 'comentario_dimensao' => [], 'visita_conselheiro' => []];
        foreach ($filhos as $t => $_) foreach ($T[$t] ?? [] as $x) $filhos[$t][$x['visita_id']][] = $x;

        // --- visitas, pelo identificador unico
        $mapVisita = [];
        foreach ($T['visita'] as $v) {
            $loc = linha('SELECT * FROM visita WHERE uuid = ?', [$v['uuid']]);
            $rotulo = ($nomeUnid[$v['unidade_id']] ?? 'unidade ' . $v['unidade_id']) . ' em ' . data_br($v['data_visita']);
            $acao = !$loc ? 'nova' : (versao_registro($v) > versao_registro($loc) ? 'atualizar' : 'ignorar');
            if ($acao === 'ignorar') { $r['ignorados']++; $mapVisita[$v['id']] = (int)$loc['id']; continue; }
            if ($acao === 'nova') { $r['visitas_novas']++; $r['novos'][] = 'Visita: ' . $rotulo; }
            else { $r['visitas_atualizadas']++; $r['atualizados'][] = 'Visita: ' . $rotulo; }
            if (!$executar) { $mapVisita[$v['id']] = $loc ? (int)$loc['id'] : 0; continue; }

            $dados = $v;
            unset($dados['id']);
            $dados['unidade_id'] = $mapUnid[$v['unidade_id']] ?? null;
            $dados['ciclo_id'] = $mapCiclo[$v['ciclo_id']] ?? null;
            if (!$dados['unidade_id'] || !$dados['ciclo_id']) { $r['avisos'][] = "Visita $rotulo não importada: unidade ou ciclo não encontrado."; continue; }
            if ($acao === 'nova') {
                $idLocal = insere_linha('visita', $dados);
            } else {
                $idLocal = (int)$loc['id'];
                $cols = array_values(array_intersect(array_keys($dados), colunas_tabela('visita')));
                $cols = array_diff($cols, ['uuid']);
                $set = implode(', ', array_map(fn($c) => "`$c` = ?", $cols));
                executa("UPDATE visita SET $set WHERE id = ?", [...array_map(fn($c) => $dados[$c], $cols), $idLocal]);
                foreach (['avaliacao_item', 'comentario_dimensao', 'visita_conselheiro'] as $t) executa("DELETE FROM `$t` WHERE visita_id = ?", [$idLocal]);
            }
            $mapVisita[$v['id']] = $idLocal;
            foreach ($filhos['avaliacao_item'][$v['id']] ?? [] as $a) {
                $it = $locItem[$itemCod[$a['item_id']] ?? ''] ?? null;
                if (!$it) { $r['avisos'][] = "Item {$a['item_id']} da visita $rotulo não existe neste computador."; continue; }
                insere_linha('avaliacao_item', ['visita_id' => $idLocal, 'item_id' => $it, 'pontuacao' => $a['pontuacao'], 'nao_se_aplica' => $a['nao_se_aplica'], 'observacao' => $a['observacao'] ?? null]);
            }
            foreach ($filhos['comentario_dimensao'][$v['id']] ?? [] as $c) {
                $d = $locDim[$dimCod[$c['dimensao_id']] ?? ''] ?? null;
                if ($d) insere_linha('comentario_dimensao', ['visita_id' => $idLocal, 'dimensao_id' => $d, 'texto' => $c['texto']]);
            }
            foreach ($filhos['visita_conselheiro'][$v['id']] ?? [] as $c) {
                if (!empty($mapCons[$c['conselheiro_id']])) insere_linha('visita_conselheiro', ['visita_id' => $idLocal, 'conselheiro_id' => $mapCons[$c['conselheiro_id']]]);
            }
        }

        // --- pendencias, pelo identificador unico
        foreach ($T['pendencia'] ?? [] as $p) {
            $loc = linha('SELECT * FROM pendencia WHERE uuid = ?', [$p['uuid']]);
            $rotulo = ($nomeUnid[$p['unidade_id']] ?? '') . ': ' . mb_strimwidth((string)$p['descricao'], 0, 70, '...');
            $acao = !$loc ? 'nova' : (versao_registro($p) > versao_registro($loc) ? 'atualizar' : 'ignorar');
            if ($acao === 'ignorar') { $r['ignorados']++; continue; }
            if ($acao === 'nova') { $r['pendencias_novas']++; $r['novos'][] = 'Pendência: ' . $rotulo; }
            else { $r['pendencias_atualizadas']++; $r['atualizados'][] = 'Pendência: ' . $rotulo; }
            if (!$executar) continue;
            $dados = $p;
            unset($dados['id']);
            $dados['unidade_id'] = $mapUnid[$p['unidade_id']] ?? null;
            $dados['visita_id'] = $p['visita_id'] ? ($mapVisita[$p['visita_id']] ?? null) : null;
            $dados['dimensao_id'] = $p['dimensao_id'] ? ($locDim[$dimCod[$p['dimensao_id']] ?? ''] ?? null) : null;
            if (!$dados['unidade_id']) { $r['avisos'][] = "Pendência $rotulo não importada: unidade não encontrada."; continue; }
            if ($acao === 'nova') insere_linha('pendencia', $dados);
            else {
                $cols = array_diff(array_values(array_intersect(array_keys($dados), colunas_tabela('pendencia'))), ['uuid']);
                $set = implode(', ', array_map(fn($c) => "`$c` = ?", $cols));
                executa("UPDATE pendencia SET $set WHERE id = ?", [...array_map(fn($c) => $dados[$c], $cols), $loc['id']]);
            }
        }
        if ($executar) $pdo->commit();
    } catch (Throwable $e) {
        if ($executar && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
    return $r;
}
