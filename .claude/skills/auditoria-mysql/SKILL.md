---
name: auditoria-mysql
description: Use ao auditar a camada de banco MySQL/MariaDB do CONVISITA — privilégio mínimo do usuário do app, prepared statements obrigatórios, backups, criptografia em repouso, logs de acesso. Também para "auditar banco", "revisar MySQL", "privilégios do usuário do banco".
---

# Auditoria de segurança — MySQL/MariaDB (CONVISITA)

Banco guarda dados de saúde (LGPD art. 11). Confidencialidade e integridade do dado = requisito.

## Privilégio mínimo do usuário do app
O app conecta com o usuário de `config.php`. Em produção ele NÃO deve ser `root`.
```sql
-- Inspeção (rode como admin):
SELECT CURRENT_USER(), USER();
SHOW GRANTS FOR CURRENT_USER();
```
Esperado: o usuário do app tem só `SELECT, INSERT, UPDATE, DELETE` no schema `convisita` — sem `DROP`, `ALTER`, `CREATE`, `GRANT`, `FILE`, nem `*.*`.
Exceção: `instalar.php` e `inc/esquema.php` fazem `CREATE/ALTER` — rodar essas etapas com credencial administrativa separada e temporária, não com o usuário do dia a dia. Achado crítico se o app roda como `root@localhost` sem senha em produção.

```sql
-- Provisão correta em produção (ajuste host/senha):
CREATE USER 'convisita_app'@'localhost' IDENTIFIED BY '<senha forte>';
GRANT SELECT, INSERT, UPDATE, DELETE ON convisita.* TO 'convisita_app'@'localhost';
FLUSH PRIVILEGES;
```

## Prepared statements obrigatórios
- Toda query do app via PDO com placeholders (`consulta/linha/executa`). `PDO::ATTR_EMULATE_PREPARES=false`.
- Proibida concatenação de valor de entrada em SQL. Ver skill `auditoria-php` A03.

## Backups
- Rotina `inc/backup.php` gera JSON em `backups/` (fora do versionamento).
- `backups/` não pode ser servível pela web — confirmar `.htaccess` de bloqueio (Deny/Require all denied).
- Backup contém dados pessoais: proteger em repouso (ver abaixo) e no transporte (só download autenticado admin).
- Teste de restauração periódico; reter cópia fora da máquina.

## Criptografia em repouso
- Produção (HostGator): verificar se o MySQL usa tablespace encryption (`SHOW VARIABLES LIKE 'innodb_encrypt%'`) ou, na ausência, criptografia de disco do provedor.
- Backups `.json` com dados de saúde: cifrar em repouso (ex.: GPG) se saírem da máquina.
- Conexão: exigir TLS entre app e banco quando não forem o mesmo host (`REQUIRE SSL` no usuário).

## Logs de acesso / auditoria
- MySQL `general_log` NÃO deve ficar ligado em produção (grava parâmetros = vazamento). `SHOW VARIABLES LIKE 'general_log';` deve ser OFF.
- Manter log de erro do MySQL e log de acesso do Apache, sem dados pessoais nas URLs.
- Para LGPD: registrar quem acessou/exportou dados (nível aplicação), sem gravar o conteúdo sensível no log.

## Varredura rápida
```sql
SELECT user, host FROM mysql.user;                  -- usuários existentes
SHOW GRANTS FOR 'convisita_app'@'localhost';        -- privilégios do app
SHOW VARIABLES LIKE 'general_log';                  -- deve ser OFF
SHOW VARIABLES LIKE 'innodb_encrypt%';              -- criptografia em repouso
```
