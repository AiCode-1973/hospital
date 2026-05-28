-- ============================================================
-- Script SQL - Hospital Santo Expedito - APAS
-- Banco: hospital_santo_expedito
-- Criação das tabelas + dados de exemplo (seed)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `hospital_santo_expedito`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `hospital_santo_expedito`;

-- ------------------------------------------------------------
-- Tabela: especialidades
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `especialidades` (
  `id`        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `nome`      VARCHAR(100)    NOT NULL,
  `descricao` TEXT            NOT NULL,
  `icone`     VARCHAR(100)    NOT NULL COMMENT 'Classe Font Awesome, ex: fa-heart',
  `ativo`     TINYINT(1)      NOT NULL DEFAULT 1,
  `criado_em` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `especialidades` (`nome`, `descricao`, `icone`) VALUES
('Cardiologia',       'Diagnóstico e tratamento de doenças do coração e do sistema cardiovascular, com tecnologia de ponta.',                             'fa-heart-pulse'),
('Ortopedia',         'Tratamento de lesões e doenças do sistema musculoesquelético, incluindo ossos, articulações e ligamentos.',                       'fa-bone'),
('Pediatria',         'Cuidados médicos especializados para bebês, crianças e adolescentes com atenção humanizada.',                                     'fa-baby'),
('Neurologia',        'Diagnóstico e tratamento de doenças do sistema nervoso central e periférico.',                                                    'fa-brain'),
('Oncologia',         'Prevenção, diagnóstico e tratamento do câncer com equipe multidisciplinar e suporte integral ao paciente.',                       'fa-ribbon'),
('Ginecologia',       'Saúde da mulher em todas as fases da vida, desde a adolescência até o climatério.',                                              'fa-venus'),
('Dermatologia',      'Diagnóstico e tratamento de doenças da pele, cabelos e unhas, incluindo procedimentos estéticos.',                               'fa-hand-dots'),
('Oftalmologia',      'Cuidados completos com a saúde ocular: consultas, cirurgias refrativas e tratamento de doenças oculares.',                       'fa-eye'),
('Endocrinologia',    'Tratamento de distúrbios hormonais como diabetes, obesidade, doenças da tireoide e outras glândulas.',                           'fa-syringe'),
('Psiquiatria',       'Diagnóstico e tratamento de transtornos mentais com abordagem humanizada e multidisciplinar.',                                   'fa-head-side-medical'),
('Urologia',          'Tratamento de doenças do trato urinário em homens e mulheres, e do sistema reprodutor masculino.',                               'fa-kidneys'),
('Reumatologia',      'Diagnóstico e tratamento de doenças autoimunes e inflamatórias que afetam articulações, músculos e ossos.',                     'fa-person-walking');

-- ------------------------------------------------------------
-- Tabela: medicos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `medicos` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nome`         VARCHAR(150)  NOT NULL,
  `especialidade`VARCHAR(100)  NOT NULL,
  `foto`         VARCHAR(255)  NOT NULL DEFAULT 'assets/img/medico-default.jpg',
  `crm`          VARCHAR(30)   NOT NULL,
  `descricao`    TEXT,
  `ativo`        TINYINT(1)    NOT NULL DEFAULT 1,
  `criado_em`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `medicos` (`nome`, `especialidade`, `foto`, `crm`, `descricao`) VALUES
('Dr. Carlos Eduardo Mendes',   'Cardiologista',    'assets/img/medico1.jpg', 'CRM/SP 123456', 'Especialista em cardiologia intervencionista com 15 anos de experiência. Membro da Sociedade Brasileira de Cardiologia.'),
('Dra. Ana Lucia Ferreira',     'Pediatra',         'assets/img/medica2.jpg', 'CRM/SP 234567', 'Pediatra com foco em neonatologia e desenvolvimento infantil. Doutora pela USP com publicações internacionais.'),
('Dr. Roberto Alves Costa',     'Ortopedista',      'assets/img/medico3.jpg', 'CRM/SP 345678', 'Especialista em cirurgia do joelho e quadril. Formado pela UNIFESP com residência no Hospital das Clínicas.'),
('Dra. Juliana Santos Oliveira','Neurologista',     'assets/img/medica4.jpg', 'CRM/SP 456789', 'Neurologista com expertise em epilepsia e doenças neurodegenerativas. Mestre pela UNICAMP.'),
('Dr. Marcos Vinícius Lima',    'Oncologista',      'assets/img/medico5.jpg', 'CRM/SP 567890', 'Oncologista clínico com especialização em tumores sólidos. Fellow do Instituto Nacional do Câncer.'),
('Dra. Patrícia Rocha Dias',    'Ginecologista',    'assets/img/medica6.jpg', 'CRM/SP 678901', 'Ginecologista e obstetra com 12 anos de experiência. Especialista em saúde da mulher e medicina fetal.');

-- ------------------------------------------------------------
-- Tabela: convenios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `convenios` (
  `id`        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nome`      VARCHAR(150)  NOT NULL,
  `logo`      VARCHAR(255)  NOT NULL DEFAULT 'assets/img/convenio-default.png',
  `ativo`     TINYINT(1)    NOT NULL DEFAULT 1,
  `criado_em` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `convenios` (`nome`, `logo`) VALUES
('Unimed',            'assets/img/convenios/unimed.png'),
('Bradesco Saúde',    'assets/img/convenios/bradesco.png'),
('SulAmérica',        'assets/img/convenios/sulamerica.png'),
('Amil',              'assets/img/convenios/amil.png'),
('Porto Seguro Saúde','assets/img/convenios/porto.png'),
('Hapvida',           'assets/img/convenios/hapvida.png'),
('NotreDame Intermédica','assets/img/convenios/notredame.png'),
('Prevent Senior',    'assets/img/convenios/prevent.png'),
('Golden Cross',      'assets/img/convenios/golden.png'),
('CASSI',             'assets/img/convenios/cassi.png');

-- ------------------------------------------------------------
-- Tabela: depoimentos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `depoimentos` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nome`       VARCHAR(150)  NOT NULL,
  `texto`      TEXT          NOT NULL,
  `avaliacao`  TINYINT(1)    NOT NULL DEFAULT 5 COMMENT 'De 1 a 5 estrelas',
  `data`       DATE          NOT NULL,
  `ativo`      TINYINT(1)    NOT NULL DEFAULT 1,
  `criado_em`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT chk_avaliacao CHECK (`avaliacao` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `depoimentos` (`nome`, `texto`, `avaliacao`, `data`) VALUES
('Maria Aparecida Silva',   'Fui atendida com muito carinho e profissionalismo. A equipe de cardiologia é excelente e me senti segura durante todo o tratamento. Recomendo de coração!', 5, '2025-11-10'),
('João Pedro Almeida',      'Minha filha foi tratada pela Dra. Ana Lucia na pediatria. Atendimento humanizado, explicações claras e muita atenção com a pequena. Hospital de referência!', 5, '2025-12-05'),
('Fernanda Costa Ramos',    'Estrutura moderna e limpa, equipe atenciosa e tempo de espera muito razoável. Fiz meu pré-natal aqui e não trocaria por nada. Parabéns ao hospital!', 5, '2026-01-20'),
('Roberto Henrique Souza',  'Excelente atendimento na ortopedia. O Dr. Roberto foi muito atencioso, explicou detalhadamente meu tratamento e o resultado da cirurgia superou minhas expectativas.', 5, '2026-02-14'),
('Cláudia Moreira Pinto',   'Após anos procurando um bom neurologista, encontrei a Dra. Juliana. Profissional incrível, atenciosa e competente. Me sinto muito bem assistida.', 5, '2026-03-08'),
('André Luiz Barbosa',      'Atendimento rápido, profissionais qualificados e instalações impecáveis. O Hospital Santo Expedito é referência de qualidade e confiança em São Paulo.', 4, '2026-04-22');

-- ------------------------------------------------------------
-- Tabela: agendamentos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agendamentos` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nome`            VARCHAR(150)  NOT NULL,
  `email`           VARCHAR(255)  NOT NULL,
  `telefone`        VARCHAR(20)   NOT NULL,
  `especialidade_id`INT UNSIGNED  NOT NULL,
  `mensagem`        TEXT,
  `data_desejada`   DATE          NOT NULL,
  `status`          ENUM('pendente','confirmado','cancelado') NOT NULL DEFAULT 'pendente',
  `criado_em`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_especialidade` (`especialidade_id`),
  KEY `idx_data` (`data_desejada`),
  CONSTRAINT fk_agendamento_especialidade
    FOREIGN KEY (`especialidade_id`) REFERENCES `especialidades`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FIM DO SCRIPT
-- ============================================================
