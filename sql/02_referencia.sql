-- Dados de referencia: instrumento, faixas, tipos e ciclos
USE convisita;
SET NAMES utf8mb4;

INSERT INTO dimensao (id,codigo,nome,nome_curto,peso,ordem) VALUES (1,'I','Infraestrutura Física (prédios, equipamentos e insumos)','Infraestrutura Física',0.25,1);
INSERT INTO dimensao (id,codigo,nome,nome_curto,peso,ordem) VALUES (2,'II','Infraestrutura Humana','Infraestrutura Humana',0.25,2);
INSERT INTO dimensao (id,codigo,nome,nome_curto,peso,ordem) VALUES (3,'III','Desempenho no Plano Municipal de Saúde','Plano Municipal de Saúde',0.25,3);
INSERT INTO dimensao (id,codigo,nome,nome_curto,peso,ordem) VALUES (4,'IV','Percepção do Usuário','Percepção do Usuário',0.25,4);
INSERT INTO item_indicador (id,dimensao_id,codigo,nome,meta,fonte,periodicidade,pontuacao_max,ordem) VALUES (1,1,'I.1','Infraestrutura civil','Prédio novo ou reformado com paredes e tetos pintados, sem mofo, infiltrações ou goteiras','Observações dos conselheiros e entrevista com o coordenador durante visita','Semestral (exceto hospital - ver cronograma de visitas)',2,1);
INSERT INTO item_indicador (id,dimensao_id,codigo,nome,meta,fonte,periodicidade,pontuacao_max,ordem) VALUES (2,1,'I.2','Infraestrutura elétrica','Fiação elétrica em bom estado com equipamentos de manutenção de energia elétrica (no-break e/ou gerador)','Observações dos conselheiros e entrevista com o coordenador durante visita','Semestral (exceto hospital - ver cronograma de visitas)',2,2);
INSERT INTO item_indicador (id,dimensao_id,codigo,nome,meta,fonte,periodicidade,pontuacao_max,ordem) VALUES (3,1,'I.3','Infraestrutura hidráulica','Canos, calhas e esgotos sem entupimentos ou vazamentos','Observações dos conselheiros e entrevista com o coordenador durante visita','Semestral (exceto hospital - ver cronograma de visitas)',2,3);
INSERT INTO item_indicador (id,dimensao_id,codigo,nome,meta,fonte,periodicidade,pontuacao_max,ordem) VALUES (4,1,'I.4','Infraestrutura de comunicação','Disponibilidade de internet, telefones e computadores funcionantes e suficientes para todo o serviço','Observações dos conselheiros e entrevista com o coordenador durante visita','Semestral (exceto hospital - ver cronograma de visitas)',2,4);
INSERT INTO item_indicador (id,dimensao_id,codigo,nome,meta,fonte,periodicidade,pontuacao_max,ordem) VALUES (5,1,'I.5','Equipamentos','Número suficiente e condições adequadas de utilização dos equipamentos para prestação do serviço','Observações dos conselheiros e entrevista com o coordenador durante visita','Semestral (exceto hospital - ver cronograma de visitas)',2,5);
INSERT INTO item_indicador (id,dimensao_id,codigo,nome,meta,fonte,periodicidade,pontuacao_max,ordem) VALUES (6,1,'I.6','Materiais, medicamentos e insumos','Materiais e insumos em quantidade, reposição e condições de armazenamento adequadas','Observações dos conselheiros e entrevista com o coordenador durante visita','Semestral (exceto hospital - ver cronograma de visitas)',2,6);
INSERT INTO item_indicador (id,dimensao_id,codigo,nome,meta,fonte,periodicidade,pontuacao_max,ordem) VALUES (7,1,'I.7','Conforto, limpeza e organização','Ambiente agradável, organizado, sinalizado, com espaço para circulação, boa luminosidade e sem lixo espalhado pelo estabelecimento','Observações dos conselheiros e entrevista com o coordenador durante visita','Semestral (exceto hospital - ver cronograma de visitas)',2,7);
INSERT INTO item_indicador (id,dimensao_id,codigo,nome,meta,fonte,periodicidade,pontuacao_max,ordem) VALUES (8,2,'II.1','Utilização de indicadores de gestão','Disponibilidade de dados sobre número de atendimentos, capacidade de serviço, número de altas, número de pacientes cadastrados no serviço, principais queixas dos usuários, entre outros. Evidências sobre decisões da coordenação com base nesses dados.','Apresentação de dados em fala do coordenador e existência de planilhas com os indicadores medidos na Unidade','Semestral (exceto hospital - ver cronograma de visitas)',2,8);
INSERT INTO item_indicador (id,dimensao_id,codigo,nome,meta,fonte,periodicidade,pontuacao_max,ordem) VALUES (9,2,'II.2','Dimensionamento de equipe','Presença de escalas de trabalho visíveis e obedecidas pelos funcionários da Unidade com cobertura de todos os horários de atendimento','Apresentação, compatibilidade com o trabalho e adequação das escalas e horários de trabalho dos funcionários do estabelecimento de saúde','Semestral (exceto hospital - ver cronograma de visitas)',2,9);
INSERT INTO item_indicador (id,dimensao_id,codigo,nome,meta,fonte,periodicidade,pontuacao_max,ordem) VALUES (10,2,'II.3','Execução do cronograma de capacitação da equipe de trabalho','Mecanismos de Educação Permanente dos funcionários planejados e funcionantes','Coordenador da Unidade tem disponível um cronograma de Educação Permanente dos funcionários planejado e realizado','Semestral (exceto hospital - ver cronograma de visitas)',2,10);
INSERT INTO item_indicador (id,dimensao_id,codigo,nome,meta,fonte,periodicidade,pontuacao_max,ordem) VALUES (11,2,'II.4','Disponibilidade de agendamento de consultas, exames e procedimentos','Disponibilização de vagas mensais de consultas, exames ou procedimentos compatíveis com a capacidade, a demanda e a pactuação com a SMS','Há um relatório com comparação do número de vagas mensais ofertadas e utilizadas com as consultas, exames ou procedimentos de responsabilidade do estabelecimento','Semestral (exceto hospital - ver cronograma de visitas)',2,11);
INSERT INTO item_indicador (id,dimensao_id,codigo,nome,meta,fonte,periodicidade,pontuacao_max,ordem) VALUES (12,3,'III.1','Atendimento aos indicadores do PMS','O estabelecimento de saúde demonstra o cumprimento de todos os indicadores que assumiu para o ano no Plano Municipal de Saúde','Porcentagem de respostas adequadas às questões formuladas para cada indicador do Plano Municipal de Saúde','Semestral',2,12);
INSERT INTO item_indicador (id,dimensao_id,codigo,nome,meta,fonte,periodicidade,pontuacao_max,ordem) VALUES (13,4,'IV.1','Registro de reclamações na Ouvidoria do COMSAUDE','Ausência de registros de reclamações dos usuários sobre o serviço e/ou resolução adequada de eventuais demandas de pequena gravidade existentes','Relatório com número de demandas apresentadas ao COMSAUDE pelos usuários e suas resoluções','Semestral',2,13);
INSERT INTO item_indicador (id,dimensao_id,codigo,nome,meta,fonte,periodicidade,pontuacao_max,ordem) VALUES (14,4,'IV.2','Satisfação dos usuários','Estabelecimento de saúde com alto nível de satisfação do usuário (NPS > 80%)','Resultado de percepção do usuário  a partir de entrevistas realizadas durante as visitas do COMSAUDE ao estabelecimento de saúde','Semestral (exceto hospital - ver cronograma de visitas)',2,14);
INSERT INTO faixa_classificacao VALUES (1,'vermelho','Crítico (abaixo de 40%)',0.0000,0.4000);
INSERT INTO faixa_classificacao VALUES (2,'amarelo','Atenção (40% a 79,9%)',0.4000,0.8000);
INSERT INTO faixa_classificacao VALUES (3,'verde','Adequado (80% ou mais)',0.8000,1.0001);
INSERT INTO tipo_unidade (id,nome) VALUES (1,'Unidade Básica de Saúde (UBS)');
INSERT INTO tipo_unidade (id,nome) VALUES (2,'Estratégia Saúde da Família (ESF)');
INSERT INTO tipo_unidade (id,nome) VALUES (3,'Centro de Atenção Psicossocial (CAPS)');
INSERT INTO tipo_unidade (id,nome) VALUES (4,'Urgência e Emergência');
INSERT INTO tipo_unidade (id,nome) VALUES (5,'Hospital');
INSERT INTO tipo_unidade (id,nome) VALUES (6,'Atenção Especializada');
INSERT INTO tipo_unidade (id,nome) VALUES (7,'Vigilância em Saúde');
INSERT INTO tipo_unidade (id,nome) VALUES (8,'Apoio Diagnóstico e Terapêutico');
INSERT INTO tipo_unidade (id,nome) VALUES (9,'Assistência Farmacêutica');
INSERT INTO tipo_unidade (id,nome) VALUES (10,'Atenção Domiciliar');
INSERT INTO tipo_unidade (id,nome) VALUES (11,'Entidade Parceira / Conveniada');
INSERT INTO tipo_unidade (id,nome) VALUES (12,'A classificar');
INSERT INTO ciclo (id,nome,data_inicio,data_fim) VALUES (1,'2023','2023-01-01','2023-12-31');
INSERT INTO ciclo (id,nome,data_inicio,data_fim) VALUES (2,'2026','2026-01-01','2026-12-31');
-- usuario inicial admin. Senha SEM valor utilizavel aqui: o instalar.php gera uma senha
-- aleatoria e a exibe uma unica vez. Login fica impossivel ate a instalacao definir a senha.
INSERT INTO usuario (nome,login,senha_hash,perfil) VALUES ('Administrador','admin','!sem-senha-defina-no-instalar','admin');
