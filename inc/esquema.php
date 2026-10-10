<?php
// Atualiza automaticamente bancos instalados com versoes anteriores do sistema.
// Executado uma vez por sessao (ver db() em base.php). Cada passo so roda se ainda for necessario.

function coluna_existe(PDO $pdo, string $tabela, string $coluna): bool
{
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $st->execute([$tabela, $coluna]);
    return (int)$st->fetchColumn() > 0;
}

function tabela_existe(PDO $pdo, string $tabela): bool
{
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $st->execute([$tabela]);
    return (int)$st->fetchColumn() > 0;
}

function garante_esquema(PDO $pdo): void
{
    // Migrações usam DDL (CREATE/ALTER). O usuário de runtime tem só DML (privilégio mínimo),
    // então aplicamos cada passo apenas quando falta e nunca derrubamos a página: se o DDL for
    // negado, registramos no log — rode instalar.php/upgrade com credencial administrativa.
    try {
        garante_esquema_ddl($pdo);
    } catch (PDOException $ex) {
        error_log('garante_esquema: migracao pendente nao aplicada (usuario sem DDL?): ' . $ex->getMessage());
    }
}

function garante_esquema_ddl(PDO $pdo): void
{
    // Tabela de parametros do sistema
    if (!tabela_existe($pdo, 'sistema')) {
        $pdo->exec('CREATE TABLE IF NOT EXISTS sistema (chave VARCHAR(60) PRIMARY KEY, valor TEXT NULL) ENGINE=InnoDB');
    }

    // Identificador unico das visitas (permite trocar dados entre computadores)
    if (!coluna_existe($pdo, 'visita', 'uuid')) {
        $pdo->exec('ALTER TABLE visita ADD COLUMN uuid VARCHAR(36) NULL UNIQUE AFTER id');
        // Visitas historicas recebem o mesmo identificador em todos os computadores
        $pdo->exec("UPDATE visita SET uuid = CONCAT('hist-', LPAD(id, 4, '0')), criado_em = '2026-10-03 00:00:00',
                           atualizado_em = atualizado_em WHERE origem LIKE 'Planilha%'");
    }
    $pdo->exec('UPDATE visita SET uuid = UUID(), atualizado_em = atualizado_em WHERE uuid IS NULL');

    // Pendencias: identificador unico e data de atualizacao
    if (!coluna_existe($pdo, 'pendencia', 'uuid')) {
        $pdo->exec('ALTER TABLE pendencia ADD COLUMN uuid VARCHAR(36) NULL UNIQUE AFTER id');
    }
    if (!coluna_existe($pdo, 'pendencia', 'atualizado_em')) {
        $pdo->exec('ALTER TABLE pendencia ADD COLUMN atualizado_em TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER criado_em');
    }
    $pdo->exec('UPDATE pendencia SET uuid = UUID(), atualizado_em = atualizado_em WHERE uuid IS NULL');
}
