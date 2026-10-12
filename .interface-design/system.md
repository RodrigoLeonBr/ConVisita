# Sistema de Design — CONVISITA

Base padrão do layout. Antes de mudar visual, consulte e mantenha coerência com o que está aqui.
Alterações só de estrutura visual — nunca SQL, autenticação ou regra de negócio.

## Intenção

- **Quem usa:** conselheiros e admins do Conselho Municipal de Saúde. Desktop no escritório, celular em campo.
- **O que fazem:** navegar módulos (painel, visitas, unidades, agenda, pendências), ler tabelas densas de notas/percentuais, registrar visitas.
- **Como deve sentir:** institucional, sóbrio, confiável (órgão público). Não lúdico. Denso o bastante para dados.

## Identidade

- **Framework:** Bootstrap 5.3.3 (CDN) + `assets/estilo.css`. Não trocar de framework sem combinar.
- **Tipografia:** stack do sistema — `"Segoe UI",Roboto,Arial,sans-serif`. 14px/1.5. Sem webfont.
- **Cor primária:** azul `#0f5c8c` (identidade do Conselho). Hover `#0a4468`.
- **Faróis (semânticos):** verde/amarelo/vermelho = conformidade. Usados em `.selo`, `.tag`, `.barra`. Cor = significado; não decorar.
- **Profundidade:** borders-only (1px `--linha`), raio 6–12px. Sem sombras decorativas. Cards = `.cartao`.

## Tokens (CSS custom properties em `assets/estilo.css`)

Tudo deriva destes. Nada de hex solto no código — use var ou adicione token.

| Token | Claro | Papel |
|---|---|---|
| `--fundo` | `#f4f6f8` | canvas / sidebar |
| `--cartao` | `#fff` | superfície (cards, tabelas) |
| `--texto` | `#1d2733` | texto principal |
| `--suave` | `#5b6878` | texto secundário / labels |
| `--linha` | `#dfe4ea` | bordas / separadores |
| `--primaria` / `--primaria-esc` | `#0f5c8c` / `#0a4468` | ação, destaque, item ativo |
| `--entrada` / `--entrada-linha` | `#fff` / `#c9d1da` | fundo e borda de campos de formulário |
| `--verde/-f` · `--amarelo/-f` · `--vermelho/-f` · `--cinza/-f` | — | faróis + fundo esmaecido do selo/tag |

**Tema escuro:** bloco `[data-bs-theme=dark]{...}` redefine os mesmos tokens (canvas `#0f1620`, superfície `#17202c`, azul clareado `#3b8fc4`). Componentes custom acompanham via var; o punhado de hex claro fixo (th, hover de linha, `.btn.sec`, `.dim-cab`, `.aviso.info`, texto amarelo) tem override explícito no bloco escuro. Componentes Bootstrap mudam sozinhos pelo atributo nativo `data-bs-theme`.

## Layout

- **Estrutura única:** `views/layout.php`. Todo o chrome (header, menu, tema, logos) vive só aqui.
- **Header** (`.topbar`): sempre visível. Hambúrguer (`#btn-menu`, 44px) + **logo do Conselho** (`logo.png`, 38px em container branco) + título "Conselho Municipal de Saúde — Visitas das Unidades" (`.titulo-topo`, some <768px) + **menu do usuário** à direita: avatar com iniciais (`.avatar`, cor `--primaria`) + nome (some <560px) + dropdown `<details class="usuario-menu">` com Alterar senha / Sair (fecha ao clicar fora ou Esc via `app.js`).
- **Rodapé da sidebar** (`.rodape-lat`): só o toggle de tema (usuário foi pro header).
- **Menu lateral** (`.barra-lat`): fixo ≥992px (`position:sticky`, 240px, mesmo fundo do canvas + borda). <992px vira off-canvas (`position:fixed`, `translateX(-100%)`).
- **Hambúrguer — dois estados simétricos** (classes em `body`, alternadas juntas): sem classe = {desktop aberto, mobile fechado}; `menu-recolhido`+`menu-aberto` = {desktop recolhido, mobile gaveta aberta}. Cada media query lê só a classe relevante. `.overlay` só no mobile.
- **Logo do sistema** (`logo_sistema.png`, `alt="CONVISITA…"`) no topo da sidebar (container branco, máx 180px) e no card de login (`.login-marca`, máx 260px).
- **Submenu "Instrumento e cadastros":** `<details class="submenu">` (colapsável nativo, sem JS). `open` automático quando a URL atual é um dos 4 subitens (instrumento/conselheiros/usuarios/backup). Caret `▾` gira via `[open]`.
- **main:** `max-width:1280px`, centralizado. Sem login: `.main-sozinho`.
- **Espaçamento:** base ~4px. Gaps usados: 8 / 12 / 16. Padding de card 16px.
- **Alvos de toque:** ≥44px em itens de menu, hambúrguer, botão de tema.

## Tema claro/escuro

- Chave única: atributo `data-bs-theme` em `<html>` (`light` | `dark`).
- Toggle: `#btn-tema` na sidebar. Ícone lua (→escuro) / sol (→claro) via `.ico-tema`.
- Persistência: `localStorage['tema']`. Padrão na 1ª visita: `prefers-color-scheme`.
- Anti-flash: script inline no `<head>` de `layout.php` aplica o tema antes do 1º paint.

## Acessibilidade

- `aria-current="page"` no item ativo; `aria-expanded` no hambúrguer (sincronizado com o estado real por largura); `<summary>` nativo já gere `aria-expanded` do submenu; `aria-pressed` no toggle de tema; `aria-controls`/`aria-label` nos botões.
- Fechar/recolher menu com `Esc`; no mobile, clicar no overlay ou num link fecha a gaveta.
- Foco de teclado sempre visível (`:focus-visible` outline 2px `--primaria`).
- Meta `viewport` presente. Contraste alvo WCAG AA (4.5:1) nos dois temas.

## Componentes custom (em `estilo.css`)

`.cartao` · `.grade/.g4/.g2` · `.kpi` · `.selo` (farol) · `.tag` (status) · `.barra` (progresso) · `.filtros` · `.btn`/`.btn.sec`/`.btn.perigo`/`.btn.peq` · `.item-aval`/`.notas` (form de visita) · `.dim-cab` · `.aviso`. Reusar estes; não reinventar.

## Arquivos do chrome

`views/layout.php` · `views/auth/login.php` · `assets/estilo.css` · `assets/app.js` · `logo.png` · `logo_sistema.png`.
