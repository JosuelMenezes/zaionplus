-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 06/03/2025 às 22:04
-- Versão do servidor: 5.7.23-23
-- Versão do PHP: 8.1.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `dunkac76_uzumaki`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `telefone` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `empresa` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `limite_compra` decimal(10,2) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome`, `telefone`, `empresa`, `limite_compra`, `data_cadastro`) VALUES
(1, 'Josuel Menezes', '61991190352', 'Dunka', 1000.00, '2025-02-26 00:43:15'),
(2, 'Estefany Menezes', '61992086827', 'Cantinho Niely', 1000.00, '2025-02-26 01:39:39'),
(3, 'Rodrigo Santos', '61998607252', 'Domaria', 1000.00, '2025-02-26 15:18:39'),
(4, 'Rita de Cassaio ', '61993838813', 'Domaria', 200.00, '2025-02-26 20:01:57'),
(5, 'ADM GRATIDÃO LIFE', '61 98143-1753', 'GRATIDÃO LIFE', 600.00, '2025-02-26 21:14:35'),
(7, 'ELISANGELA GRATIDÃO LIFE', '', 'GRATIDÃO LIFE', 0.00, '2025-02-26 21:14:35'),
(8, 'EMANUELY GRATIDÃO LIFE', '61 9644-6483', 'GRATIDÃO LIFE', 300.00, '2025-02-26 21:14:35'),
(9, 'GABRIELA GRATIDÃO LIFE', '61995169762', 'GRATIDÃO LIFE', 300.00, '2025-02-26 21:14:35'),
(10, 'MARCONI GRATIDÃO LIFE', '61 98143-1753', 'GRATIDÃO LIFE', 600.00, '2025-02-26 21:14:35'),
(11, 'RAISSA GRATIDÃO LIFE', '61 9288-0712', 'GRATIDÃO LIFE', 300.00, '2025-02-26 21:14:35'),
(12, 'SAMUEL GRATIDÃO LIFE', '61 98492-5618', 'GRATIDÃO LIFE', 600.00, '2025-02-26 21:14:35'),
(13, 'THERTULANE GRATIDÃO LIFE', '', 'GRATIDÃO LIFE', 0.00, '2025-02-26 21:14:35'),
(14, 'YASMIN GRATIDÃO LIFE', '61 9854-1324', 'GRATIDÃO LIFE', 300.00, '2025-02-26 21:14:35'),
(15, 'KAUANE KAKA  INFINITI', '6193168203', 'INFINITI', 300.00, '2025-02-26 21:14:35'),
(16, 'MARCOS INFINITI', '', 'INFINITI', 0.00, '2025-02-26 21:14:35'),
(17, 'RAIANE CAJU INFINITI', '(61) 99340-4125', 'INFINITI', 600.00, '2025-02-26 21:14:35'),
(18, 'THAINAH INFINITI', '', 'INFINITI', 0.00, '2025-02-26 21:14:35'),
(19, 'NICOLE INFINITI', '61 98569-2833', 'INFINITI', 200.00, '2025-02-26 21:14:35'),
(20, 'LISSANDRA INFINITI', '61992997481', 'INFINITI', 200.00, '2025-02-26 21:14:35'),
(21, 'PRISCILA INFINITI', '(61) 99527-2417', 'INFINITI', 300.00, '2025-02-26 21:14:35'),
(22, 'HELAINE IPHAC', '', 'IPHAC', 0.00, '2025-02-26 21:14:35'),
(23, 'LOURDES IPHAC', '', 'IPHAC', 0.00, '2025-02-26 21:14:35'),
(24, 'PATY IPHAC', '', 'IPHAC', 0.00, '2025-02-26 21:14:35'),
(25, 'ROBERTO RUSIVELT IPHAC', '', 'IPHAC', 0.00, '2025-02-26 21:14:35'),
(26, 'SANDY IPHAC', '', 'IPHAC', 0.00, '2025-02-26 21:14:35'),
(27, 'BARBARA SUPERVISORA', '', 'MANTEVIDA', 0.00, '2025-02-26 21:14:35'),
(28, 'DANI', '(61) 99345-1089', 'MANTEVIDA', 300.00, '2025-02-26 21:14:35'),
(29, 'HORTENCIA', '', 'MANTEVIDA', 0.00, '2025-02-26 21:14:35'),
(30, 'JOANA', '', 'MANTEVIDA', 0.00, '2025-02-26 21:14:35'),
(31, 'RAY RAIANE', '', 'MANTEVIDA', 0.00, '2025-02-26 21:14:35'),
(32, 'RENATA', '(61) 99219-8684', 'MANTEVIDA', 300.00, '2025-02-26 21:14:35'),
(33, 'JESSICA', '', 'MANTEVIDA', 0.00, '2025-02-26 21:14:35'),
(34, 'KELLY', '', 'MANTEVIDA', 0.00, '2025-02-26 21:14:35'),
(35, 'SARA SILVA', '', 'MANTEVIDA', 0.00, '2025-02-26 21:14:35'),
(36, 'ANA MARIA - sala 106', '61 993691857', 'CLINICA TASSIO MACIEL', 300.00, '2025-02-26 21:14:35'),
(37, 'GUSTAVO - sala 106', '', 'CLINICA TASSIO MACIEL', 0.00, '2025-02-26 21:14:35'),
(38, 'LILIANE - sala 106', '', 'CLINICA TASSIO MACIEL', 0.00, '2025-02-26 21:14:35'),
(39, 'LUCAS - sala 106', '', 'CLINICA TASSIO MACIEL', 0.00, '2025-02-26 21:14:35'),
(40, 'NAYARA - sala 106', '', 'CLINICA TASSIO MACIEL', 0.00, '2025-02-26 21:14:35'),
(41, 'TASSIO - sala 106', '', 'CLINICA TASSIO MACIEL', 0.00, '2025-02-26 21:14:35'),
(42, 'DANUZIA', '', 'VIVER E SER', 0.00, '2025-02-26 21:14:35'),
(43, 'REBECA', '', 'VIVER E SER', 0.00, '2025-02-26 21:14:35'),
(44, 'RENATA', '', 'VIVER E SER', 0.00, '2025-02-26 21:14:35'),
(45, 'FLAVIA', '', 'COMPASSIO', 0.00, '2025-02-26 21:14:35'),
(46, 'JULIANA', '', 'COMPASSIO', 0.00, '2025-02-26 21:14:35'),
(47, 'LARISSA', '', 'COMPASSIO', 0.00, '2025-02-26 21:14:35'),
(48, 'HUMBERTO', '', 'ENGENHEIRO', 0.00, '2025-02-26 21:14:35'),
(49, 'teste', '61991190352', 'Mais nos', 2.00, '2025-02-27 01:56:02'),
(50, 'Jéssica Ohara ', '61 98133-1581', 'TLK', 200.00, '2025-02-28 17:49:17'),
(51, 'ADM INFINITI', '6199999999', 'INFINITI', 600.00, '2025-02-28 21:14:17'),
(52, 'Diana TLK', '6199999999', 'TLK', 100.00, '2025-02-28 21:18:42'),
(53, 'SIMONE TLK', '619999999', 'TLK', 100.00, '2025-02-28 21:21:29'),
(54, 'Viviane Salão', '6199999999', 'Salão 217', 500.00, '2025-02-28 21:54:40');

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL,
  `chave` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `valor` text COLLATE utf8_unicode_ci,
  `descricao` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tipo` varchar(20) COLLATE utf8_unicode_ci DEFAULT 'texto',
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `chave`, `valor`, `descricao`, `tipo`, `data_atualizacao`) VALUES
(1, 'nome_empresa', 'Domaria Café', 'Nome da empresa exibido no sistema e comprovantes', 'texto', '2025-02-26 18:38:09'),
(2, 'logo_url', 'uploads/logo_1740702520_DOMARIA.png', 'URL da logomarca da empresa', 'imagem', '2025-02-28 00:28:40'),
(3, 'telefone_empresa', '61 4103-6787', 'Telefone de contato da empresa', 'texto', '2025-02-26 18:38:09'),
(4, 'email_empresa', 'domariacafe@gmail.com', 'Email de contato da empresa', 'texto', '2025-02-26 18:38:09'),
(5, 'endereco_empresa', 'Qs 5 Rua 600', 'Endereço da empresa', 'texto', '2025-02-26 18:38:09'),
(6, 'mensagem_comprovante', 'Agradecemos pela preferência!\r\nVeja nosso cardápio digital \r\nhttp://domariacafe.com.br', 'Mensagem exibida no final dos comprovantes', 'textarea', '2025-02-28 00:59:18'),
(7, 'cor_primaria', '#2d3034', 'Cor primária do sistema', 'cor', '2025-02-27 01:06:08'),
(8, 'cor_secundaria', '#966e6e', 'Cor secundária do sistema', 'cor', '2025-02-28 01:00:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_venda`
--

CREATE TABLE `itens_venda` (
  `id` int(11) NOT NULL,
  `venda_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `itens_venda`
--

INSERT INTO `itens_venda` (`id`, `venda_id`, `produto_id`, `quantidade`, `valor_unitario`) VALUES
(2, 2, 1, 1, 18.90),
(3, 2, 2, 1, 1.00),
(4, 2, 1, 1, 18.90),
(5, 3, 2, 1, 1.00),
(6, 3, 2, 1, 1.00),
(7, 3, 1, 1, 18.90),
(8, 4, 1, 1, 18.90),
(9, 4, 2, 1, 1.00),
(10, 5, 1, 1, 18.90),
(11, 6, 1, 1, 18.90),
(12, 7, 2, 1, 1.00),
(13, 8, 1, 1, 18.90),
(14, 8, 2, 1, 1.00),
(15, 9, 5, 1, 7.00),
(16, 9, 4, 1, 3.00),
(17, 9, 6, 3, 3.00),
(18, 9, 6, 1, 3.00),
(19, 10, 1, 1, 18.90),
(21, 12, 5, 1, 7.00),
(22, 13, 2, 1, 1.00),
(23, 14, 5, 1, 7.00),
(24, 15, 6, 1, 3.00),
(25, 15, 6, 1, 3.00),
(26, 16, 3, 1, 9.90),
(27, 17, 5, 1, 7.00),
(28, 17, 4, 1, 3.00),
(29, 18, 3, 1, 9.90),
(30, 19, 2, 1, 1.00),
(31, 19, 4, 1, 3.00),
(32, 20, 77, 1, 3.00),
(33, 20, 122, 1, 10.00),
(34, 21, 187, 2, 7.00),
(35, 22, 138, 1, 6.00),
(36, 23, 96, 2, 4.00),
(37, 23, 158, 2, 4.00),
(38, 23, 58, 2, 6.00),
(39, 23, 5, 1, 7.00),
(40, 24, 1, 1, 18.90),
(41, 25, 2, 10, 1.00),
(42, 25, 5, 3, 7.00),
(43, 26, 184, 1, 7.00),
(44, 27, 126, 1, 8.00),
(45, 27, 56, 1, 3.50),
(46, 28, 200, 1, 9.00),
(47, 29, 32, 3, 100.00),
(48, 29, 35, 1, 50.00),
(49, 29, 33, 1, 20.00),
(50, 30, 32, 2, 100.00),
(51, 31, 32, 6, 100.00),
(52, 31, 35, 1, 50.00),
(53, 32, 32, 5, 100.00),
(54, 32, 35, 1, 50.00),
(55, 32, 31, 2, 10.00),
(56, 33, 32, 3, 100.00),
(57, 33, 35, 1, 50.00),
(58, 33, 31, 3, 10.00),
(59, 33, 34, 1, 5.00),
(60, 34, 32, 1, 97.60),
(61, 35, 32, 1, 220.40),
(62, 36, 32, 1, 208.80),
(63, 37, 32, 1, 650.00),
(64, 38, 35, 1, 41.50),
(65, 39, 32, 1, 126.70),
(66, 40, 32, 1, 214.90),
(67, 41, 32, 1, 332.90),
(68, 42, 35, 1, 75.80),
(69, 43, 35, 1, 60.40),
(70, 44, 35, 1, 80.30),
(71, 45, 32, 1, 86.80),
(72, 46, 32, 1, 143.90),
(73, 47, 32, 1, 226.50),
(74, 48, 32, 1, 389.70),
(75, 49, 122, 1, 10.00),
(76, 50, 165, 1, 1.00),
(77, 51, 133, 1, 1.50),
(78, 52, 115, 1, 7.00),
(79, 53, 162, 1, 7.90),
(80, 54, 32, 1, 100.00),
(81, 54, 33, 1, 20.30),
(82, 55, 129, 1, 8.00),
(83, 56, 32, 1, 154.20),
(84, 57, 1, 1, 18.90),
(85, 57, 64, 1, 5.00),
(86, 58, 64, 1, 5.00),
(87, 59, 32, 1, 241.90),
(88, 60, 126, 1, 8.00),
(89, 60, 79, 1, 3.50),
(90, 61, 238, 1, 10.00),
(91, 62, 1, 1, 18.90),
(92, 63, 68, 1, 11.90),
(93, 63, 238, 1, 10.00),
(94, 64, 56, 1, 3.50),
(95, 65, 56, 1, 3.50),
(96, 66, 1, 1, 18.90),
(97, 66, 61, 1, 4.50),
(98, 67, 1, 1, 18.90),
(99, 67, 61, 1, 4.50),
(100, 67, 57, 1, 3.00),
(101, 68, 61, 1, 4.50),
(102, 69, 237, 1, 11.00),
(103, 69, 231, 1, 5.50),
(104, 70, 231, 1, 5.50),
(105, 71, 221, 1, 18.90),
(106, 72, 237, 2, 11.00),
(107, 73, 35, 1, 58.80),
(108, 74, 2, 3, 1.00),
(109, 75, 201, 3, 7.00),
(110, 75, 77, 3, 3.00),
(111, 76, 192, 1, 13.90),
(112, 76, 83, 1, 9.90),
(113, 77, 27, 1, 5.50),
(114, 77, 166, 1, 4.00),
(115, 77, 235, 1, 13.50),
(116, 77, 162, 1, 7.90),
(117, 78, 232, 1, 12.50),
(118, 78, 64, 1, 5.00),
(119, 78, 64, 1, 5.00),
(120, 78, 232, 1, 12.50),
(121, 79, 1, 1, 18.90),
(122, 79, 2, 6, 1.00),
(123, 79, 90, 1, 2.00),
(124, 79, 173, 1, 12.00),
(125, 79, 82, 1, 9.90),
(126, 79, 234, 1, 12.50),
(127, 80, 96, 2, 4.00),
(128, 80, 162, 1, 7.90),
(129, 80, 96, 4, 4.00),
(130, 80, 56, 1, 3.50),
(131, 80, 1, 1, 18.90),
(132, 80, 64, 1, 5.00),
(133, 81, 90, 1, 2.00),
(134, 81, 77, 1, 3.00),
(135, 81, 200, 1, 9.00),
(136, 81, 221, 1, 18.90),
(137, 81, 63, 1, 6.00),
(138, 82, 1, 1, 18.90),
(139, 82, 64, 1, 5.00),
(140, 83, 90, 3, 2.00),
(141, 84, 230, 3, 1.00),
(142, 84, 43, 1, 9.00),
(143, 84, 61, 1, 4.50),
(144, 85, 74, 1, 3.00),
(145, 85, 201, 1, 7.00),
(146, 86, 235, 1, 16.50),
(147, 86, 90, 1, 2.00),
(148, 87, 90, 1, 2.00),
(149, 87, 201, 1, 7.00),
(150, 87, 2, 2, 1.00),
(151, 88, 68, 1, 11.90),
(152, 88, 1, 1, 18.90),
(153, 88, 64, 1, 5.00),
(154, 89, 27, 1, 5.50),
(155, 89, 161, 1, 3.00),
(156, 90, 221, 1, 18.90),
(157, 91, 79, 1, 3.00),
(158, 91, 230, 4, 1.00),
(159, 91, 78, 1, 3.00),
(160, 91, 237, 1, 11.00),
(161, 91, 77, 1, 3.00),
(162, 91, 186, 1, 7.00),
(163, 92, 185, 2, 7.00),
(164, 93, 126, 2, 8.00),
(165, 93, 178, 1, 8.00),
(166, 93, 230, 3, 1.00),
(167, 94, 78, 1, 3.00),
(168, 94, 186, 1, 7.00),
(169, 94, 162, 1, 7.90),
(170, 95, 61, 1, 4.50),
(171, 95, 166, 1, 4.00),
(172, 95, 61, 1, 4.50);

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `data_hora` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_sistema`
--

CREATE TABLE `logs_sistema` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `usuario_nome` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `acao` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8_unicode_ci,
  `tipo` varchar(20) COLLATE utf8_unicode_ci DEFAULT 'info',
  `ip` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data_hora` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `logs_sistema`
--

INSERT INTO `logs_sistema` (`id`, `usuario_id`, `usuario_nome`, `acao`, `descricao`, `tipo`, `ip`, `data_hora`) VALUES
(1, 1, 'Administrador', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-02-27 01:31:52'),
(2, 1, 'Administrador', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-02-27 01:32:12'),
(3, 1, 'Administrador', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '179.131.234.65', '2025-02-27 14:17:29'),
(4, 1, 'Administrador', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-02-28 00:28:25'),
(5, 1, 'Administrador', 'Atualização de Logo', 'Logo da empresa atualizada: uploads/logo_1740702520_DOMARIA.png', 'info', '189.6.15.97', '2025-02-28 00:28:40'),
(6, 1, 'Administrador', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-02-28 00:28:40'),
(7, 1, 'Josuel Menezes', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-02-28 00:34:43'),
(8, 1, 'Josuel Menezes', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-02-28 00:59:18'),
(9, 1, 'Josuel Menezes', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-02-28 00:59:54'),
(10, 1, 'Josuel Menezes', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-02-28 01:00:42'),
(11, 1, 'Josuel Menezes', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-03-04 19:15:32'),
(12, 1, 'Josuel Menezes', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-03-04 20:59:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL,
  `venda_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_pagamento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observacao` text COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `pagamentos`
--

INSERT INTO `pagamentos` (`id`, `venda_id`, `valor`, `data_pagamento`, `observacao`) VALUES
(1, 6, 18.90, '2025-02-25 23:29:06', 'Pix'),
(2, 3, 20.90, '2025-02-25 23:29:34', ''),
(3, 2, 10.00, '2025-02-26 12:27:35', ''),
(4, 2, 10.00, '2025-02-26 12:29:29', ''),
(5, 2, 5.00, '2025-02-26 12:36:33', ''),
(6, 7, 1.00, '2025-02-26 00:00:00', ''),
(7, 8, 19.90, '2025-02-26 00:00:00', ''),
(8, 9, 10.90, '2025-02-26 00:00:00', ''),
(9, 2, 2.60, '2025-02-26 00:00:00', ''),
(10, 9, 5.00, '2025-02-26 00:00:00', ''),
(11, 2, 5.00, '2025-02-26 00:00:00', ''),
(12, 12, 2.00, '2025-02-26 00:00:00', ''),
(13, 12, 5.00, '2025-02-26 00:00:00', ''),
(14, 18, 9.90, '2025-02-26 00:00:00', ''),
(15, 9, 6.10, '2025-02-26 00:00:00', ''),
(16, 10, 18.90, '2025-02-26 00:00:00', ''),
(17, 2, 6.20, '2025-02-26 00:00:00', ''),
(18, 4, 19.90, '2025-02-26 00:00:00', ''),
(19, 5, 18.90, '2025-02-26 00:00:00', ''),
(20, 13, 1.00, '2025-02-26 00:00:00', ''),
(21, 14, 7.00, '2025-02-26 00:00:00', ''),
(22, 15, 6.00, '2025-02-26 00:00:00', ''),
(23, 16, 9.90, '2025-02-26 00:00:00', ''),
(24, 17, 10.00, '2025-02-26 00:00:00', ''),
(25, 19, 4.00, '2025-02-26 00:00:00', ''),
(26, 20, 13.00, '2025-02-27 00:00:00', 'pix'),
(27, 21, 14.00, '2025-02-27 00:00:00', ''),
(28, 31, 23.40, '2025-03-05 00:00:00', ''),
(29, 69, 5.50, '2025-03-05 00:00:00', ''),
(30, 55, 8.00, '2025-03-06 00:00:00', ''),
(31, 56, 154.20, '2025-03-06 00:00:00', ''),
(32, 72, 22.00, '2025-03-06 00:00:00', '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8_unicode_ci,
  `valor_venda` decimal(10,2) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `nome`, `descricao`, `valor_venda`, `data_cadastro`) VALUES
(1, 'Macarrão Domaria', '', 18.90, '2025-02-26 00:43:50'),
(2, 'Pão de Queijo ', 'Pequeno', 1.00, '2025-02-26 00:55:09'),
(3, 'Suco Prats', 'Suco Prats varios sabores', 9.90, '2025-02-26 13:26:02'),
(4, 'Suco Necta Nutri', 'Sabores', 3.00, '2025-02-26 13:26:02'),
(5, 'Suco da Polpa', 'Sabores', 7.00, '2025-02-26 13:26:02'),
(6, 'Trident / Freegels', 'Sabores', 3.00, '2025-02-26 13:26:02'),
(7, 'Agua Tonica', '', 7.00, '2025-02-26 21:40:07'),
(8, 'Balinha butter toffees', '', 0.25, '2025-02-26 21:40:07'),
(9, 'Balinha Eucalipto', '', 2.00, '2025-02-26 21:40:07'),
(10, 'Batata frita', '', 7.90, '2025-02-26 21:40:07'),
(11, 'BISCOITO DE QUEIJO', '', 3.00, '2025-02-26 21:40:07'),
(12, 'BISCOITO LIGHT', '', 10.00, '2025-02-26 21:40:07'),
(13, 'BOLO INTEIRO', '', 40.00, '2025-02-26 21:40:07'),
(14, 'Cabo Micro Usb', '', 15.00, '2025-02-26 21:40:07'),
(15, 'Caneta Bic Esferografica', '', 2.00, '2025-02-26 21:40:07'),
(16, 'CANETAS FOFAS', '', 5.00, '2025-02-26 21:40:07'),
(17, 'CHA GELADO', '', 7.00, '2025-02-26 21:40:07'),
(18, 'CHA QUENTE', '', 4.00, '2025-02-26 21:40:07'),
(19, 'CHAVEIRO P', '', 7.00, '2025-02-26 21:40:07'),
(20, 'CHAVEIROS G', '', 12.00, '2025-02-26 21:40:07'),
(21, 'Chocolate Talento', '', 3.50, '2025-02-26 21:40:07'),
(22, 'Cookies de chocolate', '', 7.00, '2025-02-26 21:40:07'),
(23, 'Corona 330 ml', '', 9.00, '2025-02-26 21:40:07'),
(24, 'Cup Noodles Sabores', '', 8.00, '2025-02-26 21:40:07'),
(25, 'CUSCUZ DOMARIA FRANGO', '', 17.00, '2025-02-26 21:40:07'),
(26, 'Donuts sabores', '', 10.00, '2025-02-26 21:40:07'),
(27, 'Doritos', '', 5.50, '2025-02-26 21:40:07'),
(28, 'FOLHEADO DE STROGONOFF', '', 10.00, '2025-02-26 21:40:07'),
(29, 'Garrafa de Cafe', '', 12.00, '2025-02-26 21:40:07'),
(30, 'GERAL 1', '', 1.00, '2025-02-26 21:40:07'),
(31, 'GERAL 10', '', 10.00, '2025-02-26 21:40:07'),
(32, 'GERAL 100', '', 100.00, '2025-02-26 21:40:07'),
(33, 'GERAL 20', '', 20.00, '2025-02-26 21:40:07'),
(34, 'GERAL 5', '', 5.00, '2025-02-26 21:40:07'),
(35, 'GERAL 50', '', 50.00, '2025-02-26 21:40:07'),
(36, 'Goma de Mascar Mentos', '', 2.00, '2025-02-26 21:40:07'),
(37, 'Guara mix 500ml', '', 7.00, '2025-02-26 21:40:07'),
(38, 'GUARANA 2L', '', 12.00, '2025-02-26 21:40:07'),
(39, 'Guarana Antartica Lata 350ml', '', 6.00, '2025-02-26 21:40:07'),
(40, 'Guarana Jesus', '', 6.00, '2025-02-26 21:40:07'),
(41, 'Kibe', '', 7.00, '2025-02-26 21:40:07'),
(42, 'Macarrão Alho e Oleo', '', 14.00, '2025-02-26 21:40:07'),
(43, 'Misto Quente Bauru', '', 9.00, '2025-02-26 21:40:07'),
(44, 'Molho de Pimenta', '', 12.00, '2025-02-26 21:40:07'),
(45, 'Pepsi 269 ml', '', 5.00, '2025-02-26 21:40:07'),
(46, 'PIPOCA MICROONDAS', '', 8.50, '2025-02-26 21:40:07'),
(47, 'Prestigio', '', 3.50, '2025-02-26 21:40:07'),
(48, 'Pudim de Leite Condensado', '', 10.00, '2025-02-26 21:40:07'),
(49, 'REFRIGERANTE FYS FRUTAS', '', 6.00, '2025-02-26 21:40:07'),
(50, 'Refrigerante ST Pierre', '', 7.50, '2025-02-26 21:40:07'),
(51, 'Schweppes 310ml', '', 6.00, '2025-02-26 21:40:07'),
(52, 'Torta Presunto e Queijo', '', 10.00, '2025-02-26 21:40:07'),
(53, 'Trento Mini', '', 2.50, '2025-02-26 21:40:07'),
(54, 'WHEY PIRACANJUBA', '', 10.00, '2025-02-26 21:40:07'),
(55, 'Água 1.5l', '', 6.00, '2025-02-26 21:40:07'),
(56, 'Água com Gás 500ML', '', 3.50, '2025-02-26 21:40:07'),
(57, 'Água Sem Gás 500ML', '', 3.00, '2025-02-26 21:40:07'),
(58, 'Coca Cola  310ml - SEM ACUCAR', '', 6.00, '2025-02-26 21:40:07'),
(59, 'Coca Cola 1.5 L', '', 12.00, '2025-02-26 21:40:07'),
(60, 'Coca Cola 220ml - CAFÉ', '', 4.50, '2025-02-26 21:40:07'),
(61, 'Coca Cola 220ml - ORIGINAL', '', 4.50, '2025-02-26 21:40:07'),
(62, 'Coca Cola 220ml - SEM ACUCAR', '', 4.50, '2025-02-26 21:40:07'),
(63, 'Coca Cola 310ml - ORIGINAL', '', 6.00, '2025-02-26 21:40:07'),
(64, 'Coca Cola KS 290ML - ORIGINAL', '', 5.00, '2025-02-26 21:40:07'),
(65, 'Coca Cola KS 290ML - SEM ACUCAR', '', 5.00, '2025-02-26 21:40:07'),
(66, 'Del Valle Nectar Pessego 200Ml', '', 3.50, '2025-02-26 21:40:07'),
(67, 'Del Valle Nectar Uva 200Ml', '', 3.50, '2025-02-26 21:40:07'),
(68, 'Energético Monster 473ml', '', 11.90, '2025-02-26 21:40:07'),
(69, 'Energético Monster 473ml Ultra', '', 11.90, '2025-02-26 21:40:07'),
(70, 'Energético Monster Juice Pacific', '', 11.90, '2025-02-26 21:40:07'),
(71, 'Fanta Laranja 220ml - ORIGINAL', '', 4.50, '2025-02-26 21:40:07'),
(72, 'Guaraná Antarctica Lata 269ml', '', 5.00, '2025-02-26 21:40:07'),
(73, 'Guarana Mineiro 350ml', '', 6.00, '2025-02-26 21:40:07'),
(74, 'Pira Kids 200ml', '', 3.00, '2025-02-26 21:40:07'),
(75, 'Sprite 310ml', '', 6.00, '2025-02-26 21:40:07'),
(76, 'Sprite Limão 220ml', '', 4.50, '2025-02-26 21:40:07'),
(77, 'Suco Necta Nutri Cajú', '', 3.00, '2025-02-26 21:40:07'),
(78, 'Suco Necta Nutri MARACUJÁ', '', 3.00, '2025-02-26 21:40:07'),
(79, 'Suco Necta Nutri UVA', '', 3.00, '2025-02-26 21:40:07'),
(80, 'Suco Prats 300ml Goiaba com maçã', '', 9.90, '2025-02-26 21:40:07'),
(81, 'Suco Prats 300ml LARANJA', '', 9.90, '2025-02-26 21:40:07'),
(82, 'Suco Prats 300ml LARANJA com ACEROLA', '', 9.90, '2025-02-26 21:40:07'),
(83, 'Suco Prats 300ml UVA', '', 9.90, '2025-02-26 21:40:07'),
(84, 'Heineken 250ml', '', 7.90, '2025-02-26 21:40:07'),
(85, 'Heineken Long 330ml', '', 9.00, '2025-02-26 21:40:07'),
(86, 'Café  Duplo', '', 4.00, '2025-02-26 21:40:07'),
(87, 'Café Coado Na Hora', '', 6.00, '2025-02-26 21:40:07'),
(88, 'Café Espresso', '', 6.00, '2025-02-26 21:40:07'),
(89, 'Café Espresso Duplo', '', 7.90, '2025-02-26 21:40:07'),
(90, 'Café Tradicional', '', 2.00, '2025-02-26 21:40:07'),
(91, 'Cappuccino com Doce de Leite', '', 12.90, '2025-02-26 21:40:07'),
(92, 'Cappuccino com Nutella', '', 12.90, '2025-02-26 21:40:07'),
(93, 'Cappuccino Domaria', '', 9.90, '2025-02-26 21:40:07'),
(94, 'Leite com Nescau', '', 4.50, '2025-02-26 21:40:07'),
(95, 'Leite Integral 300ml', '', 2.50, '2025-02-26 21:40:07'),
(96, 'PINGADO cafe com leite', '', 4.00, '2025-02-26 21:40:07'),
(97, 'Creme de Abacaxi', '', 8.50, '2025-02-26 21:40:07'),
(98, 'Creme de Acerola', '', 8.50, '2025-02-26 21:40:07'),
(99, 'Creme de Cupuaçu', '', 8.50, '2025-02-26 21:40:07'),
(100, 'Creme de Goiaba', '', 8.50, '2025-02-26 21:40:07'),
(101, 'Creme de Graviola', '', 8.50, '2025-02-26 21:40:07'),
(102, 'Creme de Manga', '', 8.50, '2025-02-26 21:40:07'),
(103, 'Creme de Maracujá', '', 8.50, '2025-02-26 21:40:07'),
(104, 'Creme de Morango', '', 8.50, '2025-02-26 21:40:07'),
(105, 'Suco de Abacaxi', '', 7.00, '2025-02-26 21:40:07'),
(106, 'Suco de Abacaxi com Hortelã', '', 7.00, '2025-02-26 21:40:07'),
(107, 'Suco de Acerola', '', 7.00, '2025-02-26 21:40:07'),
(108, 'Suco de Caju', '', 7.00, '2025-02-26 21:40:07'),
(109, 'Suco de Cupuaçu', '', 7.00, '2025-02-26 21:40:07'),
(110, 'Suco de Goiaba', '', 7.00, '2025-02-26 21:40:07'),
(111, 'Suco de Graviola', '', 7.00, '2025-02-26 21:40:07'),
(112, 'Suco de Laranja com Acerola', '', 7.00, '2025-02-26 21:40:07'),
(113, 'Suco de Limao', '', 9.90, '2025-02-26 21:40:07'),
(114, 'Suco de Manga', '', 7.00, '2025-02-26 21:40:07'),
(115, 'Suco de Maracujá', '', 7.00, '2025-02-26 21:40:07'),
(116, 'Suco de Morango', '', 7.00, '2025-02-26 21:40:07'),
(117, 'Suco de Tamarindo', '', 7.00, '2025-02-26 21:40:07'),
(118, 'Vitamina de Abacate', '', 11.90, '2025-02-26 21:40:07'),
(119, 'Vitamina de Banana', '', 11.90, '2025-02-26 21:40:07'),
(120, 'Vitamina de Mamão', '', 11.90, '2025-02-26 21:40:07'),
(121, 'Vitaminas', '', 10.00, '2025-02-26 21:40:07'),
(122, 'AÇAÍ 300ml', '', 10.00, '2025-02-26 21:40:07'),
(123, 'Bolo Fatia', '', 6.00, '2025-02-26 21:40:07'),
(124, 'Doce Hanuta', '', 6.00, '2025-02-26 21:40:07'),
(125, 'Mini Churros - Porção 7 unidades', '', 5.00, '2025-02-26 21:40:07'),
(126, 'O Verdadeiro Brownie  Creme de Chocolate', '', 8.00, '2025-02-26 21:40:07'),
(127, 'O Verdadeiro Brownie  Meio Amargo', '', 8.00, '2025-02-26 21:40:07'),
(128, 'O Verdadeiro Brownie  Tradicional', '', 8.00, '2025-02-26 21:40:07'),
(129, 'O Verdadeiro Brownie Creme de Avelã', '', 8.00, '2025-02-26 21:40:07'),
(130, 'O Verdadeiro Brownie Doce de Leite', '', 8.00, '2025-02-26 21:40:07'),
(131, 'O Verdadeiro Brownie M&Ms', '', 8.00, '2025-02-26 21:40:07'),
(132, 'Pamonha Doce com Queijo', '', 10.00, '2025-02-26 21:40:07'),
(133, 'Acréscimo Azeitona', '', 1.50, '2025-02-26 21:40:07'),
(134, 'Acréscimo Calabresa', '', 3.50, '2025-02-26 21:40:07'),
(135, 'Acréscimo Cebola', '', 1.00, '2025-02-26 21:40:07'),
(136, 'Acréscimo de Bacon', '', 3.50, '2025-02-26 21:40:07'),
(137, 'Acréscimo de Banana', '', 1.50, '2025-02-26 21:40:07'),
(138, 'Acréscimo de Carne Desfiada', '', 6.00, '2025-02-26 21:40:07'),
(139, 'Acréscimo de Frango', '', 5.00, '2025-02-26 21:40:07'),
(140, 'Acréscimo de Nescau', '', 2.00, '2025-02-26 21:40:07'),
(141, 'Acrescimo de Nutella', '', 6.00, '2025-02-26 21:40:07'),
(142, 'Acréscimo de Ovo', '', 2.00, '2025-02-26 21:40:07'),
(143, 'Acréscimo de Peito de Peru', '', 2.00, '2025-02-26 21:40:07'),
(144, 'Acréscimo de Presunto', '', 1.50, '2025-02-26 21:40:07'),
(145, 'Acréscimo de Queijo', '', 2.00, '2025-02-26 21:40:07'),
(146, 'Acréscimo de Queijo Minas', '', 3.00, '2025-02-26 21:40:07'),
(147, 'Acréscimo de Tomate', '', 1.00, '2025-02-26 21:40:07'),
(148, 'Acréscimo Doce de Leite', '', 3.00, '2025-02-26 21:40:07'),
(149, 'Acréscimo Granola', '', 1.50, '2025-02-26 21:40:07'),
(150, 'Acréscimo Leite', '', 2.00, '2025-02-26 21:40:07'),
(151, 'Acréscimo Leite Condensado', '', 2.00, '2025-02-26 21:40:07'),
(152, 'Acréscimo Leite em Pó', '', 2.00, '2025-02-26 21:40:07'),
(153, 'Acréscimo Milho', '', 1.50, '2025-02-26 21:40:07'),
(154, 'Acréscimo Oregano', '', 1.00, '2025-02-26 21:40:07'),
(155, 'Amendoim', '', 4.00, '2025-02-26 21:40:07'),
(156, 'Balinhas Mistas', '', 0.20, '2025-02-26 21:40:07'),
(157, 'Chicletes Bubbaloo', '', 0.50, '2025-02-26 21:40:07'),
(158, 'Doces Industrializados', '', 4.00, '2025-02-26 21:40:07'),
(159, 'Doritos Salgadinhos', '', 5.50, '2025-02-26 21:40:07'),
(160, 'Freegells Mento', '', 2.50, '2025-02-26 21:40:07'),
(161, 'Halls Diversos', '', 3.00, '2025-02-26 21:40:07'),
(162, 'HARIBO sabores', '', 7.90, '2025-02-26 21:40:07'),
(163, 'Mentos diversos', '', 4.00, '2025-02-26 21:40:07'),
(164, 'Ouro Branco / Sonho de Valsa / Batom', '', 2.00, '2025-02-26 21:40:07'),
(165, 'Pirulitos Diversos', '', 1.00, '2025-02-26 21:40:07'),
(166, 'Salgadinho Diversos', '', 4.00, '2025-02-26 21:40:07'),
(167, 'Snickers Tradicional', '', 5.00, '2025-02-26 21:40:07'),
(168, 'Trento', '', 5.00, '2025-02-26 21:40:07'),
(169, 'Trident Sabores', '', 3.00, '2025-02-26 21:40:07'),
(170, 'Café Mantissa - Grãos 250g', '', 35.00, '2025-02-26 21:40:07'),
(171, 'Caldo de Carne', '', 12.00, '2025-02-26 21:40:07'),
(172, 'Caldo de Feijão', '', 12.00, '2025-02-26 21:40:07'),
(173, 'Caldo de Frango', '', 12.00, '2025-02-26 21:40:07'),
(174, 'Caldo Verde', '', 12.00, '2025-02-26 21:40:07'),
(175, 'Kibe de Carne', '', 7.00, '2025-02-26 21:40:07'),
(176, 'Buraco Fundo', '', 10.00, '2025-02-26 21:40:07'),
(177, 'Coxinha de Frango com Catupiry', '', 7.00, '2025-02-26 21:40:07'),
(178, 'Empada de Frango', '', 8.00, '2025-02-26 21:40:07'),
(179, 'Empadão Goiano', '', 12.00, '2025-02-26 21:40:07'),
(180, 'Enrolado de Presunto e Queijo', '', 7.00, '2025-02-26 21:40:07'),
(181, 'Enrolado de Queijo', '', 7.00, '2025-02-26 21:40:07'),
(182, 'Hot Dog', '', 7.00, '2025-02-26 21:40:07'),
(183, 'Kibe de Queijo', '', 7.00, '2025-02-26 21:40:07'),
(184, 'Pão da Vovó', '', 7.00, '2025-02-26 21:40:07'),
(185, 'Pão hambúrguer', '', 7.00, '2025-02-26 21:40:07'),
(186, 'Pão Pizza', '', 7.00, '2025-02-26 21:40:07'),
(187, 'SALGADOS GERAL', '', 7.00, '2025-02-26 21:40:07'),
(188, 'Tapioca Bombada', '', 22.00, '2025-02-26 21:40:07'),
(189, 'Tapioca com Carne Desfiada', '', 16.00, '2025-02-26 21:40:07'),
(190, 'Tapioca com Doce de Leite', '', 13.90, '2025-02-26 21:40:07'),
(191, 'Tapioca com Frango Desfiado', '', 13.90, '2025-02-26 21:40:07'),
(192, 'Tapioca com Nutella', '', 13.90, '2025-02-26 21:40:07'),
(193, 'Tapioca com Peito de Peru e Queijo Minas', '', 12.00, '2025-02-26 21:40:07'),
(194, 'Tapioca com Presunto e Queijo Mussarela', '', 10.00, '2025-02-26 21:40:07'),
(195, 'Tapioca com Queijo Minas', '', 10.00, '2025-02-26 21:40:07'),
(196, 'Tapioca com Queijo Mussarela', '', 8.00, '2025-02-26 21:40:07'),
(197, 'Tapioca Simples', '', 7.00, '2025-02-26 21:40:07'),
(198, 'Misto Domaria - Delicioso', '', 14.00, '2025-02-26 21:40:07'),
(199, 'Misto Light', '', 9.00, '2025-02-26 21:40:07'),
(200, 'Misto Quente Completo', '', 9.00, '2025-02-26 21:40:07'),
(201, 'Misto Quente Simples', '', 7.00, '2025-02-26 21:40:07'),
(202, 'Pão com Manteiga na Chapa', '', 4.00, '2025-02-26 21:40:07'),
(203, 'Pão Especial com Carne', '', 16.90, '2025-02-26 21:40:07'),
(204, 'Pão Especial de Frango', '', 14.50, '2025-02-26 21:40:07'),
(205, 'Omelete Bauru', '', 12.00, '2025-02-26 21:40:07'),
(206, 'Omelete com Carne desfiada', '', 14.90, '2025-02-26 21:40:07'),
(207, 'Omelete com Frango Desfiado', '', 13.90, '2025-02-26 21:40:07'),
(208, 'Omelete de presunto e Queijo', '', 9.90, '2025-02-26 21:40:07'),
(209, 'Omelete Light', '', 13.90, '2025-02-26 21:40:07'),
(210, 'Crepioca de Carne Desfiada', '', 16.90, '2025-02-26 21:40:07'),
(211, 'Crepioca de Frango Desfiado', '', 14.90, '2025-02-26 21:40:07'),
(212, 'Crepioca de Presunto e Queijo', '', 11.50, '2025-02-26 21:40:07'),
(213, 'Crepioca de Queijo Mussarela', '', 10.00, '2025-02-26 21:40:07'),
(214, 'Cuscuz com Carne Desfiada', '', 14.90, '2025-02-26 21:40:07'),
(215, 'Cuscuz com Frango Desfiado', '', 13.50, '2025-02-26 21:40:07'),
(216, 'Cuscuz com Ovo', '', 8.00, '2025-02-26 21:40:07'),
(217, 'Cuscuz com Peito de Peru e Queijo Minas', '', 12.50, '2025-02-26 21:40:07'),
(218, 'Cuscuz com Presunto e Queijo Mussarela', '', 10.00, '2025-02-26 21:40:07'),
(219, 'Cuscuz com Queijo Minas', '', 9.90, '2025-02-26 21:40:07'),
(220, 'Cuscuz com Queijo Mussarela', '', 8.00, '2025-02-26 21:40:07'),
(221, 'Cuscuz Domaria', '', 18.90, '2025-02-26 21:40:07'),
(222, 'Cuscuz Simples', '', 7.00, '2025-02-26 21:40:07'),
(223, 'Ovo frito', '', 2.50, '2025-02-26 21:40:07'),
(224, 'Ovos Mexidos', '', 8.00, '2025-02-26 21:40:07'),
(225, 'Pão com ovo', '', 7.00, '2025-02-26 21:40:07'),
(226, 'Pão com ovo e queijo', '', 8.00, '2025-02-26 21:40:07'),
(227, 'Sanduiche Natural', '', 14.90, '2025-02-26 21:40:07'),
(228, 'Sanduiche Natural Domaria', '', 14.90, '2025-02-26 21:40:07'),
(229, 'Sanduiche Natural Soft', '', 14.90, '2025-02-26 21:40:07'),
(230, 'Pão de Queijo Pequeno', '', 1.00, '2025-02-26 21:40:07'),
(231, 'Picole Baton - Garoto', '', 5.50, '2025-03-04 20:44:41'),
(232, 'Picolé Caribe Garoto', '', 12.50, '2025-03-04 20:44:59'),
(233, 'Picole Serenata Garoto', '', 10.00, '2025-03-04 20:45:13'),
(234, 'Picole Serenata de Amor', '', 12.50, '2025-03-04 20:45:27'),
(235, 'Picole Crocante Garoto', '', 13.50, '2025-03-04 20:45:40'),
(236, 'Picole It Coco - Garoto', '', 12.50, '2025-03-04 20:45:53'),
(237, 'Picole Mundy - Garoto Cream', '', 11.00, '2025-03-04 20:46:05'),
(238, 'Picole Bombom - Garoto', '', 10.00, '2025-03-04 20:46:22'),
(239, 'Picole Bombomzin - Garoto', '', 16.00, '2025-03-04 20:46:33');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nivel_acesso` enum('admin','gerente','vendedor','cliente') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'cliente',
  `cliente_id` int(11) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `data_criacao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_acesso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `nivel_acesso`, `cliente_id`, `ativo`, `data_criacao`, `ultimo_acesso`) VALUES
(1, 'Josuel Menezes', 'jghoste@gmail.com', '$2y$10$QMkPXZDPnm0vFA7gfKPQZ.R28QdxagA4e7pFggqqx0QKUttUsblOe', 'admin', NULL, 1, '2025-02-26 13:50:52', '2025-03-06 21:47:20'),
(2, 'Estefany Menezes', 'niely.sp@gmail.com', '$2y$10$lwFg/1cenf8plI/lAfp9JOXrIEg6cGsP6tUrWl8wRf/Ft8k6drRua', 'vendedor', NULL, 1, '2025-02-26 19:43:02', '2025-02-27 20:03:22'),
(3, 'Rodrigo Santos', 'rodrigosantos@gmail.com', '$2y$10$cnrD.lm.jbkPfyPdhhjCsO2CiDLHXXl2cTatC.3acrssVmhqVxMbG', 'gerente', NULL, 1, '2025-02-26 19:44:01', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `vendas`
--

CREATE TABLE `vendas` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `data_venda` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('aberto','pago','cancelado') COLLATE utf8_unicode_ci DEFAULT 'aberto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `vendas`
--

INSERT INTO `vendas` (`id`, `cliente_id`, `data_venda`, `status`) VALUES
(2, 1, '2025-02-26 01:19:46', 'pago'),
(3, 1, '2025-02-26 01:27:02', 'aberto'),
(4, 1, '2025-02-26 01:32:30', 'pago'),
(5, 1, '2025-02-26 01:33:20', 'pago'),
(6, 2, '2025-02-26 01:48:27', 'aberto'),
(7, 2, '2025-02-26 01:48:42', 'pago'),
(8, 2, '2025-02-26 02:01:25', 'pago'),
(9, 3, '2025-02-26 15:20:06', 'pago'),
(10, 3, '2025-02-26 15:20:27', 'pago'),
(12, 2, '2025-02-26 16:18:46', 'pago'),
(13, 1, '2025-02-26 17:53:42', 'pago'),
(14, 1, '2025-02-26 17:55:58', 'pago'),
(15, 1, '2025-02-26 18:00:52', 'pago'),
(16, 1, '2025-02-26 18:24:54', 'pago'),
(17, 1, '2025-02-26 18:38:43', 'pago'),
(18, 4, '2025-02-26 20:05:51', 'pago'),
(19, 1, '2025-02-26 21:19:19', 'pago'),
(20, 3, '2025-02-27 14:38:26', 'pago'),
(21, 2, '2025-02-28 00:55:05', 'pago'),
(22, 9, '2025-02-28 16:09:30', 'aberto'),
(23, 35, '2025-02-28 21:07:39', 'aberto'),
(24, 30, '2025-02-28 21:12:05', 'aberto'),
(25, 51, '2025-02-28 21:16:22', 'aberto'),
(26, 52, '2025-02-28 21:19:15', 'aberto'),
(27, 53, '2025-02-28 21:23:11', 'aberto'),
(28, 50, '2025-02-28 21:24:20', 'aberto'),
(29, 45, '2025-02-28 21:27:26', 'aberto'),
(30, 42, '2025-02-28 21:31:55', 'aberto'),
(31, 43, '2025-02-28 21:33:01', 'aberto'),
(32, 44, '2025-02-28 21:34:03', 'aberto'),
(33, 15, '2025-02-28 21:36:07', 'aberto'),
(34, 20, '2025-02-28 21:36:46', 'aberto'),
(35, 19, '2025-02-28 21:37:33', 'aberto'),
(36, 17, '2025-02-28 21:39:00', 'aberto'),
(37, 18, '2025-02-28 21:39:42', 'aberto'),
(38, 22, '2025-02-28 21:42:16', 'aberto'),
(39, 23, '2025-02-28 21:42:50', 'aberto'),
(40, 24, '2025-02-28 21:43:30', 'aberto'),
(41, 25, '2025-02-28 21:44:08', 'aberto'),
(42, 26, '2025-02-28 21:45:42', 'aberto'),
(43, 36, '2025-02-28 21:46:33', 'aberto'),
(44, 38, '2025-02-28 21:47:20', 'aberto'),
(45, 39, '2025-02-28 21:47:56', 'aberto'),
(46, 41, '2025-02-28 21:48:30', 'aberto'),
(47, 3, '2025-02-28 21:51:16', 'aberto'),
(48, 54, '2025-02-28 21:55:12', 'aberto'),
(49, 2, '2025-02-28 23:24:22', 'aberto'),
(50, 2, '2025-02-28 23:25:35', 'aberto'),
(51, 2, '2025-02-28 23:32:58', 'aberto'),
(52, 18, '2025-03-05 13:40:11', 'aberto'),
(53, 21, '2025-03-05 13:47:56', 'aberto'),
(54, 21, '2025-03-05 13:49:48', 'aberto'),
(55, 28, '2025-03-05 13:59:41', 'pago'),
(56, 28, '2025-03-05 14:03:15', 'pago'),
(57, 21, '2025-03-05 14:37:40', 'aberto'),
(58, 32, '2025-03-05 15:06:46', 'aberto'),
(59, 32, '2025-03-05 15:09:15', 'aberto'),
(60, 15, '2025-03-05 15:20:37', 'aberto'),
(61, 18, '2025-03-05 15:46:42', 'aberto'),
(62, 9, '2025-03-05 15:57:20', 'aberto'),
(63, 21, '2025-03-05 16:35:27', 'aberto'),
(64, 22, '2025-03-05 16:39:45', 'aberto'),
(65, 22, '2025-03-05 16:52:44', 'aberto'),
(66, 43, '2025-03-05 16:56:18', 'aberto'),
(67, 42, '2025-03-05 16:57:41', 'aberto'),
(68, 47, '2025-03-05 17:05:28', 'aberto'),
(69, 14, '2025-03-05 17:41:56', 'aberto'),
(70, 9, '2025-03-05 17:45:17', 'aberto'),
(71, 54, '2025-03-05 18:09:54', 'aberto'),
(72, 28, '2025-03-06 23:38:29', 'pago'),
(73, 46, '2025-03-06 23:46:34', 'aberto'),
(74, 46, '2025-03-06 23:47:27', 'aberto'),
(75, 23, '2025-03-06 23:51:48', 'aberto'),
(76, 24, '2025-03-06 23:53:50', 'aberto'),
(77, 11, '2025-03-06 23:57:14', 'aberto'),
(78, 17, '2025-03-07 00:07:57', 'aberto'),
(79, 41, '2025-03-07 00:10:12', 'aberto'),
(80, 18, '2025-03-07 00:13:02', 'aberto'),
(81, 36, '2025-03-07 00:15:22', 'aberto'),
(82, 47, '2025-03-07 00:16:21', 'aberto'),
(83, 48, '2025-03-07 00:17:22', 'aberto'),
(84, 42, '2025-03-07 00:19:19', 'aberto'),
(85, 11, '2025-03-07 00:20:27', 'aberto'),
(86, 9, '2025-03-07 00:21:59', 'aberto'),
(87, 23, '2025-03-07 00:23:06', 'aberto'),
(88, 32, '2025-03-07 00:24:32', 'aberto'),
(89, 16, '2025-03-07 00:25:43', 'aberto'),
(90, 10, '2025-03-07 00:26:51', 'aberto'),
(91, 15, '2025-03-07 00:28:29', 'aberto'),
(92, 24, '2025-03-07 00:29:37', 'aberto'),
(93, 21, '2025-03-07 00:30:54', 'aberto'),
(94, 8, '2025-03-07 00:32:07', 'aberto'),
(95, 47, '2025-03-07 00:33:38', 'aberto');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`);

--
-- Índices de tabela `itens_venda`
--
ALTER TABLE `itens_venda`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venda_id` (`venda_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venda_id` (`venda_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `vendas`
--
ALTER TABLE `vendas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `itens_venda`
--
ALTER TABLE `itens_venda`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT de tabela `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `logs_sistema`
--
ALTER TABLE `logs_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=240;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `vendas`
--
ALTER TABLE `vendas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `itens_venda`
--
ALTER TABLE `itens_venda`
  ADD CONSTRAINT `itens_venda_ibfk_1` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `itens_venda_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);

--
-- Restrições para tabelas `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD CONSTRAINT `pagamentos_ibfk_1` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `vendas`
--
ALTER TABLE `vendas`
  ADD CONSTRAINT `vendas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
