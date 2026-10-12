# CONVISITA

Sistema de registro e análise das visitas do Conselho Municipal de Saúde de Americana (COMSAÚDE) às unidades de saúde do município. Substitui a planilha de pontuação, que tinha uma aba por unidade, por um banco de dados único com histórico por ciclo, cálculo automático das notas, agenda de visitas e acompanhamento de pendências.

O sistema funciona em duas formas: instalado localmente no XAMPP, para uso em um ou mais computadores, e publicado na hospedagem do HostGator em https://radl.com.br/convisita. As duas versões trocam dados pela rotina de backup e sincronização.

## Documentação

| Documento | Conteúdo |
|---|---|
| `README.md` | Visão geral, instalação, publicação e estrutura de pastas (este arquivo) |
| `docs/DICIONARIO_DE_DADOS.md` | Tabelas, campos, domínios, visões de cálculo e formato do backup |
| `docs/REQUISITOS_DO_SISTEMA.md` | Requisitos funcionais, não funcionais, regras de negócio e ambiente |
| `docs/ACHADOS_E_ENTENDIMENTOS.md` | Análise da planilha original, inconsistências encontradas e origem do modelo de dados |
| `docs/manual/` | Manual do usuário em seis partes (PDF), com telas do sistema |

## Funcionalidades

| Tela | Uso |
|---|---|
| Painel | Classificação das unidades no ciclo, unidades ainda sem visita (em cinza), médias por dimensão, itens críticos e situação da agenda, com filtros de ciclo e tipo de unidade |
| Visitas | Lista e filtro das visitas, ficha para impressão e lançamento com cálculo da nota em tempo real |
| Unidades | Cadastro, histórico de visitas, gráfico de evolução e pendências de cada unidade |
| Agenda | Próxima visita prevista (última visita somada à periodicidade da unidade) e unidades atrasadas |
| Pendências | Encaminhamentos das visitas com responsável, prazo e situação |
| Comparativo | Evolução entre dois ciclos, por unidade e por item |
| Instrumento e cadastros | Itens, pesos, faixas do farol, ciclos, tipos, conselheiros, usuários e backup |
| Exportar | Arquivos CSV de resultados, notas por item e agenda, prontos para o Excel |

Perfis de acesso: administrador (tudo), conselheiro (lança visitas e pendências) e consulta (somente leitura).

## Instalação local (XAMPP)

1. Abra o painel do XAMPP e ligue Apache e MySQL.
2. Confirme que esta pasta está em `E:\xampp\htdocs\convisita`.
3. Acesse `http://localhost/convisita/instalar.php` e clique em "Instalar agora". O instalador cria o banco `convisita` e carrega as 46 unidades e as 52 visitas históricas (ciclos 2023 e 2026).
4. Entre em `http://localhost/convisita` com usuário `admin` e senha `comsaude` e troque a senha pelo menu do usuário no canto superior direito (avatar › Alterar senha).
5. Em Instrumento e cadastros › Backup e sincronização, informe o nome deste computador.

Se o MySQL do XAMPP tiver senha para o usuário root, ajuste `config.php`. Os arquivos da pasta `sql` também podem ser importados pelo phpMyAdmin, na ordem numérica. Para acesso por outros computadores da rede, use o IP desta máquina no lugar de `localhost` (por exemplo, `http://192.168.0.10/convisita`).

## Publicação na hospedagem (HostGator)

A versão publicada fica em `public_html/convisita` da conta do cPanel, com banco e usuário `radlc849_convisita`. O pacote de publicação está em `deploy/convisita_hostgator.zip` e não inclui documentação, planilhas, ferramentas nem backups.

Para atualizar a versão publicada:

1. Faça um backup em Backup e sincronização no servidor antes de qualquer troca de arquivos.
2. Envie o pacote pelo Gerenciador de Arquivos do cPanel para `public_html` e extraia.
3. Preserve o `config.php` do servidor, que guarda a conexão com o banco da hospedagem. Se o pacote trouxer um `config.php`, descarte o do pacote.
4. Mudanças de estrutura do banco são aplicadas automaticamente no primeiro acesso de cada sessão.

O `.htaccess` do servidor redireciona todo acesso para https. Essa regra existe só na hospedagem e não deve ser levada para o XAMPP, onde não há certificado. O instalador fica bloqueado depois da primeira instalação; para reinstalar, apagando todos os dados, é preciso definir `permitir_reinstalar` como `true` no `config.php`.

## Backup e sincronização entre computadores

Em Instrumento e cadastros › Backup e sincronização (somente administrador):

| Ação | O que faz |
|---|---|
| Baixar backup | Gera um arquivo `.json` com todos os dados deste computador. Uma cópia fica guardada na pasta `backups`. |
| Mesclar | Traz de um arquivo de outro computador apenas o que é novo ou mais recente: visitas, notas, comentários, pendências, conselheiros, unidades, ciclos e tipos. Nada é apagado. |
| Restaurar | Substitui todos os dados deste computador pelos do arquivo (cópia fiel). Usuários e senhas só são substituídos se a opção for marcada. |

Antes de mesclar ou restaurar, o sistema mostra o que vai mudar e grava uma cópia de segurança dos dados atuais. Visitas e pendências são reconhecidas pelo identificador único de cada registro; unidades, ciclos, tipos e conselheiros, pelo nome. Quando a mesma visita foi alterada nos dois lados, vale a alteração mais recente. Exclusões não são transmitidas pela mesclagem: exclua nos dois computadores ou use a restauração. Renomear uma unidade em apenas um computador faz a mesclagem criar uma unidade nova no outro.

Rotina sugerida: ao terminar o trabalho, baixe o backup e mescle no outro computador ou no servidor. Para deixar dois ambientes idênticos, mescle nos dois sentidos.

## Regra de cálculo

Cada item recebe 0, 1 ou 2 pontos. O percentual da dimensão é a soma dos pontos dividida pelo máximo possível dos itens avaliados. A nota final é a média ponderada das dimensões (25% cada), com redistribuição do peso quando um item ou uma dimensão não se aplica. A tela da visita também mostra a nota pelo método da planilha original, para comparação.

Quando uma unidade recebe mais de uma visita no mesmo ciclo, todas ficam no histórico, mas o Painel, as médias e o Comparativo usam a visita mais recente. A Agenda calcula a próxima visita a partir da última, conforme a periodicidade cadastrada (6 meses por padrão).

## Estrutura da pasta

| Caminho | Conteúdo |
|---|---|
| `index.php`, `src/`, `views/`, `inc/` | Aplicação MVC em PHP 8 (front controller + router; controllers, views e núcleo compartilhado), com MySQL ou MariaDB |
| `assets/` | CSS e JS do front-end (Bootstrap 5 via CDN; menu lateral responsivo e tema claro/escuro; sem etapa de build) |
| `composer.json`, `tests/` | Apenas desenvolvimento: PHPUnit e testes. A aplicação tem autoloader próprio e não precisa do `vendor/` em produção |
| `config.php` | Conexão com o banco e título do sistema (diferente em cada ambiente) |
| `sql/01_estrutura.sql` | Tabelas e visões de cálculo |
| `sql/02_referencia.sql` | Instrumento (dimensões e itens), faixas, tipos, ciclos e usuário inicial |
| `sql/03_carga_historica.sql` | Unidades, visitas e notas extraídas da planilha original |
| `planilha/CONVISITA_base_visitas.xlsx` | Versão em planilha do mesmo modelo de dados |
| `planilha/original/` | Planilha de origem, preservada sem alterações |
| `backups/` | Criada automaticamente; guarda backups gerados, recebidos e cópias de segurança |
| `deploy/` | Pacote de publicação para a hospedagem |
| `docs/` | Documentação técnica e manual do usuário |
| `ferramentas/` | Scripts Python usados para extrair os dados da planilha e gerar a carga |

As pastas `src`, `views`, `sql`, `inc`, `docs`, `planilha`, `ferramentas` e `backups`, além do `config.php` e dos arquivos `.sql`, `.md`, `.json`, `.py` e `.xlsx`, têm o acesso pelo navegador bloqueado por `.htaccess`.
