# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Language

Code, comments, UI strings, commit messages and docs are all in **Brazilian Portuguese**. Keep new code consistent — variable/function names in Portuguese (e.g. `consulta`, `exige_login`, `pode_editar`), accented UTF-8 throughout.

## Stack & running

PHP 8 + MySQL/MariaDB, served by Apache under XAMPP. Hand-rolled MVC (front controller + router), **no third-party runtime framework, no npm, no build step** — edit a `.php`/`.css`/`.js` file and reload the browser. Composer is a **dev-only** dependency (PHPUnit); the app registers its own PSR-4 autoloader in `bootstrap.php` and does **not** need `vendor/` at runtime. Frontend is plain CSS/JS with Bootstrap 5.3 loaded from CDN.

- Run locally: start Apache + MySQL in XAMPP, then open `http://localhost/convisita`. App root is `E:\xampp\htdocs\convisita`.
- First-time DB setup: `http://localhost/convisita/instalar.php` → "Instalar agora" (creates `convisita` DB, runs `sql/01`→`02`→`03`). Login `admin` / `comsaude`.
- DB connection lives in `config.php` (returns a PHP array). Default is XAMPP root/no-password. The installer is blocked after first run unless `permitir_reinstalar` is `true`.
- SQL files in `sql/` import in numeric order via phpMyAdmin if needed.
- Run tests: `composer install` once, then `vendor/bin/phpunit` (suite in `tests/`, bootstraps `vendor/autoload.php`).

## Architecture

**Lightweight hand-rolled MVC.** Single front controller `index.php` → `bootstrap.php` (requires `inc/base.php` + `inc/consultas.php`, registers the PSR-4 autoloader for `Convisita\` → `src/`). `index.php` defines `BASE` (the app's URL subpath, e.g. `/convisita`), parses the request path, looks it up in `routes.php`, and dispatches through `src/Core/Router` to `(new Controller())->acao($params)`.

- `routes.php` — the route table: `'METHOD /path'` (with `{id}` placeholders) → `[Controller::class, 'acao']`.
- `src/Core/` — `Router` (regex match + named params), `Controller` (base; `render($template, $dados, $menu)`), `View` (renders `views/<template>.php` into `$conteudo`, then wraps it in `views/layout.php`).
- `src/Controllers/` — one per area (Painel, Visita, Unidade, Agenda, Pendencia, Comparativo, Instrumento, Conselheiro, Usuario, Export, Backup, Auth). Each action gates access, handles POST, then calls `render()`.
- `src/Models/` — domain logic (e.g. `Visita`).
- `views/` — PHP templates grouped by area; `views/layout.php` is the **single shell** (sidebar nav, light/dark theme toggle, logos). The controller's `$menu` string marks the active nav item. Frontend assets: `assets/estilo.css` + `assets/app.js`; design base documented in `.interface-design/system.md`.

`inc/` is the shared, non-OO core (still required on every request via `bootstrap.php`):
- `base.php` — bootstraps session/timezone/encoding; `db()` returns a PDO singleton (prepared statements, exceptions, utf8mb4); query helpers `consulta()` / `linha()` / `executa()`; formatting helpers `e()` (html-escape — use on all output), `pct()`, `data_br()`, `farol()`/`selo()`/`barra()` (red-yellow-green status from a score); auth helpers (`usuario()`, `exige_login()`/`exige_edicao()`/`exige_admin()`, `pode_editar()`/`e_admin()`, `csrf()`/`confere_csrf()`); `novo_uuid()`.
- `esquema.php` — **auto-migration**. `garante_esquema()` runs once per session (gated by `$_SESSION['esquema_v2']` in `db()`) and applies idempotent `ALTER`/`CREATE IF NOT EXISTS` steps. When you change the schema, add a guarded step here **and** update the `sql/` files, so both fresh installs and existing DBs (local + HostGator) converge.
- `consultas.php` — queries shared across painel/comparativo/exportar.
- `backup.php` — JSON export / merge / restore between machines (see below).

**Scoring logic lives in SQL views, not PHP** (`sql/01_estrutura.sql`):
- `vw_dimensao_visita` — per-dimension percentage; items marked `nao_se_aplica` are dropped from the denominator.
- `vw_resultado_visita` — per-visit result; `nota_final` is the weighted mean of evaluated dimensions with weights redistributed; `nota_metodo_planilha` reproduces the original spreadsheet (unevaluated dimension counts as zero) for comparison.
- `vw_ultima_visita` / `vw_agenda` — latest valid visit per unit and next-due date (`ultima_visita + periodicidade_meses`). "Valid" = situacao in `('concluida','incompleta')`; drafts/`em_conferencia` are excluded everywhere.

## Auth & security conventions

- Session login. Profiles: `admin` (everything), `conselheiro` (records visits/pendencias), `consulta` (read-only).
- Gate every controller action (at the top of the action): `exige_login()`, `exige_edicao()`, `exige_admin()`. Check `pode_editar()` / `e_admin()` before rendering edit controls.
- **Every POST form** must emit `csrf()` and the handler must call `confere_csrf()` first.
- Always use parameterized queries via the helpers, and `e()` on everything rendered.

## Sync model (important for schema changes)

Local XAMPP and the HostGator copy exchange data through `inc/backup.php` JSON files, not a shared DB. Records are matched by **UUID** (visitas, pendencias) or by **name** (unidades, ciclos, tipos, conselheiros); latest `atualizado_em` wins on conflict; deletions do not propagate. Any new table meant to sync needs a UUID column (added via `esquema.php`) and handling in `backup.php`.

## Deploy (HostGator)

Package is `deploy/convisita_hostgator.zip` (excludes docs/planilha/ferramentas/backups; `vendor/` and `tests/` are dev-only and not needed at runtime). On the server, **never overwrite the server's `config.php`** — it holds the production DB credentials. Schema changes apply automatically on first access per session via `esquema.php`. The server `.htaccess` forces HTTPS; that rule must not come back to XAMPP (no local cert).

## Other directories

- `src/` — OO code (`Core/`, `Controllers/`, `Models/`); `views/` — templates; `tests/` — PHPUnit suite; `.interface-design/system.md` — frontend design base.
- `docs/` — technical docs (`DICIONARIO_DE_DADOS.md`, `REQUISITOS_DO_SISTEMA.md`, `ACHADOS_E_ENTENDIMENTOS.md`) and the user manual. Read `DICIONARIO_DE_DADOS.md` for the full data model.
- `ferramentas/` — one-off Python scripts that extracted the original spreadsheet into `sql/03_carga_historica.sql`. Not part of the running app.
- `planilha/` — the original spreadsheet (source of the historical data), preserved.
- `.htaccess` blocks browser access to `config.php` and `.sql/.py/.md/.json/.xlsx` files; subfolders have their own blocking `.htaccess`.
