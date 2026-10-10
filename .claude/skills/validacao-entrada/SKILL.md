---
name: validacao-entrada
description: Use ao ler ou gravar qualquer entrada do usuário no CONVISITA ($_GET/$_POST/$_FILES, parâmetros de URL, uploads). Define o padrão único de validação/sanitização — filter_var, casting, whitelist — para evitar divergência entre páginas. Também para "validar entrada", "sanitizar", "tratar $_POST".
---

# Padrão de validação e sanitização de entrada (CONVISITA)

Uma regra só, aplicada em todo lugar. Validar na entrada (antes de usar), escapar na saída (ver `auditoria-php` XSS). Não confie em validação de front-end.

## Helpers do projeto (use sempre)
- `post($k, $padrao=null)` / `get($k, $padrao=null)` — já fazem `trim()` em string. Ponto único de leitura.
- `nulo($v)` — transforma `''`/`null` em `null` (para colunas opcionais).
- `e($v)` — escape de saída HTML.

## Regras por tipo

### Inteiro / id
```php
$id = (int) post('id');            // cast, nunca concatenar cru
if ($id <= 0) { /* rejeita */ }
```
IDs em SQL: sempre placeholder `?`. Se precisar concatenar (filtro dinâmico), só depois de `(int)`.

### Whitelist (enum, perfil, situação, ação, tipo de export)
Valor que pertence a um conjunto fixo → validar contra o conjunto, com default seguro:
```php
$perfil = in_array(post('perfil'), ['admin','conselheiro','consulta'], true) ? post('perfil') : 'consulta';
$modo   = isset($MODOS[post('modo')]) ? post('modo') : 'mesclar';
```
Nunca usar valor de whitelist não validado para montar nome de arquivo, coluna ou caminho.

### Texto livre (nome, observação, descrição)
- Validar tamanho: `mb_substr($v, 0, N)` para o limite da coluna.
- Guardar como veio (sem stripar); escapar só na saída com `e()`.
- Obrigatório? Rejeitar vazio com `aviso(...,'erro')` e não gravar.

### Número decimal / percentual
```php
$n = filter_var(post('x'), FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND);
if ($n === false) { /* rejeita */ }
```

### Data
```php
$d = post('data');
$ok = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$d) && strtotime($d);
$valor = $ok ? $d : null;   // coluna aceita NULL
```

### E-mail / URL (se houver)
```php
$email = filter_var(post('email'), FILTER_VALIDATE_EMAIL) ?: null;
```

### Redirecionamento (open redirect)
Só aceitar destino interno:
```php
$volta = (string) get('volta', '');
redireciona($volta !== '' && str_starts_with($volta, '/') ? $volta : 'index.php');
```

### Upload de arquivo
```php
$f = $_FILES['arquivo'] ?? null;
if (!$f || $f['error'] !== UPLOAD_ERR_OK) { /* rejeita */ }
// nome do destino gerado pelo servidor, nunca $f['name']
$destino = pasta_backups() . '/' . nome_arquivo_seguro(...);
// validar conteúdo (JSON esperado) antes de processar
```

## Anti-padrões (achado em auditoria)
- Ler `$_POST`/`$_GET` direto no meio da lógica em vez de `post()/get()`.
- Concatenar entrada em SQL, caminho de arquivo, nome de coluna ou HTML.
- Sanitizar destrutivamente texto que vai ser escapado na saída (perde dado, não adiciona segurança).
- Confiar em `switch` sem `default` seguro para valores de whitelist.
