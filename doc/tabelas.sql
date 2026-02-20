-- phpMyAdmin SQL Dump
-- version 4.0.0
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 13, 2025 at 06:09 PM
-- Server version: 10.1.45-MariaDB-0+deb9u1
-- PHP Version: 7.1.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `mkradius`
--

-- --------------------------------------------------------

--
-- Table structure for table `sis_cliente`
--

CREATE TABLE IF NOT EXISTS `sis_cliente` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `bairro` varchar(255) DEFAULT NULL,
  `cidade` varchar(255) DEFAULT NULL,
  `cep` varchar(9) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `cpf_cnpj` varchar(20) DEFAULT NULL,
  `fone` varchar(50) DEFAULT NULL,
  `obs` text,
  `nascimento` varchar(32) DEFAULT NULL,
  `estado_civil` enum('S','C','D','V') DEFAULT 'S',
  `cadastro` varchar(32) DEFAULT NULL,
  `login` varchar(64) DEFAULT NULL,
  `tipo` varchar(10) DEFAULT NULL,
  `night` varchar(3) DEFAULT 'nao',
  `aviso` text,
  `foto` varchar(255) DEFAULT NULL,
  `venc` varchar(2) DEFAULT '01',
  `mac` varchar(17) DEFAULT NULL,
  `complemento` varchar(255) DEFAULT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `ramal` varchar(32) DEFAULT NULL,
  `rg` varchar(32) DEFAULT NULL,
  `isento` varchar(3) DEFAULT 'nao',
  `celular` varchar(32) DEFAULT NULL,
  `bloqueado` enum('sim','nao') DEFAULT 'nao',
  `autoip` enum('sim','nao') DEFAULT 'nao',
  `automac` enum('sim','nao') DEFAULT 'nao',
  `conta` varchar(11) DEFAULT '1',
  `ipvsix` varchar(255) DEFAULT NULL,
  `plano` varchar(64) DEFAULT NULL,
  `send` varchar(3) DEFAULT 'nao',
  `cli_ativado` enum('s','n') DEFAULT 's',
  `simultaneo` varchar(3) DEFAULT 'nao',
  `turbo` varchar(64) DEFAULT NULL,
  `comodato` varchar(3) DEFAULT 'nao',
  `observacao` enum('sim','nao') DEFAULT 'nao',
  `chavetipo` varchar(10) DEFAULT NULL,
  `chave` varchar(255) DEFAULT NULL,
  `contrato` varchar(32) NOT NULL,
  `ssid` varchar(64) DEFAULT NULL,
  `senha` varchar(32) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `responsavel` varchar(255) DEFAULT NULL,
  `nome_pai` varchar(255) DEFAULT NULL,
  `nome_mae` varchar(255) DEFAULT NULL,
  `expedicao_rg` varchar(20) DEFAULT NULL,
  `naturalidade` varchar(50) DEFAULT NULL,
  `acessacen` varchar(50) DEFAULT 'sim',
  `pessoa` varchar(10) DEFAULT 'fisica',
  `endereco_res` varchar(255) DEFAULT NULL,
  `numero_res` varchar(20) DEFAULT NULL,
  `bairro_res` varchar(255) DEFAULT NULL,
  `cidade_res` varchar(255) DEFAULT NULL,
  `cep_res` varchar(9) DEFAULT NULL,
  `estado_res` varchar(2) DEFAULT NULL,
  `complemento_res` varchar(255) DEFAULT NULL,
  `desconto` decimal(12,2) DEFAULT '0.00',
  `acrescimo` decimal(12,2) DEFAULT '0.00',
  `equipamento` varchar(20) DEFAULT 'nenhum',
  `vendedor` varchar(255) DEFAULT NULL,
  `nextel` varchar(50) DEFAULT NULL,
  `accesslist` enum('sim','nao') NOT NULL DEFAULT 'nao',
  `resumo` varchar(6) DEFAULT '032011',
  `grupo` varchar(50) DEFAULT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `prilanc` enum('pro','tot') NOT NULL DEFAULT 'pro',
  `tipobloq` enum('aut','man') NOT NULL DEFAULT 'aut',
  `adesao` decimal(12,2) DEFAULT '0.00',
  `mbdisco` int(12) NOT NULL DEFAULT '100',
  `sms` enum('sim','nao') DEFAULT 'sim',
  `zap` enum('sim','nao') DEFAULT 'sim',
  `ltrafego` bigint(11) DEFAULT '0',
  `planodown` varchar(255) DEFAULT 'nenhum',
  `ligoudown` varchar(6) DEFAULT '012011',
  `statusdown` enum('on','off') NOT NULL DEFAULT 'off',
  `statusturbo` enum('on','off') NOT NULL DEFAULT 'off',
  `opcelular` varchar(100) DEFAULT 'nenhuma',
  `nome_res` varchar(255) DEFAULT NULL,
  `coordenadas` varchar(64) DEFAULT NULL,
  `rem_obs` datetime DEFAULT NULL,
  `valor_sva` decimal(12,2) DEFAULT '0.00',
  `dias_corte` int(3) DEFAULT '999',
  `user_ip` varchar(100) DEFAULT NULL,
  `user_mac` varchar(100) DEFAULT NULL,
  `data_ip` datetime DEFAULT NULL,
  `data_mac` datetime DEFAULT NULL,
  `last_update` datetime DEFAULT NULL,
  `data_bloq` datetime DEFAULT NULL,
  `tags` longtext,
  `tecnico` varchar(255) DEFAULT NULL,
  `data_ins` datetime DEFAULT NULL,
  `altsenha` enum('sim','nao') DEFAULT NULL,
  `geranfe` enum('sim','nao') DEFAULT 'sim',
  `mesref` enum('now','ant') DEFAULT 'ant',
  `ipfall` varchar(32) DEFAULT NULL,
  `tit_abertos` int(12) DEFAULT NULL,
  `parc_abertas` int(12) DEFAULT NULL,
  `tipo_pessoa` int(1) DEFAULT NULL,
  `celular2` varchar(32) DEFAULT NULL,
  `mac_serial` varchar(255) DEFAULT NULL,
  `status_corte` enum('full','down','bloq') DEFAULT 'full',
  `plano15` varchar(255) DEFAULT 'nenhum',
  `pgaviso` enum('sim','nao') DEFAULT 'sim',
  `porta_olt` varchar(32) DEFAULT NULL,
  `caixa_herm` varchar(128) DEFAULT NULL,
  `porta_splitter` varchar(32) DEFAULT NULL,
  `onu_ont` varchar(64) DEFAULT NULL,
  `switch` varchar(128) DEFAULT NULL,
  `tit_vencidos` int(12) DEFAULT NULL,
  `pgcorte` enum('sim','nao') DEFAULT 'sim',
  `interface` varchar(128) DEFAULT NULL,
  `login_atend` varchar(63) DEFAULT 'full_users',
  `cidade_ibge` varchar(16) DEFAULT NULL,
  `estado_ibge` varchar(8) DEFAULT NULL,
  `data_desbloq` datetime DEFAULT '2015-01-01 00:00:00',
  `pool_name` varchar(30) DEFAULT 'nenhum',
  `pool6` varchar(48) DEFAULT NULL,
  `rec_email` enum('sim','nao') DEFAULT 'sim',
  `termo` varchar(16) DEFAULT NULL,
  `opcelular2` varchar(32) DEFAULT 'nenhuma',
  `dot_ref` varchar(128) DEFAULT NULL,
  `tipo_cliente` int(2) DEFAULT '99',
  `armario_olt` varchar(96) DEFAULT NULL,
  `conta_cartao` int(11) DEFAULT '0',
  `plano_bloqc` varchar(64) DEFAULT 'nenhum',
  `uuid_cliente` varchar(48) DEFAULT NULL,
  `data_desativacao` datetime DEFAULT NULL,
  `tipo_cob` enum('titulo','carne') DEFAULT NULL,
  `fortunus` tinyint(1) DEFAULT '0',
  `gsici` tinyint(1) DEFAULT '1',
  `local_dici` enum('u','r') DEFAULT 'u',
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  KEY `nome` (`nome`),
  KEY `rg` (`rg`),
  KEY `ip` (`ip`),
  KEY `mac` (`mac`),
  KEY `accesslist` (`accesslist`),
  KEY `tipo` (`tipo`),
  KEY `senha` (`senha`),
  KEY `plano` (`plano`),
  KEY `ativo2` (`cli_ativado`),
  KEY `bloqueado` (`bloqueado`),
  KEY `observacao` (`observacao`),
  KEY `ramal` (`ramal`),
  KEY `desconto` (`desconto`),
  KEY `acrescimo` (`acrescimo`),
  KEY `pgaviso` (`pgaviso`),
  KEY `pgcorte` (`pgcorte`),
  KEY `interface` (`interface`),
  KEY `tit_abertos` (`tit_abertos`),
  KEY `parc_abertas` (`parc_abertas`),
  KEY `tit_vencidos` (`tit_vencidos`),
  KEY `login_atend` (`login_atend`),
  KEY `rec_email` (`rec_email`),
  KEY `plano_bloqc` (`plano_bloqc`),
  KEY `dot_ref` (`dot_ref`),
  KEY `uuid_cliente` (`uuid_cliente`)
) ENGINE=TokuDB  DEFAULT CHARSET=latin1 `compression`='tokudb_zlib' AUTO_INCREMENT=923 ;

--
-- Triggers `sis_cliente`
--
DROP TRIGGER IF EXISTS `tig_cliente`;
DELIMITER //
CREATE TRIGGER `tig_cliente` AFTER UPDATE ON `sis_cliente`
 FOR EACH ROW BEGIN
	DECLARE senha_anterior, senha_atual, var_pgcorte, var_bloqueado varchar(64);

	SELECT value INTO senha_anterior FROM radcheck WHERE attribute = 'Password' AND username = new.login LIMIT 1;
	SET senha_atual = new.senha;

	SELECT valor INTO var_pgcorte FROM sis_opcao WHERE nome = 'pgcorte' LIMIT 1;
	SET var_bloqueado = new.bloqueado;

	IF (var_pgcorte = 'nao' AND var_bloqueado = 'sim') THEN
		UPDATE radcheck SET ativo = 'n' WHERE username = new.login;
	END IF;
	IF (BINARY(senha_atual) <> BINARY(senha_anterior)) THEN
		UPDATE radcheck SET value = senha_atual WHERE attribute = 'Password' AND username = new.login;
	END IF;
END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `sis_lanc`
--

CREATE TABLE IF NOT EXISTS `sis_lanc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datavenc` datetime DEFAULT NULL,
  `nossonum` varchar(64) DEFAULT NULL,
  `datapag` datetime DEFAULT NULL,
  `nome` varchar(16) DEFAULT NULL,
  `recibo` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT 'aberto',
  `login` varchar(255) DEFAULT NULL,
  `tipo` varchar(255) DEFAULT NULL,
  `cfop_lanc` varchar(8) DEFAULT '5307',
  `obs` varchar(255) DEFAULT NULL,
  `processamento` datetime DEFAULT NULL,
  `aviso` varchar(3) DEFAULT 'nao',
  `url` longtext,
  `usergerou` varchar(20) DEFAULT NULL,
  `valorger` varchar(20) DEFAULT 'completo',
  `coletor` varchar(20) DEFAULT NULL,
  `linhadig` varchar(255) DEFAULT NULL,
  `valor` varchar(50) DEFAULT NULL,
  `valorpag` varchar(50) DEFAULT NULL,
  `gwt_numero` varchar(32) DEFAULT NULL,
  `imp` enum('sim','nao') NOT NULL DEFAULT 'nao',
  `referencia` varchar(8) DEFAULT NULL,
  `tipocob` enum('fat','car') NOT NULL DEFAULT 'fat',
  `codigo_carne` varchar(32) DEFAULT NULL,
  `chave_gnet` varchar(32) DEFAULT NULL,
  `chave_gnet2` varchar(32) DEFAULT NULL,
  `chave_juno` varchar(32) DEFAULT NULL,
  `chave_galaxpay` varchar(32) DEFAULT NULL,
  `chave_iugu` varchar(96) DEFAULT NULL,
  `numconta` int(11) DEFAULT NULL,
  `gerourem` tinyint(1) DEFAULT '0',
  `remvalor` decimal(12,2) DEFAULT '0.00',
  `remdata` datetime DEFAULT NULL,
  `formapag` varchar(100) DEFAULT NULL,
  `fcartaobandeira` varchar(100) DEFAULT NULL,
  `fcartaonumero` varchar(32) DEFAULT NULL,
  `fchequenumero` varchar(100) DEFAULT NULL,
  `fchequebanco` varchar(100) DEFAULT NULL,
  `fchequeagcc` varchar(100) DEFAULT NULL,
  `percmulta` decimal(4,2) DEFAULT '0.00',
  `valormulta` decimal(12,2) DEFAULT '0.00',
  `percmora` decimal(4,2) DEFAULT '0.00',
  `valormora` decimal(12,2) DEFAULT '0.00',
  `percdesc` decimal(4,2) DEFAULT '0.00',
  `valordesc` decimal(12,2) DEFAULT '0.00',
  `deltitulo` tinyint(1) DEFAULT '0',
  `datadel` datetime DEFAULT NULL,
  `num_recibos` int(11) DEFAULT '0',
  `num_retornos` int(11) DEFAULT '0',
  `alt_venc` tinyint(1) DEFAULT '0',
  `uuid_lanc` varchar(48) DEFAULT NULL,
  `tarifa_paga` decimal(12,2) DEFAULT '0.00',
  `id_empresa` varchar(16) DEFAULT NULL,
  `oco01` tinyint(1) DEFAULT '0',
  `oco02` tinyint(1) DEFAULT '0',
  `oco06` tinyint(1) DEFAULT '0',
  `codigo_barras` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recibo` (`recibo`),
  KEY `datavenc` (`datavenc`),
  KEY `nossonum` (`nossonum`),
  KEY `login` (`login`),
  KEY `usergerou` (`usergerou`),
  KEY `codigo_carne` (`codigo_carne`),
  KEY `remvalor` (`remvalor`),
  KEY `status` (`status`),
  KEY `deltitulo` (`deltitulo`),
  KEY `nome` (`nome`),
  KEY `uuid_lanc` (`uuid_lanc`),
  KEY `remdata` (`remdata`),
  KEY `id_empresa` (`id_empresa`),
  KEY `alt_venc` (`alt_venc`),
  KEY `oco01` (`oco01`),
  KEY `oco02` (`oco02`),
  KEY `oco06` (`oco06`)
) ENGINE=TokuDB  DEFAULT CHARSET=latin1 `compression`='tokudb_zlib' AUTO_INCREMENT=30656 ;
