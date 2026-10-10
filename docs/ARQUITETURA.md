# Arquitetura do CONVISITA

## Estado atual
PHP 8 procedural, ~19 páginas de topo; cada uma faz entrada + query + HTML inline.
Núcleo em `inc/` (conexão, helpers, auth, csrf). Regra de cálculo em views SQL
(`vw_resultado_visita`, `vw_dimensao_visita`, `vw_ultima_visita`, `vw_agenda`).
Sem router, sem classes, sem testes. Zero dependências. Roda em XAMPP e cPanel.

## Opções avaliadas
| Opção | Esforço | Risco | Ganho |
|---|---|---|---|
| 1. MVC-lite sem framework | Médio-baixo (2–4 dias) | Baixo | Separa camadas, testável, mantém deploy |
| 2. Micro-framework (Slim 4) | Médio-alto (1–2 sem) | Médio | Middleware, Twig; mas Composer no deploy, mais dependências |
| 3. Framework completo (Laravel) | Alto (semanas) | Alto | Ecossistema; desproporcional, briga com views SQL, pesado no cPanel |

## Decisão: Opção 1 — MVC-lite no PHP atual
Front controller + router + autoloader PSR-4 manual (zero dependência de runtime).
Controllers/Models/Views reaproveitando `inc/*` e as views SQL. Migração incremental
(strangler): páginas antigas seguem como fallback até migrarem. Composer só em dev (PHPUnit).

**Raiz web mantida (sem `public/`):** front controller e `.htaccess` na raiz; `src/`, `views/`,
`config.php` e `sql/` protegidos por `.htaccess` (como hoje). Evita mudar DocumentRoot no
XAMPP e no cPanel. App vive sob `/convisita` — o front controller remove o base path.

Pastas-alvo (na raiz atual): `src/{Core,Controllers,Models}`, `views/`, `tests/`,
`bootstrap.php`, `routes.php`; `inc/`, `sql/`, `assets/`, `config.php` inalterados de lugar.

## Layout: Bootstrap 5 via CDN
Sem build (Tailwind exigiria npm, incompatível com deploy por FTP). Aplicar onde paga:
navbar colapsável, `table-responsive`, grid de KPIs/filtros, modais de confirmação.
Preservar componentes customizados bons (faróis/selos/barras).

### Prioridade de UX: responsividade + acessibilidade
- Faróis não dependem só de cor: rótulo textual (adequada/atenção/crítica) + `aria-label`.
- Tabelas largas: `table-responsive`; esconder colunas secundárias no mobile.
- `<th scope>`, `<label for>`, foco visível, contraste AA, navegação por teclado nos selects de pontuação.

### Arquitetura de informação
Menu mantém: Painel · Visitas · Unidades · Agenda · Pendências · Comparativo · (dropdown) Instrumento e cadastros.
Fluxo principal: Painel → ficha da unidade → Registrar visita → formulário com nota ao vivo → volta ao Painel.
