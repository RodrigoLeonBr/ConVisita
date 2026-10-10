-- =====================================================================
-- CONVISITA - Sistema de Visitas do Conselho Municipal de Saude (COMSAUDE)
-- Americana/SP
-- Estrutura do banco de dados (MariaDB 10.4+ / MySQL 8+, padrao XAMPP)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS convisita
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE convisita;

SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW IF EXISTS vw_agenda;
DROP VIEW IF EXISTS vw_ultima_visita;
DROP VIEW IF EXISTS vw_resultado_visita;
DROP VIEW IF EXISTS vw_dimensao_visita;
DROP TABLE IF EXISTS pendencia;
DROP TABLE IF EXISTS comentario_dimensao;
DROP TABLE IF EXISTS avaliacao_item;
DROP TABLE IF EXISTS visita_conselheiro;
DROP TABLE IF EXISTS visita;
DROP TABLE IF EXISTS conselheiro;
DROP TABLE IF EXISTS ciclo;
DROP TABLE IF EXISTS unidade;
DROP TABLE IF EXISTS tipo_unidade;
DROP TABLE IF EXISTS faixa_classificacao;
DROP TABLE IF EXISTS item_indicador;
DROP TABLE IF EXISTS dimensao;
DROP TABLE IF EXISTS usuario;
DROP TABLE IF EXISTS sistema;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- INSTRUMENTO DE AVALIACAO
-- ---------------------------------------------------------------------
CREATE TABLE dimensao (
  id          TINYINT UNSIGNED PRIMARY KEY,
  codigo      VARCHAR(5)   NOT NULL UNIQUE,          -- I, II, III, IV
  nome        VARCHAR(120) NOT NULL,
  nome_curto  VARCHAR(40)  NOT NULL,
  peso        DECIMAL(6,4) NOT NULL DEFAULT 0.25,     -- soma dos pesos = 1
  ordem       TINYINT      NOT NULL
) ENGINE=InnoDB;

CREATE TABLE item_indicador (
  id             SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dimensao_id    TINYINT UNSIGNED NOT NULL,
  codigo         VARCHAR(10)  NOT NULL UNIQUE,        -- I.1 ... IV.2
  nome           VARCHAR(150) NOT NULL,
  meta           TEXT         NOT NULL,
  fonte          TEXT         NULL,
  periodicidade  VARCHAR(120) NULL,
  pontuacao_max  TINYINT UNSIGNED NOT NULL DEFAULT 2,
  ordem          TINYINT      NOT NULL,
  ativo          TINYINT(1)   NOT NULL DEFAULT 1,
  CONSTRAINT fk_item_dim FOREIGN KEY (dimensao_id) REFERENCES dimensao(id)
) ENGINE=InnoDB;

-- Faixas do farol (equivalem a formatacao condicional da planilha original)
CREATE TABLE faixa_classificacao (
  id               TINYINT UNSIGNED PRIMARY KEY,
  cor              VARCHAR(10)  NOT NULL,              -- vermelho, amarelo, verde
  rotulo           VARCHAR(60)  NOT NULL,
  limite_inferior  DECIMAL(6,4) NOT NULL,              -- inclusivo
  limite_superior  DECIMAL(6,4) NOT NULL               -- exclusivo
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CADASTROS
-- ---------------------------------------------------------------------
CREATE TABLE tipo_unidade (
  id    TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome  VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE unidade (
  id                   SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome                 VARCHAR(150) NOT NULL UNIQUE,
  nome_planilha        VARCHAR(80)  NULL,               -- nome da aba na planilha de origem
  tipo_id              TINYINT UNSIGNED NULL,
  gestao               VARCHAR(80)  NULL,               -- direta, OSS, conveniada etc.
  endereco             VARCHAR(200) NULL,
  situacao             ENUM('ativa','em_reforma','inativa') NOT NULL DEFAULT 'ativa',
  periodicidade_meses  TINYINT UNSIGNED NOT NULL DEFAULT 6,  -- visita semestral
  observacao           TEXT NULL,
  CONSTRAINT fk_unid_tipo FOREIGN KEY (tipo_id) REFERENCES tipo_unidade(id)
) ENGINE=InnoDB;

CREATE TABLE ciclo (
  id           SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome         VARCHAR(40) NOT NULL UNIQUE,              -- ex.: 2023, 2026
  data_inicio  DATE NOT NULL,
  data_fim     DATE NOT NULL
) ENGINE=InnoDB;

CREATE TABLE conselheiro (
  id        SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome      VARCHAR(150) NOT NULL,
  segmento  ENUM('usuario','trabalhador','gestor_prestador') NULL,
  ativo     TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE usuario (
  id          SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(150) NOT NULL,
  login       VARCHAR(60)  NOT NULL UNIQUE,
  senha_hash  VARCHAR(255) NOT NULL,
  perfil      ENUM('admin','conselheiro','consulta') NOT NULL DEFAULT 'consulta',
  ativo       TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Parametros do sistema (ex.: nome deste computador, usado nos backups)
CREATE TABLE sistema (
  chave  VARCHAR(60) PRIMARY KEY,
  valor  TEXT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- VISITAS E AVALIACOES
-- ---------------------------------------------------------------------
CREATE TABLE visita (
  id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uuid                   VARCHAR(36) NULL UNIQUE,          -- identificador unico entre computadores
  unidade_id             SMALLINT UNSIGNED NOT NULL,
  ciclo_id               SMALLINT UNSIGNED NOT NULL,
  data_visita            DATE NOT NULL,
  data_aproximada        TINYINT(1) NOT NULL DEFAULT 0,
  tipo                   ENUM('ordinaria','extraordinaria') NOT NULL DEFAULT 'ordinaria',
  situacao               ENUM('rascunho','concluida','incompleta','em_conferencia') NOT NULL DEFAULT 'rascunho',
  relato                 TEXT NULL,
  origem                 VARCHAR(120) NULL,             -- sistema ou aba da planilha importada
  observacao_importacao  TEXT NULL,
  criado_em              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em          TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_vis_unid  FOREIGN KEY (unidade_id) REFERENCES unidade(id),
  CONSTRAINT fk_vis_ciclo FOREIGN KEY (ciclo_id)   REFERENCES ciclo(id),
  INDEX ix_vis_unid_data (unidade_id, data_visita)
) ENGINE=InnoDB;

CREATE TABLE visita_conselheiro (
  visita_id       INT UNSIGNED NOT NULL,
  conselheiro_id  SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (visita_id, conselheiro_id),
  CONSTRAINT fk_vc_vis FOREIGN KEY (visita_id)      REFERENCES visita(id) ON DELETE CASCADE,
  CONSTRAINT fk_vc_con FOREIGN KEY (conselheiro_id) REFERENCES conselheiro(id)
) ENGINE=InnoDB;

-- Uma linha por item avaliado na visita.
-- pontuacao: 0 = nao atende, 1 = atende parcialmente, 2 = atende plenamente
-- pontuacao NULL e nao_se_aplica = 0 -> item nao avaliado (visita incompleta)
-- nao_se_aplica = 1 -> item excluido do calculo (peso redistribuido)
CREATE TABLE avaliacao_item (
  visita_id      INT UNSIGNED NOT NULL,
  item_id        SMALLINT UNSIGNED NOT NULL,
  pontuacao      TINYINT UNSIGNED NULL,
  nao_se_aplica  TINYINT(1) NOT NULL DEFAULT 0,
  observacao     TEXT NULL,
  PRIMARY KEY (visita_id, item_id),
  CONSTRAINT ck_pont CHECK (pontuacao IS NULL OR pontuacao BETWEEN 0 AND 2),
  CONSTRAINT fk_ai_vis  FOREIGN KEY (visita_id) REFERENCES visita(id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_item FOREIGN KEY (item_id)   REFERENCES item_indicador(id)
) ENGINE=InnoDB;

-- "Comentarios e oportunidades de melhoria" por dimensao
CREATE TABLE comentario_dimensao (
  visita_id    INT UNSIGNED NOT NULL,
  dimensao_id  TINYINT UNSIGNED NOT NULL,
  texto        TEXT NOT NULL,
  PRIMARY KEY (visita_id, dimensao_id),
  CONSTRAINT fk_cd_vis FOREIGN KEY (visita_id)   REFERENCES visita(id) ON DELETE CASCADE,
  CONSTRAINT fk_cd_dim FOREIGN KEY (dimensao_id) REFERENCES dimensao(id)
) ENGINE=InnoDB;

-- Encaminhamentos / pendencias apontadas nas visitas, para acompanhamento
CREATE TABLE pendencia (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uuid          VARCHAR(36) NULL UNIQUE,
  visita_id     INT UNSIGNED NULL,
  unidade_id    SMALLINT UNSIGNED NOT NULL,
  dimensao_id   TINYINT UNSIGNED NULL,
  descricao     TEXT NOT NULL,
  responsavel   VARCHAR(150) NULL,
  prazo         DATE NULL,
  situacao      ENUM('aberta','em_andamento','resolvida','cancelada') NOT NULL DEFAULT 'aberta',
  resolvida_em  DATE NULL,
  observacao    TEXT NULL,
  criado_em     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pen_vis  FOREIGN KEY (visita_id)   REFERENCES visita(id) ON DELETE SET NULL,
  CONSTRAINT fk_pen_unid FOREIGN KEY (unidade_id)  REFERENCES unidade(id),
  CONSTRAINT fk_pen_dim  FOREIGN KEY (dimensao_id) REFERENCES dimensao(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- VISOES DE CALCULO (substituem as formulas espalhadas pelas abas)
-- ---------------------------------------------------------------------

-- Resultado de cada dimensao em cada visita.
-- O maximo possivel considera apenas itens efetivamente pontuados,
-- de modo que itens "nao se aplica" nao penalizam a unidade.
CREATE VIEW vw_dimensao_visita AS
SELECT
  v.id                                   AS visita_id,
  d.id                                   AS dimensao_id,
  d.codigo                               AS dimensao_codigo,
  d.nome_curto                           AS dimensao,
  d.peso                                 AS peso,
  COUNT(i.id)                            AS itens_previstos,
  SUM(CASE WHEN a.pontuacao IS NOT NULL AND a.nao_se_aplica = 0 THEN 1 ELSE 0 END) AS itens_pontuados,
  SUM(CASE WHEN a.nao_se_aplica = 1 THEN 1 ELSE 0 END)                             AS itens_nao_aplicaveis,
  SUM(CASE WHEN a.nao_se_aplica = 0 THEN a.pontuacao ELSE 0 END)                   AS pontos,
  SUM(CASE WHEN a.pontuacao IS NOT NULL AND a.nao_se_aplica = 0 THEN i.pontuacao_max ELSE 0 END) AS pontos_possiveis,
  SUM(i.pontuacao_max)                   AS pontos_maximos_instrumento,
  CASE WHEN SUM(CASE WHEN a.pontuacao IS NOT NULL AND a.nao_se_aplica = 0 THEN i.pontuacao_max ELSE 0 END) > 0
       THEN SUM(CASE WHEN a.nao_se_aplica = 0 THEN a.pontuacao ELSE 0 END)
            / SUM(CASE WHEN a.pontuacao IS NOT NULL AND a.nao_se_aplica = 0 THEN i.pontuacao_max ELSE 0 END)
       ELSE NULL END                     AS percentual
FROM visita v
CROSS JOIN dimensao d
JOIN item_indicador i      ON i.dimensao_id = d.id AND i.ativo = 1
LEFT JOIN avaliacao_item a ON a.visita_id = v.id AND a.item_id = i.id
GROUP BY v.id, d.id, d.codigo, d.nome_curto, d.peso;

-- Resultado consolidado de cada visita.
-- nota_final: media ponderada das dimensoes avaliadas, com pesos redistribuidos
-- nota_metodo_planilha: soma(peso x percentual), dimensao nao avaliada vale zero
--                       (reproduz o calculo da planilha original, para comparacao)
CREATE VIEW vw_resultado_visita AS
SELECT
  v.id AS visita_id, v.unidade_id, u.nome AS unidade, t.nome AS tipo_unidade,
  v.ciclo_id, c.nome AS ciclo, v.data_visita, v.data_aproximada, v.tipo, v.situacao,
  SUM(dv.pontos)                AS pontos_totais,
  SUM(dv.pontos_possiveis)      AS pontos_possiveis,
  SUM(CASE WHEN dv.percentual IS NOT NULL THEN 1 ELSE 0 END) AS dimensoes_avaliadas,
  MAX(CASE WHEN dv.dimensao_codigo = 'I'   THEN dv.percentual END) AS perc_infra_fisica,
  MAX(CASE WHEN dv.dimensao_codigo = 'II'  THEN dv.percentual END) AS perc_infra_humana,
  MAX(CASE WHEN dv.dimensao_codigo = 'III' THEN dv.percentual END) AS perc_plano_municipal,
  MAX(CASE WHEN dv.dimensao_codigo = 'IV'  THEN dv.percentual END) AS perc_percepcao_usuario,
  CASE WHEN SUM(CASE WHEN dv.percentual IS NOT NULL THEN dv.peso ELSE 0 END) > 0
       THEN SUM(dv.peso * COALESCE(dv.percentual,0))
            / SUM(CASE WHEN dv.percentual IS NOT NULL THEN dv.peso ELSE 0 END)
       ELSE NULL END AS nota_final,
  SUM(dv.peso * COALESCE(dv.percentual,0)) AS nota_metodo_planilha
FROM visita v
JOIN unidade u              ON u.id = v.unidade_id
LEFT JOIN tipo_unidade t    ON t.id = u.tipo_id
JOIN ciclo c                ON c.id = v.ciclo_id
JOIN vw_dimensao_visita dv  ON dv.visita_id = v.id
GROUP BY v.id, v.unidade_id, u.nome, t.nome, v.ciclo_id, c.nome, v.data_visita,
         v.data_aproximada, v.tipo, v.situacao;

-- Ultima visita valida de cada unidade (ignora rascunhos e registros em conferencia)
CREATE VIEW vw_ultima_visita AS
SELECT r.*
FROM vw_resultado_visita r
WHERE r.situacao IN ('concluida','incompleta')
  AND r.visita_id = (
    SELECT v2.id FROM visita v2
    WHERE v2.unidade_id = r.unidade_id AND v2.situacao IN ('concluida','incompleta')
    ORDER BY v2.data_visita DESC, v2.id DESC LIMIT 1);

-- Agenda: proxima visita prevista = ultima visita + periodicidade da unidade
CREATE VIEW vw_agenda AS
SELECT
  u.id AS unidade_id, u.nome AS unidade, t.nome AS tipo_unidade, u.situacao AS situacao_unidade,
  u.periodicidade_meses,
  uv.visita_id, uv.data_visita AS ultima_visita, uv.nota_final,
  CASE WHEN uv.data_visita IS NULL THEN NULL
       ELSE DATE_ADD(uv.data_visita, INTERVAL u.periodicidade_meses MONTH) END AS proxima_prevista,
  CASE
    WHEN u.situacao <> 'ativa' THEN 'suspensa'
    WHEN uv.data_visita IS NULL THEN 'sem_visita'
    WHEN DATE_ADD(uv.data_visita, INTERVAL u.periodicidade_meses MONTH) < CURDATE() THEN 'atrasada'
    WHEN DATE_ADD(uv.data_visita, INTERVAL u.periodicidade_meses MONTH) <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'proximos_30_dias'
    ELSE 'em_dia' END AS situacao_agenda
FROM unidade u
LEFT JOIN tipo_unidade t     ON t.id = u.tipo_id
LEFT JOIN vw_ultima_visita uv ON uv.unidade_id = u.id;
