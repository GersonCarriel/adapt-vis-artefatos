-- Recreate script for adapt_vis_db
-- Safe to run multiple times. Drops and recreates the database, then loads schema.
-- Generated on demand.

-- 1) Drop and create database
DROP DATABASE IF EXISTS adapt_vis_db;
CREATE DATABASE adapt_vis_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE adapt_vis_db;

-- 2) Session settings for import
SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT;
SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS;
SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION;
SET NAMES utf8mb4;
SET @OLD_TIME_ZONE=@@TIME_ZONE;
SET TIME_ZONE='+00:00';
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS; SET UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS; SET FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE; SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET @OLD_SQL_NOTES=@@SQL_NOTES; SET SQL_NOTES=0;

-- 3) Ensure view is dropped before tables just in case
DROP VIEW IF EXISTS v_aluno_estilo;

-- 4) Load schema from embedded dump (tables first, then view)

/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: localhost    Database: adapt_vis_db
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0+deb12u2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `aluno_estilo_scores`
--

DROP TABLE IF EXISTS `aluno_estilo_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aluno_estilo_scores` (
  `aluno_id` bigint(20) NOT NULL,
  `dimensao_id` smallint(6) NOT NULL,
  `score_polo_a` smallint(6) NOT NULL,
  `score_polo_b` smallint(6) NOT NULL,
  `origem` varchar(40) NOT NULL DEFAULT 'questionario',
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`aluno_id`,`dimensao_id`),
  KEY `fk_aes_dimensao` (`dimensao_id`),
  CONSTRAINT `fk_aes_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`usuario_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_aes_dimensao` FOREIGN KEY (`dimensao_id`) REFERENCES `estilo_dimensoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_aes_a` CHECK (`score_polo_a` between 0 and 11),
  CONSTRAINT `chk_aes_b` CHECK (`score_polo_b` between 0 and 11)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alunos`
--

DROP TABLE IF EXISTS `alunos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alunos` (
  `usuario_id` bigint(20) NOT NULL,
  `ra` varchar(100) DEFAULT NULL,
  `turma` varchar(100) DEFAULT NULL,
  `curso_id` bigint(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'ativo',
  `data_matricula` date DEFAULT NULL,
  PRIMARY KEY (`usuario_id`),
  KEY `fk_alunos_curso` (`curso_id`),
  CONSTRAINT `fk_alunos_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_alunos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `atividades_enviadas`
--

DROP TABLE IF EXISTS `atividades_enviadas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `atividades_enviadas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `usuario_id` bigint(20) NOT NULL,
  `status` enum('fazendo','enviado','corrigido') DEFAULT 'fazendo',
  `data_inicio` datetime DEFAULT current_timestamp(),
  `data_envio` datetime DEFAULT NULL,
  `tempo_execucao` int(11) DEFAULT NULL,
  `nome_arquivo` varchar(255) DEFAULT NULL,
  `caminho_local` varchar(500) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `texto_resposta` text DEFAULT NULL,
  `nota` decimal(5,2) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `material_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_material_usuario` (`material_id`,`usuario_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `atividades_enviadas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_atividades_enviadas_material` FOREIGN KEY (`material_id`) REFERENCES `materiais_item` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aulas`
--

DROP TABLE IF EXISTS `aulas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aulas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `curso_id` bigint(20) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT 1,
  `publicado` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_aulas_curso_ordem` (`curso_id`,`ordem`),
  CONSTRAINT `fk_aulas_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aulas_aluno`
--

DROP TABLE IF EXISTS `aulas_aluno`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aulas_aluno` (
  `usuario_id` bigint(20) NOT NULL,
  `aula_id` bigint(20) NOT NULL,
  `liberada_em` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`usuario_id`,`aula_id`),
  KEY `aula_id` (`aula_id`),
  CONSTRAINT `aulas_aluno_ibfk_1` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `checklist_itens`
--

DROP TABLE IF EXISTS `checklist_itens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklist_itens` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `material_id` bigint(20) DEFAULT NULL,
  `ordem` int(11) NOT NULL,
  `texto` text NOT NULL,
  `dica` text DEFAULT NULL,
  `foco` tinyint(1) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_material_checklist` (`material_id`),
  CONSTRAINT `fk_material_checklist` FOREIGN KEY (`material_id`) REFERENCES `materiais_item` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `checklist_status_aluno`
--

DROP TABLE IF EXISTS `checklist_status_aluno`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklist_status_aluno` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `material_id` bigint(20) NOT NULL,
  `checklist_item_id` bigint(20) NOT NULL,
  `usuario_id` bigint(20) NOT NULL,
  `checked` tinyint(1) DEFAULT 0,
  `marcado_em` datetime DEFAULT NULL,
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_material_checklist_usuario` (`material_id`,`checklist_item_id`,`usuario_id`),
  KEY `fk_checklist_item` (`checklist_item_id`),
  CONSTRAINT `fk_checklist_item` FOREIGN KEY (`checklist_item_id`) REFERENCES `checklist_itens` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `conhecimento_dependencias`
--

DROP TABLE IF EXISTS `conhecimento_dependencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `conhecimento_dependencias` (
  `conhecimento_id` bigint(20) NOT NULL,
  `depende_de_id` bigint(20) NOT NULL,
  PRIMARY KEY (`conhecimento_id`,`depende_de_id`),
  KEY `depende_de_id` (`depende_de_id`),
  CONSTRAINT `conhecimento_dependencias_ibfk_1` FOREIGN KEY (`conhecimento_id`) REFERENCES `conhecimentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conhecimento_dependencias_ibfk_2` FOREIGN KEY (`depende_de_id`) REFERENCES `conhecimentos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `conhecimentos`
--

DROP TABLE IF EXISTS `conhecimentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `conhecimentos` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(100) DEFAULT NULL,
  `descricao` varchar(400) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `conteudos`
--

DROP TABLE IF EXISTS `conteudos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `conteudos` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `aula_id` bigint(20) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_conteudos_aula_ordem` (`aula_id`,`ordem`),
  CONSTRAINT `fk_conteudos_aula` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cursos`
--

DROP TABLE IF EXISTS `cursos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cursos` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) NOT NULL,
  `descricao` text DEFAULT NULL,
  `publicado` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `estilo_dimensoes`
--

DROP TABLE IF EXISTS `estilo_dimensoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `estilo_dimensoes` (
  `id` smallint(6) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nome` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `estilo_polos`
--

DROP TABLE IF EXISTS `estilo_polos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `estilo_polos` (
  `id` smallint(6) NOT NULL,
  `dimensao_id` smallint(6) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `lado` char(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dimensao_id` (`dimensao_id`,`codigo`),
  CONSTRAINT `fk_polos_dim` FOREIGN KEY (`dimensao_id`) REFERENCES `estilo_dimensoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `interacoes_aluno`
--

DROP TABLE IF EXISTS `interacoes_aluno`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `interacoes_aluno` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `usuario_id` bigint(20) NOT NULL,
  `item_id` bigint(20) NOT NULL,
  `material_id` bigint(20) NOT NULL,
  `data_acesso` datetime DEFAULT current_timestamp(),
  `data_conclusao` datetime DEFAULT NULL,
  `tempo_utilizado_segundos` int(11) DEFAULT 0,
  `tempo_pos_concluido_segundos` int(11) DEFAULT 0,
  `concluido` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `item_id` (`item_id`),
  KEY `material_id` (`material_id`),
  CONSTRAINT `interacoes_aluno_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `alunos` (`usuario_id`),
  CONSTRAINT `interacoes_aluno_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `itens_aula` (`id`),
  CONSTRAINT `interacoes_aluno_ibfk_3` FOREIGN KEY (`material_id`) REFERENCES `materiais_item` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `interacoes_extras_sugestoes`
--

DROP TABLE IF EXISTS `interacoes_extras_sugestoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `interacoes_extras_sugestoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` bigint(20) NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `dimensao_id` smallint(6) DEFAULT NULL,
  `polo_id` smallint(6) DEFAULT NULL,
  `data_acesso` datetime NOT NULL,
  `tempo_utilizado_segundos` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_ies_usuario` (`usuario_id`),
  KEY `fk_ies_dimensao` (`dimensao_id`),
  KEY `fk_ies_polo` (`polo_id`),
  CONSTRAINT `fk_ies_dimensao` FOREIGN KEY (`dimensao_id`) REFERENCES `estilo_dimensoes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ies_polo` FOREIGN KEY (`polo_id`) REFERENCES `estilo_polos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ies_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `item_conhecimento`
--

DROP TABLE IF EXISTS `item_conhecimento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_conhecimento` (
  `item_id` bigint(20) NOT NULL,
  `conhecimento_id` bigint(20) NOT NULL,
  PRIMARY KEY (`item_id`,`conhecimento_id`),
  KEY `fk_ic_conh` (`conhecimento_id`),
  CONSTRAINT `fk_ic_conh` FOREIGN KEY (`conhecimento_id`) REFERENCES `conhecimentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ic_item` FOREIGN KEY (`item_id`) REFERENCES `itens_aula` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `item_estilo`
--

DROP TABLE IF EXISTS `item_estilo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_estilo` (
  `item_id` bigint(20) NOT NULL,
  `dimensao_id` smallint(6) NOT NULL,
  `polo_id` smallint(6) NOT NULL,
  `adequacao` smallint(6) NOT NULL,
  PRIMARY KEY (`item_id`,`dimensao_id`,`polo_id`),
  KEY `fk_ie_dim` (`dimensao_id`),
  KEY `fk_ie_polo` (`polo_id`),
  CONSTRAINT `fk_ie_dim` FOREIGN KEY (`dimensao_id`) REFERENCES `estilo_dimensoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ie_item` FOREIGN KEY (`item_id`) REFERENCES `itens_aula` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ie_polo` FOREIGN KEY (`polo_id`) REFERENCES `estilo_polos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_ie_adeq` CHECK (`adequacao` between 1 and 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `itens_aula`
--

DROP TABLE IF EXISTS `itens_aula`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `itens_aula` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `conteudo_id` bigint(20) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `exibir_no_menu` tinyint(1) DEFAULT 1,
  `dimensao_principal` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_itens_conteudo_ordem` (`conteudo_id`,`ordem`),
  CONSTRAINT `fk_itens_conteudo` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `materiais_entregues_aluno`
--

DROP TABLE IF EXISTS `materiais_entregues_aluno`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `materiais_entregues_aluno` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `usuario_id` bigint(20) NOT NULL,
  `item_id` bigint(20) NOT NULL,
  `material_id` bigint(20) NOT NULL,
  `selecionado_em` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `item_id` (`item_id`),
  KEY `material_id` (`material_id`),
  CONSTRAINT `materiais_entregues_aluno_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `alunos` (`usuario_id`),
  CONSTRAINT `materiais_entregues_aluno_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `itens_aula` (`id`),
  CONSTRAINT `materiais_entregues_aluno_ibfk_3` FOREIGN KEY (`material_id`) REFERENCES `materiais_item` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `materiais_item`
--

DROP TABLE IF EXISTS `materiais_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `materiais_item` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `item_id` bigint(20) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `url` text DEFAULT NULL,
  `dimensao` varchar(10) DEFAULT NULL,
  `polo` varchar(3) DEFAULT NULL,
  `balanceado` tinyint(1) DEFAULT 0,
  `texto_html` text DEFAULT NULL,
  `texto_pre_atividade` text DEFAULT NULL,
  `texto_pos_atividade` text DEFAULT NULL,
  `caminho_local` text DEFAULT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `duracao_seg` int(11) DEFAULT NULL,
  `ordem` int(11) DEFAULT 1,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `exibir_no_menu` tinyint(1) DEFAULT 1,
  `item_pai_id` bigint(20) DEFAULT NULL,
  `recebe_atividade` tinyint(1) DEFAULT 0,
  `descricao_atividade` text DEFAULT NULL,
  `prazo_entrega` datetime DEFAULT NULL,
  `tipo_devolutiva` varchar(20) DEFAULT NULL,
  `grau` int(11) DEFAULT NULL CHECK (`grau` in (1,3,5,7,9,11)),
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `materiais_item_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `itens_aula` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `perfil_cognitivo_usuario`
--

DROP TABLE IF EXISTS `perfil_cognitivo_usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `perfil_cognitivo_usuario` (
  `usuario_id` bigint(20) NOT NULL,
  `dimensao` varchar(10) NOT NULL,
  `desc_dimensao` enum('processamento','entrada','percepcao','organizacao') NOT NULL,
  `polo_dominante` varchar(3) NOT NULL,
  `score_maior` int(11) NOT NULL,
  `score_menor` int(11) NOT NULL,
  `intensidade` int(11) NOT NULL CHECK (`intensidade` in (1,3,5,7,9,11)),
  `classificacao` varchar(15) GENERATED ALWAYS AS (case when `intensidade` between 1 and 3 then 'balanceado' when `intensidade` between 5 and 7 then 'moderado' when `intensidade` between 9 and 11 then 'forte' else 'indefinido' end) STORED,
  PRIMARY KEY (`usuario_id`,`dimensao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pergunta_conhecimento`
--

DROP TABLE IF EXISTS `pergunta_conhecimento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pergunta_conhecimento` (
  `pergunta_id` bigint(20) NOT NULL,
  `conhecimento_id` bigint(20) NOT NULL,
  PRIMARY KEY (`pergunta_id`,`conhecimento_id`),
  KEY `fk_pk_conhecimento` (`conhecimento_id`),
  CONSTRAINT `fk_pk_conhecimento` FOREIGN KEY (`conhecimento_id`) REFERENCES `conhecimentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pk_pergunta` FOREIGN KEY (`pergunta_id`) REFERENCES `quiz_perguntas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `professores`
--

DROP TABLE IF EXISTS `professores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `professores` (
  `usuario_id` bigint(20) NOT NULL,
  `siape` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `area_atuacao` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'ativo',
  `data_contratacao` date DEFAULT NULL,
  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `fk_professores_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quiz_opcoes`
--

DROP TABLE IF EXISTS `quiz_opcoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_opcoes` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pergunta_id` bigint(20) NOT NULL,
  `texto` text NOT NULL,
  `correta` tinyint(1) NOT NULL DEFAULT 0,
  `ordem` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_opcoes_pergunta_ordem` (`pergunta_id`,`ordem`),
  CONSTRAINT `fk_opcao_pergunta` FOREIGN KEY (`pergunta_id`) REFERENCES `quiz_perguntas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=193 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quiz_perguntas`
--

DROP TABLE IF EXISTS `quiz_perguntas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_perguntas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `quiz_id` bigint(20) NOT NULL,
  `pergunta_tipo` varchar(30) NOT NULL,
  `enunciado` text NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_perguntas_quiz_ordem` (`quiz_id`,`ordem`),
  CONSTRAINT `fk_pergunta_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quiz_respostas`
--

DROP TABLE IF EXISTS `quiz_respostas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_respostas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tentativa_id` bigint(20) NOT NULL,
  `pergunta_id` bigint(20) NOT NULL,
  `opcao_id` bigint(20) DEFAULT NULL,
  `resposta_texto` text DEFAULT NULL,
  `correta` tinyint(1) DEFAULT NULL,
  `pontos` decimal(6,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_resp_pergunta` (`pergunta_id`),
  KEY `fk_resp_opcao` (`opcao_id`),
  KEY `idx_respostas_tentativa` (`tentativa_id`),
  CONSTRAINT `fk_resp_opcao` FOREIGN KEY (`opcao_id`) REFERENCES `quiz_opcoes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_resp_pergunta` FOREIGN KEY (`pergunta_id`) REFERENCES `quiz_perguntas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_resp_tentativa` FOREIGN KEY (`tentativa_id`) REFERENCES `quiz_tentativas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quiz_tentativas`
--

DROP TABLE IF EXISTS `quiz_tentativas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_tentativas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `quiz_id` bigint(20) NOT NULL,
  `aluno_id` bigint(20) NOT NULL,
  `iniciado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `finalizado_em` datetime DEFAULT NULL,
  `score_pct` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tent_aluno` (`aluno_id`),
  KEY `idx_tentativas_quiz_aluno` (`quiz_id`,`aluno_id`),
  CONSTRAINT `fk_tent_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`usuario_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tent_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quizzes`
--

DROP TABLE IF EXISTS `quizzes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quizzes` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `aula_id` bigint(20) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `instrucoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aula_id` (`aula_id`),
  CONSTRAINT `fk_quiz_aula` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `respostas_quiz`
--

DROP TABLE IF EXISTS `respostas_quiz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `respostas_quiz` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `pergunta_id` int(11) NOT NULL,
  `opcao_id` int(11) NOT NULL,
  `data_resposta` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_pergunta` (`usuario_id`,`pergunta_id`)
) ENGINE=InnoDB AUTO_INCREMENT=495 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `nome` varchar(200) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `foto_url` text DEFAULT NULL,
  `lti_sub` varchar(255) DEFAULT NULL,
  `lti_issuer` varchar(255) DEFAULT NULL,
  `ultimo_login_ts` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `moodle_user` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_usuarios_lti` (`lti_issuer`,`lti_sub`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `v_aluno_estilo`
--

DROP TABLE IF EXISTS `v_aluno_estilo`;
/*!50001 DROP VIEW IF EXISTS `v_aluno_estilo`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `v_aluno_estilo` AS SELECT
 1 AS `aluno_id`,
  1 AS `dimensao_id`,
  1 AS `dimensao_codigo`,
  1 AS `dimensao_nome`,
  1 AS `score_polo_a`,
  1 AS `score_polo_b`,
  1 AS `polo_predominante`,
  1 AS `score_predominante`,
  1 AS `classificacao`,
  1 AS `polo_codigo`,
  1 AS `polo_nome`,
  1 AS `origem`,
  1 AS `atualizado_em` */;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `v_aluno_estilo`
--

/*!50001 DROP VIEW IF EXISTS `v_aluno_estilo`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_aluno_estilo` AS select `aes`.`aluno_id` AS `aluno_id`,`aes`.`dimensao_id` AS `dimensao_id`,`d`.`codigo` AS `dimensao_codigo`,`d`.`nome` AS `dimensao_nome`,`aes`.`score_polo_a` AS `score_polo_a`,`aes`.`score_polo_b` AS `score_polo_b`,case when `aes`.`score_polo_a` > `aes`.`score_polo_b` then 'A' when `aes`.`score_polo_b` > `aes`.`score_polo_a` then 'B' else 'A' end AS `polo_predominante`,greatest(`aes`.`score_polo_a`,`aes`.`score_polo_b`) AS `score_predominante`,case when greatest(`aes`.`score_polo_a`,`aes`.`score_polo_b`) in (9,11) then 'forte' when greatest(`aes`.`score_polo_a`,`aes`.`score_polo_b`) in (5,7) then 'moderada' else 'balanceada' end AS `classificacao`,case when `aes`.`score_polo_a` > `aes`.`score_polo_b` then `pA`.`codigo` else `pB`.`codigo` end AS `polo_codigo`,case when `aes`.`score_polo_a` > `aes`.`score_polo_b` then `pA`.`nome` else `pB`.`nome` end AS `polo_nome`,`aes`.`origem` AS `origem`,`aes`.`atualizado_em` AS `atualizado_em` from (((`aluno_estilo_scores` `aes` join `estilo_dimensoes` `d` on(`d`.`id` = `aes`.`dimensao_id`)) join `estilo_polos` `pA` on(`pA`.`dimensao_id` = `d`.`id` and `pA`.`lado` = 'A')) join `estilo_polos` `pB` on(`pB`.`dimensao_id` = `d`.`id` and `pB`.`lado` = 'B')) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-09 16:00:40


-- 5) Restore session settings
SET TIME_ZONE=@OLD_TIME_ZONE;
SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT;
SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS;
SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION;
SET SQL_NOTES=@OLD_SQL_NOTES;
