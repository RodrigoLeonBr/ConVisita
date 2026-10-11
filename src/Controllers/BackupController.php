<?php
declare(strict_types=1);
namespace Convisita\Controllers;
use Convisita\Core\Controller;

final class BackupController extends Controller
{
    private const MODOS = ['mesclar' => 'Mesclar (trazer novidades de outro computador)', 'restaurar' => 'Restaurar (substituir todos os dados deste computador)'];

    public function index(array $p): void
    {
        $this->exigeLogin();
        $this->exigeAdmin();
        require_once dirname(__DIR__, 2) . '/inc/backup.php';

        // Downloads: saem antes de qualquer saída HTML
        if (get('baixar')) {
            $dados = gera_backup();
            $nome = salva_backup($dados, 'manual');
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $nome . '"');
            readfile(pasta_backups() . '/' . $nome);
            exit;
        }
        if ($arq = get('download')) {
            $cam = caminho_backup((string)$arq);
            if (!$cam) { http_response_code(404); exit('Arquivo não encontrado.'); }
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . basename($cam) . '"');
            readfile($cam);
            exit;
        }

        $analise = null;
        if ($arq = get('analisar')) {
            $cam = caminho_backup((string)$arq);
            $modo = isset(self::MODOS[get('modo')]) ? get('modo') : 'mesclar';
            try {
                if (!$cam) throw new \RuntimeException('Arquivo não encontrado.');
                $b = le_backup($cam);
                $analise = ['arquivo' => basename($cam), 'modo' => $modo, 'b' => $b, 'plano' => $modo === 'mesclar' ? mescla_backup($b, false) : null];
            } catch (\Throwable $e) {
                aviso('Não foi possível ler o arquivo: ' . $e->getMessage(), 'erro');
            }
        }

        $this->render('backup/index', [
            'titulo' => 'Backup e sincronização', 'analise' => $analise, 'cont' => contagem_atual(), 'MODOS' => self::MODOS,
        ], 'cadastros');
    }

    public function salvar(array $p): void
    {
        $this->exigeLogin();
        $this->exigeAdmin();
        confere_csrf();
        require_once dirname(__DIR__, 2) . '/inc/backup.php';
        $acao = post('acao');
        try {
            if ($acao === 'nome_computador') {
                $n = trim((string)post('nome'));
                if ($n !== '') { grava_parametro('nome_computador', mb_substr($n, 0, 60)); aviso('Nome do computador gravado.'); }
                $this->redireciona(BASE . '/backup');
            }
            if ($acao === 'enviar') {
                $f = $_FILES['arquivo'] ?? null;
                if (!$f || $f['error'] !== UPLOAD_ERR_OK) throw new \RuntimeException('Selecione um arquivo de backup (.json) para enviar.');
                $tmp = $f['tmp_name'];
                $b = le_backup($tmp);
                $nome = sprintf('convisita_%s_recebido_%s.json', date('Ymd_His'), nome_arquivo_seguro($b['computador'] ?? 'outro'));
                move_uploaded_file($tmp, pasta_backups() . '/' . $nome);
                $modo = isset(self::MODOS[post('modo')]) ? post('modo') : 'mesclar';
                $this->redireciona(BASE . '/backup?analisar=' . urlencode($nome) . '&modo=' . $modo);
            }
            if ($acao === 'executar') {
                $cam = caminho_backup((string)post('arquivo'));
                if (!$cam) throw new \RuntimeException('Arquivo de backup não encontrado.');
                $b = le_backup($cam);
                $modo = post('modo');
                if ($modo === 'restaurar' && post('confirmo') !== '1') throw new \RuntimeException('Marque a confirmação para restaurar o backup.');
                $seg = salva_backup(gera_backup(), 'antes-de-' . $modo);
                if ($modo === 'restaurar') {
                    $c = restaura_backup($b, post('usuarios') === '1');
                    aviso(sprintf('Backup restaurado: %d visitas, %d unidades e %d pendências. Os dados anteriores foram guardados em %s.',
                        $c['visita'], $c['unidade'], $c['pendencia'] ?? 0, $seg));
                } else {
                    $r = mescla_backup($b, true);
                    aviso(sprintf('Mesclagem concluída: %d visita(s) nova(s), %d atualizada(s), %d pendência(s) nova(s), %d atualizada(s) e %d outro(s) cadastro(s) novo(s). Cópia de segurança anterior: %s.',
                        $r['visitas_novas'], $r['visitas_atualizadas'], $r['pendencias_novas'], $r['pendencias_atualizadas'],
                        count($r['novos']) - $r['visitas_novas'] - $r['pendencias_novas'], $seg));
                    foreach (array_slice($r['avisos'], 0, 10) as $a) aviso($a, 'erro');
                }
                unset($_SESSION['esquema_v2']);
                $this->redireciona(BASE . '/backup');
            }
            if ($acao === 'excluir') {
                $cam = caminho_backup((string)post('arquivo'));
                if ($cam) { unlink($cam); aviso('Arquivo excluído.'); }
                $this->redireciona(BASE . '/backup');
            }
        } catch (\Throwable $e) {
            aviso('Não foi possível concluir: ' . $e->getMessage(), 'erro');
            $this->redireciona(BASE . '/backup');
        }
        $this->redireciona(BASE . '/backup');
    }
}
