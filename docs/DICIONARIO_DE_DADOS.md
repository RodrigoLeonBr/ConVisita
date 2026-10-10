# CONVISITA · Dicionário de dados

Banco relacional MySQL 8 ou MariaDB 10.4 ou superior, motor InnoDB, conjunto de caracteres `utf8mb4` e ordenação `utf8mb4_unicode_ci`. Nome do banco: `convisita` na instalação local e `radlc849_convisita` na hospedagem. A estrutura está em `sql/01_estrutura.sql`; os dados de referência, em `sql/02_referencia.sql`; a carga histórica da planilha, em `sql/03_carga_historica.sql`. Atualizações de estrutura para bancos já instalados ficam em `inc/esquema.php` e rodam automaticamente.

Convenções usadas nas tabelas abaixo: PK é chave primária, FK é chave estrangeira, UQ indica valor único e "Nulo" informa se o campo aceita ficar vazio.

## Visão geral

| Grupo | Tabelas | Papel |
|---|---|---|
| Instrumento de avaliação | `dimensao`, `item_indicador`, `faixa_classificacao` | Define o que é avaliado, os pesos e as faixas do farol |
| Cadastros | `tipo_unidade`, `unidade`, `ciclo`, `conselheiro`, `usuario`, `sistema` | Dados de apoio, acesso e parâmetros |
| Visitas | `visita`, `visita_conselheiro`, `avaliacao_item`, `comentario_dimensao` | Registro de cada visita e das notas dadas |
| Acompanhamento | `pendencia` | Encaminhamentos gerados pelas visitas |
| Visões de cálculo | `vw_dimensao_visita`, `vw_resultado_visita`, `vw_ultima_visita`, `vw_agenda` | Notas, resultados e agenda calculados pelo banco |

Relacionamentos principais: uma unidade pertence a um tipo e tem várias visitas; cada visita pertence a um ciclo, tem uma nota por item, um comentário por dimensão, um ou mais conselheiros e pode originar pendências; cada item pertence a uma dimensão.

## Instrumento de avaliação

### dimensao

As quatro dimensões do instrumento e o peso de cada uma na nota final.

| Campo | Tipo | Nulo | Padrão | Descrição |
|---|---|---|---|---|
| id | TINYINT UNSIGNED | não | | PK |
| codigo | VARCHAR(5) | não | | UQ. Numeral romano da dimensão: I, II, III ou IV |
| nome | VARCHAR(120) | não | | Nome completo |
| nome_curto | VARCHAR(40) | não | | Nome usado em telas e gráficos |
| peso | DECIMAL(6,4) | não | 0.25 | Fração da nota final. A soma das quatro dimensões deve ser 1 |
| ordem | TINYINT | não | | Ordem de exibição |

Conteúdo inicial: I Infraestrutura Física, II Infraestrutura Humana, III Plano Municipal de Saúde e IV Percepção do Usuário, todas com peso 0,25.

### item_indicador

Itens avaliados em cada dimensão.

| Campo | Tipo | Nulo | Padrão | Descrição |
|---|---|---|---|---|
| id | SMALLINT UNSIGNED | não | automático | PK |
| dimensao_id | TINYINT UNSIGNED | não | | FK para `dimensao.id` |
| codigo | VARCHAR(10) | não | | UQ. Código do item, como I.1 ou IV.2 |
| nome | VARCHAR(150) | não | | Nome do item |
| meta | TEXT | não | | Situação esperada para o item ser plenamente atendido |
| fonte | TEXT | sim | | Onde o conselheiro verifica a informação |
| periodicidade | VARCHAR(120) | sim | | Frequência de verificação sugerida |
| pontuacao_max | TINYINT UNSIGNED | não | 2 | Pontuação máxima do item |
| ordem | TINYINT | não | | Ordem de exibição |
| ativo | TINYINT(1) | não | 1 | 1 entra nas fichas e no cálculo; 0 fica fora |

Conteúdo inicial: 14 itens, todos com pontuação máxima 2.

| Dimensão | Itens |
|---|---|
| I | I.1 Infraestrutura civil, I.2 Infraestrutura elétrica, I.3 Infraestrutura hidráulica, I.4 Infraestrutura de comunicação, I.5 Equipamentos, I.6 Materiais, medicamentos e insumos, I.7 Conforto, limpeza e organização |
| II | II.1 Utilização de indicadores de gestão, II.2 Dimensionamento de equipe, II.3 Execução do cronograma de capacitação da equipe, II.4 Disponibilidade de agendamento de consultas, exames e procedimentos |
| III | III.1 Atendimento aos indicadores do PMS |
| IV | IV.1 Registro de reclamações na Ouvidoria do COMSAÚDE, IV.2 Satisfação dos usuários |

### faixa_classificacao

Faixas do farol aplicadas à nota final e aos percentuais.

| Campo | Tipo | Nulo | Padrão | Descrição |
|---|---|---|---|---|
| id | TINYINT UNSIGNED | não | | PK |
| cor | VARCHAR(10) | não | | vermelho, amarelo ou verde |
| rotulo | VARCHAR(60) | não | | Texto exibido |
| limite_inferior | DECIMAL(6,4) | não | | Início da faixa, inclusive |
| limite_superior | DECIMAL(6,4) | não | | Fim da faixa, exclusive |

Conteúdo inicial: vermelho "Crítico" de 0 a 0,40; amarelo "Atenção" de 0,40 a 0,80; verde "Adequado" de 0,80 a 1,0001. O limite superior da última faixa passa de 1 para que a nota 100% fique dentro dela.

## Cadastros

### tipo_unidade

| Campo | Tipo | Nulo | Padrão | Descrição |
|---|---|---|---|---|
| id | TINYINT UNSIGNED | não | automático | PK |
| nome | VARCHAR(80) | não | | UQ. Nome do tipo |

Conteúdo inicial: UBS, ESF, CAPS, Urgência e Emergência, Hospital, Atenção Especializada, Vigilância em Saúde, Apoio Diagnóstico e Terapêutico, Assistência Farmacêutica, Atenção Domiciliar, Entidade Parceira / Conveniada e A classificar.

### unidade

| Campo | Tipo | Nulo | Padrão | Descrição |
|---|---|---|---|---|
| id | SMALLINT UNSIGNED | não | automático | PK |
| nome | VARCHAR(150) | não | | UQ. Nome oficial da unidade. Também identifica a unidade na mesclagem entre computadores |
| nome_planilha | VARCHAR(80) | sim | | Nome da aba correspondente na planilha original |
| tipo_id | TINYINT UNSIGNED | sim | | FK para `tipo_unidade.id` |
| gestao | VARCHAR(80) | sim | | Modelo de gestão: direta, OSS, conveniada etc. |
| endereco | VARCHAR(200) | sim | | Endereço |
| situacao | ENUM | não | ativa | Ver domínio abaixo |
| periodicidade_meses | TINYINT UNSIGNED | não | 6 | Intervalo esperado entre visitas, de 1 a 24 meses |
| observacao | TEXT | sim | | Anotações livres |

Domínio de `situacao`: `ativa`; `em_reforma`; `inativa`. Unidades que não estão ativas aparecem como "suspensa" na agenda.

### ciclo

Período de avaliação usado para agrupar visitas e comparar resultados.

| Campo | Tipo | Nulo | Padrão | Descrição |
|---|---|---|---|---|
| id | SMALLINT UNSIGNED | não | automático | PK |
| nome | VARCHAR(40) | não | | UQ. Ex.: 2023, 2026 |
| data_inicio | DATE | não | | Início do ciclo |
| data_fim | DATE | não | | Fim do ciclo |

### conselheiro

| Campo | Tipo | Nulo | Padrão | Descrição |
|---|---|---|---|---|
| id | SMALLINT UNSIGNED | não | automático | PK |
| nome | VARCHAR(150) | não | | Nome do conselheiro |
| segmento | ENUM | sim | | `usuario`, `trabalhador` ou `gestor_prestador` |
| ativo | TINYINT(1) | não | 1 | 0 deixa de aparecer na seleção de novas visitas |

### usuario

Pessoas com acesso ao sistema. Não precisa coincidir com a tabela de conselheiros.

| Campo | Tipo | Nulo | Padrão | Descrição |
|---|---|---|---|---|
| id | SMALLINT UNSIGNED | não | automático | PK |
| nome | VARCHAR(150) | não | | Nome da pessoa |
| login | VARCHAR(60) | não | | UQ. Nome de acesso |
| senha_hash | VARCHAR(255) | não | | Senha cifrada com `password_hash` do PHP (bcrypt). A senha em si nunca é gravada |
| perfil | ENUM | não | consulta | `admin`, `conselheiro` ou `consulta` |
| ativo | TINYINT(1) | não | 1 | 0 bloqueia o acesso |

Usuário inicial: `admin`, perfil administrador, senha `comsaude`, que deve ser trocada no primeiro acesso.

### sistema

Parâmetros de cada instalação. Não é copiada pelo backup, porque cada computador tem os seus.

| Campo | Tipo | Nulo | Padrão | Descrição |
|---|---|---|---|---|
| chave | VARCHAR(60) | não | | PK. Nome do parâmetro |
| valor | TEXT | sim | | Valor do parâmetro |

Chaves em uso: `nome_computador`, que identifica a instalação nos arquivos de backup.

## Visitas

### visita

| Campo | Tipo | Nulo | Padrão | Descrição |
|---|---|---|---|---|
| id | INT UNSIGNED | não | automático | PK. Número local, pode ser diferente em cada computador |
| uuid | VARCHAR(36) | sim | | UQ. Identificador único entre computadores. Visitas da planilha usam `hist-0001` a `hist-0052`; as demais, UUID versão 4 |
| unidade_id | SMALLINT UNSIGNED | não | | FK para `unidade.id` |
| ciclo_id | SMALLINT UNSIGNED | não | | FK para `ciclo.id` |
| data_visita | DATE | não | | Data da visita |
| data_aproximada | TINYINT(1) | não | 0 | 1 indica data estimada (por exemplo, só mês e ano conhecidos na planilha) |
| tipo | ENUM | não | ordinaria | `ordinaria` ou `extraordinaria` |
| situacao | ENUM | não | rascunho | Ver domínio abaixo |
| relato | TEXT | sim | | Relato geral da visita |
| origem | VARCHAR(120) | sim | | `sistema` para lançamentos na tela, ou aba da planilha de onde veio a visita |
| observacao_importacao | TEXT | sim | | Ajustes feitos na importação da planilha |
| criado_em | TIMESTAMP | não | data e hora atuais | Momento do cadastro |
| atualizado_em | TIMESTAMP | sim | | Momento da última alteração. Decide qual versão vale na mesclagem |

Índice `ix_vis_unid_data` sobre (`unidade_id`, `data_visita`).

Domínio de `situacao`:

| Valor | Significado | Entra nos resultados |
|---|---|---|
| rascunho | Lançamento em andamento | não |
| concluida | Todos os itens avaliados | sim |
| incompleta | Faltou avaliar algum item | sim, listada depois das concluídas |
| em_conferencia | Aguardando revisão | não |

### visita_conselheiro

Conselheiros que participaram de cada visita.

| Campo | Tipo | Nulo | Padrão | Descrição |
|---|---|---|---|---|
| visita_id | INT UNSIGNED | não | | PK e FK para `visita.id`. Excluída junto com a visita |
| conselheiro_id | SMALLINT UNSIGNED | não | | PK e FK para `conselheiro.id` |

### avaliacao_item

Uma linha por item avaliado em cada visita.

| Campo | Tipo | Nulo | Padrão | Descrição |
|---|---|---|---|---|
| visita_id | INT UNSIGNED | não | | PK e FK para `visita.id`. Excluída junto com a visita |
| item_id | SMALLINT UNSIGNED | não | | PK e FK para `item_indicador.id` |
| pontuacao | TINYINT UNSIGNED | sim | | 0 não atende, 1 atende parcialmente, 2 atende plenamente. Restrição: vazio ou entre 0 e 2 |
| nao_se_aplica | TINYINT(1) | não | 0 | 1 retira o item do cálculo e redistribui o peso |
| observacao | TEXT | sim | | Observação do conselheiro sobre o item |

Leitura combinada: pontuação preenchida com `nao_se_aplica` 0 é item avaliado; pontuação vazia com `nao_se_aplica` 0 é item não avaliado, o que torna a visita incompleta; `nao_se_aplica` 1 é item excluído do cálculo.

### comentario_dimensao

Campo "Comentários e oportunidades de melhoria" de cada dimensão.

| Campo | Tipo | Nulo | Padrão | Descrição |
|---|---|---|---|---|
| visita_id | INT UNSIGNED | não | | PK e FK para `visita.id`. Excluída junto com a visita |
| dimensao_id | TINYINT UNSIGNED | não | | PK e FK para `dimensao.id` |
| texto | TEXT | não | | Comentário |

## Acompanhamento

### pendencia

| Campo | Tipo | Nulo | Padrão | Descrição |
|---|---|---|---|---|
| id | INT UNSIGNED | não | automático | PK |
| uuid | VARCHAR(36) | sim | | UQ. Identificador único entre computadores |
| visita_id | INT UNSIGNED | sim | | FK para `visita.id`. Fica vazio se a visita for excluída |
| unidade_id | SMALLINT UNSIGNED | não | | FK para `unidade.id` |
| dimensao_id | TINYINT UNSIGNED | sim | | FK para `dimensao.id` |
| descricao | TEXT | não | | O que precisa ser resolvido |
| responsavel | VARCHAR(150) | sim | | Pessoa ou setor responsável |
| prazo | DATE | sim | | Data limite |
| situacao | ENUM | não | aberta | `aberta`, `em_andamento`, `resolvida` ou `cancelada` |
| resolvida_em | DATE | sim | | Data da resolução |
| observacao | TEXT | sim | | Acompanhamento |
| criado_em | TIMESTAMP | não | data e hora atuais | Momento do cadastro |
| atualizado_em | TIMESTAMP | sim | | Momento da última alteração |

Pendências abertas ou em andamento com prazo vencido recebem a marca "vencida" na lista.

## Visões de cálculo

### vw_dimensao_visita

Uma linha por visita e dimensão, considerando só itens ativos.

| Coluna | Descrição |
|---|---|
| visita_id, dimensao_id, dimensao_codigo, dimensao, peso | Identificação |
| itens_previstos | Itens ativos da dimensão |
| itens_pontuados | Itens com pontuação e aplicáveis |
| itens_nao_aplicaveis | Itens marcados como não se aplica |
| pontos | Soma das pontuações aplicáveis |
| pontos_possiveis | Soma das pontuações máximas dos itens pontuados |
| pontos_maximos_instrumento | Soma das pontuações máximas de todos os itens ativos |
| percentual | `pontos / pontos_possiveis`; vazio se nenhum item foi pontuado |

### vw_resultado_visita

Uma linha por visita, com o resultado consolidado.

| Coluna | Descrição |
|---|---|
| visita_id, unidade_id, unidade, tipo_unidade, ciclo_id, ciclo, data_visita, data_aproximada, tipo, situacao | Identificação |
| pontos_totais, pontos_possiveis | Somas das quatro dimensões |
| dimensoes_avaliadas | Quantidade de dimensões com percentual |
| perc_infra_fisica, perc_infra_humana, perc_plano_municipal, perc_percepcao_usuario | Percentual de cada dimensão |
| nota_final | Média ponderada dos percentuais das dimensões avaliadas, com pesos redistribuídos entre elas |
| nota_metodo_planilha | Soma de peso vezes percentual, valendo zero a dimensão não avaliada, como na planilha original |

### vw_ultima_visita

Mesmas colunas de `vw_resultado_visita`, restritas à visita concluída ou incompleta mais recente de cada unidade, em qualquer ciclo. Em caso de empate na data, vale o maior `id`.

### vw_agenda

Uma linha por unidade.

| Coluna | Descrição |
|---|---|
| unidade_id, unidade, tipo_unidade, situacao_unidade, periodicidade_meses | Dados da unidade |
| visita_id, ultima_visita, nota_final | Última visita válida |
| proxima_prevista | Data da última visita somada à periodicidade |
| situacao_agenda | Ver domínio abaixo |

Domínio de `situacao_agenda`: `suspensa` (unidade não ativa); `sem_visita` (nenhuma visita válida); `atrasada` (data prevista já passou); `proximos_30_dias` (prevista para os próximos 30 dias); `em_dia` (demais casos).

## Formato do arquivo de backup

Arquivo JSON gerado em Backup e sincronização.

| Chave | Conteúdo |
|---|---|
| formato | Sempre `convisita-backup` |
| versao | Versão do formato, hoje 2. Arquivos de versão maior que a do sistema são recusados |
| gerado_em | Data e hora da geração |
| computador | Valor de `nome_computador` da origem |
| usuario | Login de quem gerou |
| tabelas | Objeto com uma lista de registros por tabela, na ordem: tipo_unidade, dimensao, item_indicador, faixa_classificacao, ciclo, unidade, conselheiro, usuario, visita, visita_conselheiro, avaliacao_item, comentario_dimensao, pendencia |

A tabela `sistema` não entra no backup. Arquivos da versão 1, sem `uuid`, recebem identificadores ao serem lidos.

Chaves de reconhecimento na mesclagem: visitas e pendências pelo `uuid`; unidades, ciclos, tipos e conselheiros pelo `nome`. A versão de cada registro é `atualizado_em` ou, se vazio, `criado_em`; a mais recente prevalece.
