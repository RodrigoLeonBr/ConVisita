<?php
// Nucleo do sistema: conexao, sessao, autenticacao e funcoes de apoio
declare(strict_types=1);
date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

// Ambiente local (XAMPP/rede interna) vê erros; produção (hospedagem) não, para não vazar stack trace.
function e_local(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return $host === '' // CLI
        || preg_match('/^(localhost|127\.0\.0\.1|192\.168\.|10\.)/', $host) === 1;
}
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', e_local() ? '1' : '0');

// Cabeçalhos de segurança (CSP omitido: o app usa handlers/estilos inline; adicionar ao mover JS inline para assets).
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('convisita');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $https]);
    session_start();
}

function cfg(string $k)
{
    static $c = null;
    if ($c === null) $c = require __DIR__ . '/../config.php';
    return $c[$k] ?? null;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', cfg('db_host'), cfg('db_porta'), cfg('db_nome'));
        try {
            $pdo = new PDO($dsn, cfg('db_usuario'), cfg('db_senha'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $ex) {
            http_response_code(500);
            echo '<p style="font-family:sans-serif">Não foi possível conectar ao banco <b>' . e((string)cfg('db_nome'))
               . '</b>. Verifique se o MySQL do XAMPP está ligado e se o banco foi instalado '
               . '(<a href="instalar.php">instalar.php</a>).</p>';
            exit;
        }
        if (empty($_SESSION['esquema_v2'])) {
            require_once __DIR__ . '/esquema.php';
            garante_esquema($pdo);
            $_SESSION['esquema_v2'] = 1;
        }
    }
    return $pdo;
}

/** Identificador unico (UUID versao 4), usado para reconhecer o mesmo registro em computadores diferentes. */
function novo_uuid(): string
{
    $b = random_bytes(16);
    $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
    $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

function consulta(string $sql, array $p = []): array
{
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st->fetchAll();
}
function linha(string $sql, array $p = []): ?array
{
    $r = consulta($sql, $p);
    return $r[0] ?? null;
}
function executa(string $sql, array $p = []): int
{
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st->rowCount();
}

// ---------------------------------------------------------------- formatacao
function e($s): string { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
function pct($x, int $casas = 1): string
{
    if ($x === null || $x === '') return '–';
    return number_format((float)$x * 100, $casas, ',', '.') . '%';
}
function data_br($d): string
{
    if (!$d) return '–';
    $t = strtotime((string)$d);
    return $t ? date('d/m/Y', $t) : (string)$d;
}
function faixas(): array
{
    static $f = null;
    if ($f === null) $f = consulta('SELECT * FROM faixa_classificacao ORDER BY limite_inferior');
    return $f;
}
function farol($x): string
{
    if ($x === null || $x === '') return 'cinza';
    foreach (faixas() as $f) {
        if ((float)$x >= (float)$f['limite_inferior'] && (float)$x < (float)$f['limite_superior']) return $f['cor'];
    }
    return 'cinza';
}
function selo($x, bool $comTexto = true): string
{
    $c = farol($x);
    return '<span class="selo ' . $c . '">' . ($comTexto ? pct($x) : '&nbsp;') . '</span>';
}
function barra($x): string
{
    if ($x === null) return '<div class="barra vazia"><span>não avaliada</span></div>';
    $w = max(0, min(100, (float)$x * 100));
    return '<div class="barra"><i class="' . farol($x) . '" style="width:' . $w . '%"></i><span>' . pct($x) . '</span></div>';
}
const ROTULO_SITUACAO = [
    'rascunho' => 'Rascunho', 'concluida' => 'Concluída', 'incompleta' => 'Incompleta', 'em_conferencia' => 'Em conferência',
    'sem_visita' => 'Sem visita', 'atrasada' => 'Atrasada', 'proximos_30_dias' => 'Próximos 30 dias', 'em_dia' => 'Em dia',
    'suspensa' => 'Suspensa', 'aberta' => 'Aberta', 'em_andamento' => 'Em andamento', 'resolvida' => 'Resolvida',
    'cancelada' => 'Cancelada', 'ativa' => 'Ativa', 'em_reforma' => 'Em reforma', 'inativa' => 'Inativa',
    'ordinaria' => 'Ordinária', 'extraordinaria' => 'Extraordinária',
];
function rotulo(?string $s): string { return e(ROTULO_SITUACAO[$s] ?? (string)$s); }

// ---------------------------------------------------------------- autenticacao
function usuario(): ?array { return $_SESSION['usuario'] ?? null; }
function exige_login(): void
{
    if (!usuario()) { header('Location: ' . (defined('BASE') ? BASE : '') . '/login?volta=' . urlencode($_SERVER['REQUEST_URI'] ?? '')); exit; }
}
function pode_editar(): bool { return in_array(usuario()['perfil'] ?? '', ['admin', 'conselheiro'], true); }
function e_admin(): bool { return (usuario()['perfil'] ?? '') === 'admin'; }
function exige_edicao(): void { if (!pode_editar()) { http_response_code(403); exit('Acesso restrito a conselheiros e administradores.'); } }
function exige_admin(): void { if (!e_admin()) { http_response_code(403); exit('Acesso restrito ao administrador.'); } }

function csrf(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}
function confere_csrf(): void
{
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? null)) { http_response_code(400); exit('Formulário expirado. Volte e tente novamente.'); }
}
function aviso(string $msg, string $tipo = 'ok'): void { $_SESSION['avisos'][] = [$tipo, $msg]; }
function redireciona(string $url): void { header('Location: ' . $url); exit; }
function post(string $k, $padrao = null) { $v = $_POST[$k] ?? $padrao; return is_string($v) ? trim($v) : $v; }
function get(string $k, $padrao = null) { $v = $_GET[$k] ?? $padrao; return is_string($v) ? trim($v) : $v; }
function nulo($v) { return ($v === '' || $v === null) ? null : $v; }

function ciclos(): array { return consulta('SELECT * FROM ciclo ORDER BY data_inicio DESC'); }
function opcoes(array $linhas, string $chave, string $texto, $selecionado, string $vazio = ''): string
{
    $h = $vazio !== '' ? '<option value="">' . e($vazio) . '</option>' : '';
    foreach ($linhas as $l) {
        $s = ((string)$l[$chave] === (string)$selecionado) ? ' selected' : '';
        $h .= '<option value="' . e($l[$chave]) . '"' . $s . '>' . e($l[$texto]) . '</option>';
    }
    return $h;
}
