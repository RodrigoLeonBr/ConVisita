# Visitas do COMSAÚDE às unidades de saúde: achados e entendimentos

Análise da planilha "Pontuação visitas 2026atualizada.xlsx" e proposta de modelo de dados para o sistema CONVISITA.
Elaborado em outubro de 2026.

## 1. Visão geral da planilha

A pasta de trabalho possui 48 abas, organizadas em quatro blocos com funções distintas.

| Bloco | Abas | Função | Natureza dos dados |
|---|---|---|---|
| Fichas de visita | 1 a 43 (uma por unidade) | Registro da pontuação item a item da visita mais recente e quadro-resumo por dimensão | Ciclo 2026 na maioria; algumas abas ainda com dados de 2023 ou não preenchidas |
| Performance Unidades 2023 | 44 | Painel consolidado do ciclo 2023, com pontos, percentuais, comentários por dimensão, última visita e previsão da próxima | Ciclo 2023, valores digitados manualmente |
| DADOS | 45 | Espelho da Performance 2023 em formato de tabela plana (nomes de coluna já em padrão de banco) | Apenas fórmulas apontando para a aba 44 |
| Avaliação Global | 46 | Formato longo (unidade x item), 19 unidades x 14 itens = 266 linhas | Notas item a item do ciclo 2023, sem fórmulas |
| Planilha3 | 47 | Tabelas auxiliares: situação de cada unidade em 2023 (visitado, reforma, incompleto, 2022) e faróis por dimensão (verde, amarelo, vermelho) | Classificação manual |
| Planilha2 | 48 | Vazia | – |

O conjunto mistura, portanto, dois ciclos de avaliação (2023 e 2026) sem uma coluna que identifique o ciclo, o que é a principal origem das inconsistências descritas adiante.

## 2. O instrumento de avaliação

Todas as 43 fichas seguem o mesmo instrumento, com 4 dimensões e 14 itens. Cada item recebe 0, 1 ou 2 pontos, lançados em colunas separadas (G = mínima, H = média, I = máxima); apenas uma das três colunas deve ser preenchida.

| Dimensão | Itens | Pontos máximos | Peso |
|---|---|---|---|
| I. Infraestrutura Física (prédios, equipamentos e insumos) | I.1 civil, I.2 elétrica, I.3 hidráulica, I.4 comunicação, I.5 equipamentos, I.6 materiais e insumos, I.7 conforto, limpeza e organização | 14 | 25% |
| II. Infraestrutura Humana | II.1 indicadores de gestão, II.2 dimensionamento de equipe, II.3 educação permanente, II.4 agendamento de consultas, exames e procedimentos | 8 | 25% |
| III. Desempenho no Plano Municipal de Saúde | III.1 atendimento aos indicadores do PMS | 2 | 25% |
| IV. Percepção do Usuário | IV.1 reclamações na Ouvidoria do COMSAÚDE, IV.2 satisfação dos usuários (NPS acima de 80%) | 4 | 25% |

A nota final da unidade é a média ponderada dos percentuais de cada dimensão:

Nota = 0,25 x (pontos I / 14) + 0,25 x (pontos II / 8) + 0,25 x (pontos III / 2) + 0,25 x (pontos IV / 4)

Como os pesos são iguais e o número de itens não, cada item tem influência muito diferente na nota. O único item da dimensão III vale sozinho 25% da nota, enquanto cada item da dimensão I vale cerca de 3,6%. Vale o conselho avaliar se essa assimetria é intencional.

A periodicidade prevista é semestral, com exceção do Hospital Municipal, que segue cronograma próprio. A classificação por cor, extraída da formatação condicional da Performance 2023, usa as faixas de 0 a 40% (vermelho), 41% a 79% (amarelo) e 80% a 100% (verde).

## 3. Similaridades entre as visões

As quatro visões representam a mesma informação em granularidades diferentes, o que permite reconciliá-las.

| Visão | Granularidade | Ciclo | Relação com as demais |
|---|---|---|---|
| Fichas por unidade | Item | Predominantemente 2026 | Fonte primária do ciclo 2026 |
| Avaliação Global | Item | 2023 | Fonte primária do ciclo 2023; somadas por dimensão, as notas reproduzem exatamente a Performance 2023 em todas as unidades comparáveis, com exceção do item III.1 da UPA São José |
| Performance Unidades 2023 | Dimensão | 2023 | Consolidação da Avaliação Global, acrescida de comentários e datas |
| DADOS | Dimensão | 2023 | Cópia plana da Performance 2023 |
| Planilha3 | Dimensão (farol) | 2023 | Classificação qualitativa, nem sempre coerente com os percentuais |

A reconciliação permitiu recuperar a nota da UBS Jardim São Paulo em 2023 (85,7%), que na Performance aparecia como erro de referência.

## 4. Achados

### 4.1 Integridade das fichas de visita

Onze abas não refletem uma visita do ciclo 2026 e não foram importadas como visita nova.

| Aba | Situação encontrada |
|---|---|
| ESF Zanaga II, ADAM, AEPHIVA, Clínica Jones | Pontuação e data (29/06/2023) idênticas às da UAD em 2023: abas copiadas e nunca preenchidas. Todas exibem 82,6%. |
| UBS Dona Rosa | Pontuação idêntica à da UBS Parque Gramado (14/05/2026), com data de 30/10/2023, que não corresponde à visita real da unidade em 2023 (71,4%). |
| UBS LIBERDADE | Cópia da aba UBS Dona Rosa (mesmo cabeçalho, data e notas). |
| APAE | Sem data e com todos os itens em nota máxima: modelo não preenchido. |
| UVISA | Data de 29/06/2023 (a mesma da UAD) e notas que não constam da Avaliação Global. Requer confirmação. |
| UNACON, CTA, CAPS Álcool e Drogas | Notas idênticas às de 2023; correspondem à visita daquele ciclo, já importada. |

Há ainda erros de cabeçalho no quadro-resumo: a aba ESF São José traz "ESF SÃO VITO", e as abas Clínica Mod. Zanaga, Farmácia Central e CEO trazem "UBS D. ROSA". O nome do item I.3 (Infraestrutura hidráulica) está em branco em 22 abas.

### 4.2 Fórmulas e cálculo

O quadro-resumo de cada ficha (colunas F a H) não está ligado às notas dos itens: os pontos por dimensão são digitados à mão. Isso gerou divergência na ESF Mário Covas, cuja dimensão IV soma 3 pontos nos itens, mas aparece com 4 no quadro (nota 67,9% na planilha contra 61,6% pelo cálculo correto).

Em sete abas a fórmula de soma da coluna J foi substituída por valor fixo: UBS Mathiensen (J4, J11 e J15) e, na célula J15, H. M. Urgência e Emergência, UBS Parque Gramado, ESF Alvorada, Núcleo de Especialidades, Farmácia Central e CEO. Na Performance 2023, três linhas (ESF Praia Azul, ESF São José e ESF Zanaga) multiplicam por pesos das linhas 1 a 3 em vez da linha 4, por deslocamento de referência, e a UPA São José tem o percentual do Plano Municipal digitado (50%) enquanto a Avaliação Global registra 0 ponto.

A Performance 2023 também depende de um arquivo externo (escala e resumo de visitas de 2022 e 2023) que não está disponível, o que explica os erros de referência na UBS Jardim São Paulo e nos nomes do Núcleo de Especialidades e das UPAs.

As faixas de cor deixam lacunas: valores entre 40% e 41% ou entre 79% e 80% (por exemplo, 79,5% da UBS Mathiensen em 2026) ficam sem cor.

### 4.3 Tratamento do "não se aplica"

Quando uma dimensão não se aplica (sigilo no SAE/DST, perfil domiciliar da UAD), a planilha conta zero para ela e mantém o peso de 25%, rebaixando a unidade injustamente. No sistema proposto, o item marcado como "não se aplica" sai do cálculo e o peso da dimensão é redistribuído entre as demais.

| Unidade (2026) | Nota na planilha | Nota recalculada | Motivo |
|---|---|---|---|
| UAD | 75,0% | 100,0% | Percepção do usuário marcada como não aplicável |
| SAE/DST | 69,6% | 92,9% | Percepção do usuário não se aplica por sigilo |
| Clínica Modelo Zanaga | 78,6% | 91,1% | Item IV.2 não se aplica |
| ESF Mário Covas | 67,9% | 61,6% | Quadro-resumo digitado diverge dos itens |

O mesmo critério vale para as visitas incompletas de 2023 (UBS Cariobinha, ESF São José e ESF Alvorada), que receberam nota zero nas dimensões não avaliadas. No sistema elas ficam marcadas como incompletas, a nota considera apenas o que foi avaliado e elas não entram na média geral.

### 4.4 Datas e agenda

As datas da última visita aparecem em três lugares (rodapé da ficha, coluna Q da Performance e nomes de arquivo externos), com divergências: CAPS AD (21/11/2023 na Performance e 27/11/2023 na ficha), UNACON ("agosto??" na Performance e 08/12/2023 na ficha) e UBS Jardim São Paulo ("nov/23??"). A previsão da próxima visita era digitada, inclusive com textos como "Predia entraria em reforma". No sistema, a próxima visita é calculada a partir da última data e da periodicidade da unidade.

### 4.5 Cadastro de unidades

Os nomes variam entre as abas (UPA - Bairro Antonio Zanaga, PA Zanaga e UPA Zanaga; Centro de Zoonoses e CCZ; CAPS Vida Nova e CAPS Alcool e Drogas). A reconciliação das notas confirmou que PA Zanaga corresponde à UPA Antonio Zanaga de 2023. Ficam pendentes de confirmação a equivalência entre UPA Dona Rosa (2026) e UPA São José (2023), entre CAPS Adulto e CAPS Arte & Vida, além do tipo da Clínica Jones e da Clínica Modelo Zanaga.

## 5. Resultados consolidados

Considerando apenas as visitas completas e o cálculo com redistribuição de pesos:

| Indicador | 2023 | 2026 |
|---|---|---|
| Unidades com avaliação completa | 16 | 32 |
| Nota média | 68,2% | 82,0% |
| Infraestrutura Física | 72,8% | 77,7% |
| Infraestrutura Humana | 60,9% | 87,1% |
| Plano Municipal de Saúde | 53,1% | 82,8% |
| Percepção do Usuário | 85,9% | 79,2% |

Em 2026, 19 unidades estão na faixa adequada, 12 em atenção e 1 em situação crítica (CCZ, 37,5%). A infraestrutura física concentra os itens de pior desempenho: I.1 infraestrutura civil (média 1,22 em 2 pontos), I.2 elétrica (1,44) e I.3 hidráulica (1,56). A percepção do usuário foi a única dimensão que recuou entre os ciclos.

Entre as 15 unidades avaliadas nos dois ciclos, os maiores avanços foram da UBS Mathiensen (+43,8 p.p.), do PA Antonio Zanaga (+38,1 p.p.) e do Núcleo de Especialidades (+30,4 p.p.). A UBS Guanabara caiu 20,1 p.p. (de 76,3% para 56,3%). As quedas da ESF São José e da UBS Cariobinha devem ser lidas com cautela, porque em 2023 só a infraestrutura física foi avaliada nessas unidades.

Na data de elaboração, 11 unidades estão com visita semestral vencida, 6 vencem nos próximos 30 dias e 9 não têm visita válida registrada.

## 6. Modelo de dados proposto

A lógica de banco de dados separa o que é cadastro (instrumento e unidades) do que é fato (visitas e notas), eliminando a necessidade de uma aba por unidade e permitindo histórico ilimitado de ciclos.

| Tabela | Conteúdo | Origem na planilha |
|---|---|---|
| dimensao | 4 dimensões, peso e ordem | Quadro-resumo das fichas (D23:D26) |
| item_indicador | 14 itens com meta, fonte e periodicidade | Linhas 4 a 17 das fichas |
| faixa_classificacao | Limites do farol | Formatação condicional da Performance 2023 |
| tipo_unidade | UBS, ESF, CAPS, Urgência e Emergência, Hospital etc. | Classificação nova |
| unidade | Cadastro único das 46 unidades, com periodicidade e situação | Nomes das abas, Performance 2023 e Planilha3 |
| ciclo | Período de avaliação (2023, 2026, ...) | Inexistente na planilha |
| visita | Unidade, ciclo, data, tipo (ordinária ou extraordinária) e situação | Rodapé das fichas e coluna Q da Performance |
| avaliacao_item | Nota 0 a 2 de cada item em cada visita, com marcação de "não se aplica" | Colunas G a I das fichas e Avaliação Global |
| comentario_dimensao | Comentários e oportunidades de melhoria | Colunas E, H, K e N da Performance 2023 |
| pendencia | Encaminhamentos com responsável, prazo e situação | Inexistente; transforma os comentários em acompanhamento |
| conselheiro e visita_conselheiro | Quem participou de cada visita | Inexistente |
| usuario | Acesso ao sistema (administrador, conselheiro, consulta) | Inexistente |

Quatro visões de banco substituem as fórmulas espalhadas: vw_dimensao_visita (pontos e percentual por dimensão), vw_resultado_visita (nota final e nota pelo método antigo, para comparação), vw_ultima_visita e vw_agenda (próxima visita e situação: atrasada, próximos 30 dias, em dia ou sem visita).

## 7. Carga histórica

Foram importadas 52 visitas: 20 do ciclo 2023, a partir da Avaliação Global combinada com datas e comentários da Performance 2023, e 32 do ciclo 2026, a partir das fichas por unidade. Cada visita guarda a aba de origem e uma nota explicando ajustes feitos na importação. A aba Origem_Abas da planilha normalizada registra o tratamento dado a cada uma das 43 fichas.

## 8. Pontos que dependem de decisão do conselho

Confirmar se as abas não importadas (ESF Zanaga II, ADAM, AEPHIVA, Clínica Jones, UBS Dona Rosa, UBS Liberdade, APAE e UVISA) tiveram visita em 2026 e, em caso positivo, lançá-las no sistema.

Validar o critério de redistribuição de peso para itens que não se aplicam, que altera a nota de quatro unidades em relação à planilha.

Confirmar as equivalências de nomes indicadas no item 4.5 e preencher o tipo de gestão (direta, OSS ou conveniada) de cada unidade.

Avaliar se a dimensão III deve continuar com um único item e peso de 25%, ou se o Plano Municipal de Saúde deve ser desdobrado em itens por unidade.

Definir a periodicidade do Hospital Municipal, hoje cadastrada como semestral por padrão.
