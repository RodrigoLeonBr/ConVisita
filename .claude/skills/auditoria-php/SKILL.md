---
name: auditoria-php
description: Use ao revisar, escrever ou auditar código PHP deste projeto quanto a segurança. Checklist OWASP Top 10 aplicado ao CONVISITA — SQL injection, XSS, CSRF, sessão/cookies, upload, hashing de senha, cabeçalhos. Também para "auditar PHP", "revisar segurança", "checklist OWASP".
---

# Auditoria de segurança — PHP (CONVISITA)

Sistema trata dados de saúde (sensíveis, LGPD art. 11). Falha de confidencialidade = incidente reportável. Trate cada item abaixo como requisito, não sugestão.

## Como usar
1. Rode a varredura automática (abaixo) antes de ler código manualmente.
2. Para cada achado: classifique severidade (crítico/alto/médio/baixo), aponte arquivo:linha, dê a correção.
3. Só marque "ok" um item depois de ver o código que o garante — não presuma.

## Varredura rápida (rode primeiro)
```bash
# SQL montado por concatenação (candidato a injeção)
rg -n "(query|exec|prepare)\s*\(.*\.\s*\\\$" --glob '*.php'
rg -n "\\\$_(GET|POST|REQUEST|COOKIE)" --glob '*.php' | rg -i "query|exec|SELECT|INSERT|UPDATE|DELETE"
# echo/print sem escape
rg -n "(echo|print|=)\s*\\\$_(GET|POST|REQUEST)" --glob '*.php'
rg -n "<\?=\s*\\\$" --glob '*.php' | rg -v "e\(|pct\(|data_br\(|farol\(|selo\(|barra\(|rotulo\(|opcoes\(|csrf\(|int\)"
# segredos
rg -n "(senha|password|secret|token|api_?key)\s*=>?\s*['\"][^'\"]+['\"]" --glob '*.php'
```

## Checklist OWASP (aplicado ao projeto)

### A03 Injeção (SQL)
- Toda query usa `consulta()/linha()/executa()` com placeholders `?`/`:nome`. Valores NUNCA concatenados.
- Concatenação só permitida para: nomes de coluna vindos de whitelist (`colunas_tabela()`) e inteiros já convertidos com `(int)`. Nome de tabela/coluna de entrada do usuário = proibido.
- `PDO::ATTR_EMULATE_PREPARES => false` (já definido em `inc/base.php`).

### A03 XSS
- Toda saída HTML passa por `e()` (htmlspecialchars, ENT_QUOTES, UTF-8). Exceções auditáveis: helpers que já escapam (`selo`,`barra`,`rotulo`,`opcoes`) e inteiros com cast.
- Atributos dinâmicos (href, value, class) também escapados.
- Sem `innerHTML` com dado do servidor em `assets/*.js`.

### A01 CSRF / Controle de acesso
- Todo POST emite `csrf()` e o handler chama `confere_csrf()` na primeira linha.
- Toda página protegida chama `exige_login()`; edição `exige_edicao()`; admin `exige_admin()`. Verifique que a checagem vem ANTES de qualquer efeito colateral.
- Perfis: admin / conselheiro / consulta. `consulta` nunca grava.

### A07 Sessão e cookies
- `session_regenerate_id(true)` no login e na troca de privilégio.
- Cookie `httponly`, `samesite=Lax`, `secure` quando HTTPS (já em `inc/base.php`).
- Logout destrói a sessão.
- Login tem trava de força bruta (tentativas + atraso).

### A02 Credenciais e hashing
- Senhas só com `password_hash(PASSWORD_DEFAULT)` / `password_verify`. Nunca md5/sha1, nunca texto claro.
- `config.php` fora do versionamento; nenhum segredo hard-coded no código.

### A05 Má configuração
- `display_errors=0` em produção; `log_errors=1`. Detalhe de exceção nunca vai para a tela do usuário final.
- Cabeçalhos: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`. CSP quando o JS inline sair para `assets/`.

### A08 Upload de arquivo (backup/sincronização)
- Aceita só o tipo esperado; valida `UPLOAD_ERR_OK`.
- Nome do destino gerado pelo servidor (`nome_arquivo_seguro`), nunca o nome do cliente.
- Path de leitura/escrita preso à pasta `backups/` via `basename()` + `realpath()`. Sem path traversal.
- Conteúdo JSON validado (estrutura esperada) antes de mesclar/restaurar.

### LGPD (requisito, não opcional)
- Dados pessoais/saúde só acessíveis após `exige_login()`.
- Exportação/backup disponível só a admin.
- Erros não vazam dados pessoais em mensagem ou log público.
- Minimização: campos coletados têm finalidade; sem dado sensível em logs de acesso.
