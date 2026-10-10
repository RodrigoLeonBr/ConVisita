# CONVISITA · Requisitos do sistema

Este documento reúne o que o sistema deve fazer, as regras que aplica e o ambiente necessário para funcionar. Serve de referência para manutenção, para a instalação em novos computadores e para avaliar pedidos de mudança. As tabelas e campos citados estão descritos em `DICIONARIO_DE_DADOS.md`.

## 1. Objetivo e escopo

O CONVISITA apoia o Conselho Municipal de Saúde de Americana (COMSAÚDE) no planejamento, no registro e na análise das visitas às unidades de saúde do município. O sistema substitui a planilha "Pontuação visitas", preserva o histórico dos ciclos 2023 e 2026 e passa a calcular as notas, a agenda e os comparativos a partir de um banco de dados único.

Ficam fora do escopo a integração com sistemas da Secretaria Municipal de Saúde, o envio de mensagens ou e-mails e a assinatura digital de documentos.

## 2. Perfis de acesso

| Perfil | Permissões |
|---|---|
| Administrador | Tudo o que o conselheiro faz, mais excluir visitas, alterar o instrumento (itens, pesos, faixas, ciclos, tipos), gerenciar usuários e executar backup, mesclagem e restauração |
| Conselheiro | Consultar todas as telas, lançar e editar visitas, cadastrar e editar unidades, conselheiros e pendências |
| Consulta | Somente leitura de todas as telas e exportação de CSV |

Todos os perfis podem trocar a própria senha.

## 3. Requisitos funcionais

| Código | Requisito |
|---|---|
| RF01 | Exigir login e senha para qualquer tela, exceto a de entrada e o instalador |
| RF02 | Exibir no Painel a classificação das unidades no ciclo, com nota final, percentuais por dimensão e farol |
| RF03 | Listar no Painel, em cinza, as unidades ativas que ainda não receberam visita no ciclo, com a data e a nota da última visita anterior quando houver |
| RF04 | Filtrar o Painel por ciclo e por tipo de unidade, aplicando o filtro à classificação, às médias, aos itens críticos, à agenda e às pendências |
| RF05 | Mostrar no Painel a média de cada item no ciclo e destacar os itens com pior desempenho |
| RF06 | Lançar visita informando unidade, ciclo, data, indicação de data aproximada, tipo, conselheiros participantes, relato, nota de cada item, observação por item e comentário por dimensão |
| RF07 | Calcular a nota da visita em tempo real durante o lançamento |
| RF08 | Permitir marcar um item como "não se aplica", retirando-o do cálculo |
| RF09 | Gravar a visita como rascunho ou finalizada, classificando-a automaticamente como concluída ou incompleta conforme os itens avaliados; a opção "em conferência" é exclusiva do administrador |
| RF10 | Exibir a visita em formato de ficha para impressão, com a nota pelo método atual e pelo método da planilha original |
| RF11 | Listar e filtrar visitas por ciclo, unidade e situação |
| RF12 | Manter o cadastro de unidades com tipo, gestão, endereço, situação e periodicidade de visita |
| RF13 | Exibir para cada unidade o histórico de visitas, o gráfico de evolução da nota e as pendências |
| RF14 | Calcular na Agenda a próxima visita prevista e classificar cada unidade como atrasada, nos próximos 30 dias, em dia, sem visita ou suspensa |
| RF15 | Registrar pendências ligadas à unidade e, opcionalmente, à visita e à dimensão, com responsável, prazo, situação e data de resolução |
| RF16 | Filtrar pendências por situação e sinalizar as vencidas |
| RF17 | Comparar dois ciclos por unidade e por item, mostrando a variação |
| RF18 | Exportar em CSV os resultados, as notas por item e a agenda |
| RF19 | Permitir ao administrador alterar nome, meta, fonte, periodicidade e situação dos itens, os pesos das dimensões e os limites e rótulos das faixas |
| RF20 | Permitir ao administrador criar ciclos e tipos de unidade |
| RF21 | Manter o cadastro de conselheiros com segmento e situação |
| RF22 | Manter o cadastro de usuários com perfil e situação, e permitir a troca de senha |
| RF23 | Gerar backup completo em arquivo JSON e guardar uma cópia no servidor |
| RF24 | Mesclar um backup de outro computador, trazendo apenas registros novos ou mais recentes, depois de mostrar uma prévia do que vai mudar |
| RF25 | Restaurar um backup, substituindo todos os dados, com opção de incluir ou não os usuários |
| RF26 | Gravar uma cópia de segurança automática antes de cada mesclagem ou restauração |
| RF27 | Listar os backups guardados, com opções de baixar, mesclar, restaurar e excluir |
| RF28 | Instalar o banco de dados pela tela de instalação, com a carga histórica da planilha |

## 4. Regras de negócio

| Código | Regra |
|---|---|
| RN01 | Cada item recebe 0 (não atende), 1 (atende parcialmente) ou 2 (atende plenamente) |
| RN02 | O percentual de uma dimensão é a soma dos pontos dividida pela soma das pontuações máximas dos itens efetivamente pontuados. Itens não aplicáveis ou não avaliados não entram no denominador |
| RN03 | A nota final é a média ponderada dos percentuais das dimensões avaliadas. Quando uma dimensão não tem item pontuado, seu peso é redistribuído entre as demais |
| RN04 | A nota pelo método da planilha soma peso vezes percentual e atribui zero à dimensão não avaliada. É exibida apenas para comparação |
| RN05 | A soma dos pesos das dimensões deve ser 100%. O sistema recusa a gravação se a soma for diferente |
| RN06 | Faixas do farol: abaixo de 40% vermelho, de 40% até menos de 80% amarelo, 80% ou mais verde. Os limites podem ser alterados pelo administrador |
| RN07 | Visita concluída tem todos os itens ativos avaliados ou marcados como não se aplica. Visita com item sem nota é incompleta |
| RN08 | Somente visitas concluídas ou incompletas entram em Painel, médias, Comparativo, Agenda e exportações de resultado. Rascunhos e visitas em conferência ficam fora |
| RN09 | Quando a unidade tem mais de uma visita no mesmo ciclo, todas permanecem no histórico, mas o Painel, as médias e o Comparativo usam a mais recente. Em caso de mesma data, vale a de maior número |
| RN10 | A próxima visita prevista é a data da última visita válida somada à periodicidade da unidade, que por padrão é de 6 meses |
| RN11 | Unidades em reforma ou inativas aparecem como suspensas na agenda e não são cobradas |
| RN12 | Alterações no instrumento valem para todas as visitas, inclusive as já registradas, porque as notas são recalculadas pelo banco a cada consulta |
| RN13 | Itens inativos deixam de aparecer nas novas fichas e saem do cálculo de todas as visitas |
| RN14 | Pendência aberta ou em andamento com prazo anterior à data atual é considerada vencida |
| RN15 | Na mesclagem, visitas e pendências são reconhecidas pelo identificador único; unidades, ciclos, tipos e conselheiros, pelo nome. Prevalece a versão com data de atualização mais recente |
| RN16 | A mesclagem não transmite exclusões. Para replicar uma exclusão, é preciso excluir nos dois ambientes ou restaurar |
| RN17 | Os parâmetros da instalação, como o nome do computador, não são copiados pelo backup |
| RN18 | O instalador só roda com o banco vazio, a menos que `permitir_reinstalar` esteja ativado no `config.php` |

## 5. Requisitos não funcionais

| Código | Categoria | Requisito |
|---|---|---|
| RNF01 | Segurança | Senhas gravadas apenas na forma cifrada (bcrypt, função `password_hash` do PHP), com no mínimo 6 caracteres |
| RNF02 | Segurança | Sessão com cookie `HttpOnly`, `SameSite=Lax` e `Secure` quando acessada por https; identificador de sessão renovado no login |
| RNF03 | Segurança | Todo formulário que altera dados protegido por token contra falsificação de requisição (CSRF) |
| RNF04 | Segurança | Consultas ao banco feitas com parâmetros (PDO com instruções preparadas) e todo texto exibido escapado contra injeção de código |
| RNF05 | Segurança | Acesso pelo navegador bloqueado ao `config.php`, às pastas `sql`, `inc`, `docs`, `planilha`, `ferramentas` e `backups` e aos arquivos `.sql`, `.md`, `.json`, `.py` e `.xlsx`; listagem de pastas desativada |
| RNF06 | Segurança | Na hospedagem, redirecionamento obrigatório para https |
| RNF07 | Integridade | Chaves estrangeiras e restrições de domínio no banco; exclusão de visita remove em cascata notas, comentários e participantes e desvincula as pendências |
| RNF08 | Integridade | Cálculos feitos por visões do banco, sem notas gravadas, para que não haja divergência entre telas |
| RNF09 | Manutenção | Atualizações de estrutura aplicadas automaticamente no primeiro acesso de cada sessão, sem perda de dados |
| RNF10 | Manutenção | Aplicação em PHP puro, sem framework nem bibliotecas externas, para facilitar a instalação em hospedagem compartilhada |
| RNF11 | Portabilidade | Mesmo código para XAMPP e hospedagem; o que muda entre ambientes fica apenas no `config.php` |
| RNF12 | Compatibilidade | Funcionar com o modo SQL estrito do MySQL, inclusive `ONLY_FULL_GROUP_BY` |
| RNF13 | Usabilidade | Interface em português, linguagem simples, datas no formato DD/MM/AAAA e percentuais com vírgula |
| RNF14 | Usabilidade | Telas utilizáveis em computador e em celular, e fichas de visita próprias para impressão |
| RNF15 | Interoperabilidade | Exportações em CSV com separador ponto e vírgula e codificação UTF-8, abríveis no Excel |
| RNF16 | Disponibilidade | Backup sob demanda e cópia de segurança automática antes de operações que substituem dados |
| RNF17 | Desempenho | Telas respondendo em poucos segundos com o volume previsto, de algumas centenas de unidades e alguns milhares de visitas |

## 6. Requisitos de ambiente

### Servidor

| Componente | Requisito mínimo | Observação |
|---|---|---|
| Servidor web | Apache 2.4 com `mod_rewrite` e suporte a `.htaccess` | No XAMPP já vem habilitado |
| PHP | 8.0 ou superior (testado em 8.2) | Extensões `pdo_mysql`, `mbstring` e `json` |
| Banco de dados | MySQL 8 ou MariaDB 10.4 ou superior | Motor InnoDB, `utf8mb4` |
| Usuário do banco | Permissões de criar tabelas e visões, alterar tabelas e ler e gravar dados no banco do sistema | Na hospedagem, permissão total apenas sobre `radlc849_convisita` |
| Espaço em disco | 20 MB para a aplicação, mais o espaço dos backups | Cada backup ocupa algumas centenas de kB |
| Gravação em pasta | A pasta `backups` precisa permitir gravação pelo PHP | Criada automaticamente |

### Ambientes em uso

| Ambiente | Endereço | Banco |
|---|---|---|
| Local (XAMPP) | `http://localhost/convisita`, pasta `E:\xampp\htdocs\convisita` | `convisita`, usuário `root` |
| Hospedagem (HostGator, cPanel, PHP 8.2) | `https://radl.com.br/convisita`, pasta `public_html/convisita` | `radlc849_convisita` |

### Estação do usuário

Navegador atualizado (Chrome, Edge ou Firefox) com JavaScript ativado. Para abrir as exportações, Excel ou programa compatível com CSV. Para os manuais, leitor de PDF. Não há instalação de programa no computador do usuário.

## 7. Restrições e premissas

O instrumento tem hoje quatro dimensões com códigos fixos (I a IV), usados na visão de resultado; acrescentar uma quinta dimensão exige ajuste na estrutura. A data aproximada das visitas históricas vem da planilha original, onde algumas visitas tinham apenas mês e ano. A sincronização entre ambientes depende da troca manual do arquivo de backup; não há sincronização automática em tempo real.
