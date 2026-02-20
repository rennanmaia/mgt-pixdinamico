-- phpMyAdmin SQL Dump
-- version 4.0.0
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 13, 2025 at 06:42 PM
-- Server version: 10.1.45-MariaDB-0+deb9u1
-- PHP Version: 7.1.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `mkradius`
--

-- --------------------------------------------------------

--
-- Table structure for table `sis_caixa`
--

CREATE TABLE IF NOT EXISTS `sis_caixa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid_caixa` varchar(48) DEFAULT NULL,
  `usuario` varchar(50) DEFAULT NULL,
  `data` datetime DEFAULT NULL,
  `historico` varchar(255) DEFAULT NULL,
  `complemento` longtext,
  `entrada` decimal(12,2) DEFAULT NULL,
  `saida` decimal(12,2) DEFAULT NULL,
  `tipomov` enum('aut','man') DEFAULT 'aut',
  `planodecontas` varchar(50) DEFAULT 'Outros',
  PRIMARY KEY (`id`),
  KEY `data` (`data`),
  KEY `uuid_caixa` (`uuid_caixa`)
) ENGINE=TokuDB  DEFAULT CHARSET=latin1 `compression`='tokudb_zlib' AUTO_INCREMENT=32321 ;
