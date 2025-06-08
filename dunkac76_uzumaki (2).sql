-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Tempo de geração: 08/06/2025 às 13:41
-- Versão do servidor: 8.0.40
-- Versão do PHP: 8.3.14

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

DELIMITER $$
--
-- Funções
--
CREATE DEFINER=`root`@`localhost` FUNCTION `calcular_horas_ponto` (`p_entrada_manha` TIME, `p_saida_almoco` TIME, `p_entrada_tarde` TIME, `p_saida_final` TIME) RETURNS TIME DETERMINISTIC READS SQL DATA BEGIN
    DECLARE total_segundos INT DEFAULT 0;
    DECLARE periodo_manha INT DEFAULT 0;
    DECLARE periodo_tarde INT DEFAULT 0;
    
    -- Se não tem entrada da manhã, retorna NULL
    IF p_entrada_manha IS NULL THEN
        RETURN NULL;
    END IF;
    
    -- Se não tem saída final, retorna NULL
    IF p_saida_final IS NULL THEN
        RETURN NULL;
    END IF;
    
    -- Calcular período da manhã
    IF p_saida_almoco IS NOT NULL THEN
        SET periodo_manha = TIME_TO_SEC(TIMEDIFF(p_saida_almoco, p_entrada_manha));
    ELSE
        -- Se não tem almoço, conta até saída final
        SET periodo_manha = TIME_TO_SEC(TIMEDIFF(p_saida_final, p_entrada_manha));
        RETURN SEC_TO_TIME(periodo_manha);
    END IF;
    
    -- Calcular período da tarde
    IF p_entrada_tarde IS NOT NULL THEN
        SET periodo_tarde = TIME_TO_SEC(TIMEDIFF(p_saida_final, p_entrada_tarde));
    ELSE
        -- Se não registrou volta do almoço, assumir 1h de almoço
        SET periodo_tarde = TIME_TO_SEC(TIMEDIFF(p_saida_final, ADDTIME(p_saida_almoco, '01:00:00')));
    END IF;
    
    SET total_segundos = periodo_manha + periodo_tarde;
    
    -- Garantir que não seja negativo
    IF total_segundos < 0 THEN
        SET total_segundos = 0;
    END IF;
    
    RETURN SEC_TO_TIME(total_segundos);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `anexos_contas`
--

CREATE TABLE `anexos_contas` (
  `id` int NOT NULL,
  `conta_id` int NOT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `nome_original` varchar(255) NOT NULL,
  `tipo_arquivo` varchar(50) DEFAULT NULL,
  `tamanho` int DEFAULT NULL,
  `caminho` varchar(500) NOT NULL,
  `usuario_upload` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias_contas`
--

CREATE TABLE `categorias_contas` (
  `id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('receita','despesa') NOT NULL,
  `cor` varchar(7) DEFAULT '#6c757d',
  `icone` varchar(50) DEFAULT 'fas fa-circle',
  `descricao` text,
  `ativo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `categorias_contas`
--

INSERT INTO `categorias_contas` (`id`, `nome`, `tipo`, `cor`, `icone`, `descricao`, `ativo`, `created_at`, `updated_at`) VALUES
(1, 'Vendas de Produtos', 'receita', '#28a745', 'fas fa-shopping-cart', 'Receitas provenientes de vendas de produtos', 1, '2025-06-07 23:53:14', '2025-06-07 23:53:14'),
(2, 'Vendas de Serviços', 'receita', '#17a2b8', 'fas fa-handshake', 'Receitas de prestação de serviços', 1, '2025-06-07 23:53:14', '2025-06-07 23:53:14'),
(3, 'Receitas Financeiras', 'receita', '#ffc107', 'fas fa-chart-line', 'Juros, rendimentos e aplicações', 1, '2025-06-07 23:53:14', '2025-06-07 23:53:14'),
(4, 'Outras Receitas', 'receita', '#6f42c1', 'fas fa-plus-circle', 'Receitas diversas não categorizadas', 1, '2025-06-07 23:53:14', '2025-06-07 23:53:14'),
(5, 'Fornecedores', 'despesa', '#fd7e14', 'fas fa-truck', 'Pagamentos a fornecedores de produtos', 1, '2025-06-07 23:53:14', '2025-06-07 23:53:14'),
(6, 'Salários e Encargos', 'despesa', '#e83e8c', 'fas fa-users', 'Folha de pagamento e encargos trabalhistas', 1, '2025-06-07 23:53:14', '2025-06-07 23:53:14'),
(7, 'Aluguel e Condomínio', 'despesa', '#6c757d', 'fas fa-building', 'Aluguel, condomínio e taxas prediais', 1, '2025-06-07 23:53:14', '2025-06-07 23:53:14'),
(8, 'Energia Elétrica', 'despesa', '#ffc107', 'fas fa-bolt', 'Conta de energia elétrica', 1, '2025-06-07 23:53:14', '2025-06-07 23:53:14'),
(9, 'Água e Esgoto', 'despesa', '#20c997', 'fas fa-tint', 'Conta de água e esgoto', 1, '2025-06-07 23:53:14', '2025-06-07 23:53:14'),
(10, 'Internet e Telefone', 'despesa', '#17a2b8', 'fas fa-wifi', 'Telecomunicações e internet', 1, '2025-06-07 23:53:14', '2025-06-07 23:53:14'),
(11, 'Material de Escritório', 'despesa', '#6f42c1', 'fas fa-paperclip', 'Materiais e suprimentos de escritório', 1, '2025-06-07 23:53:14', '2025-06-07 23:53:14'),
(12, 'Marketing e Publicidade', 'despesa', '#dc3545', 'fas fa-bullhorn', 'Despesas com marketing e publicidade', 1, '2025-06-07 23:53:14', '2025-06-07 23:53:14'),
(13, 'Manutenção e Reparos', 'despesa', '#fd7e14', 'fas fa-tools', 'Manutenção de equipamentos e instalações', 1, '2025-06-07 23:53:14', '2025-06-07 23:53:14'),
(14, 'Impostos e Taxas', 'despesa', '#dc3545', 'fas fa-file-invoice-dollar', 'Impostos, taxas e contribuições', 1, '2025-06-07 23:53:14', '2025-06-07 23:53:14'),
(15, 'Outras Despesas', 'despesa', '#6c757d', 'fas fa-minus-circle', 'Despesas diversas não categorizadas', 1, '2025-06-07 23:53:14', '2025-06-07 23:53:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias_fornecedores`
--

CREATE TABLE `categorias_fornecedores` (
  `id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text,
  `cor` varchar(7) DEFAULT '#007bff',
  `icone` varchar(50) DEFAULT 'fas fa-box',
  `ativo` tinyint(1) DEFAULT '1',
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `categorias_fornecedores`
--

INSERT INTO `categorias_fornecedores` (`id`, `nome`, `descricao`, `cor`, `icone`, `ativo`, `data_criacao`) VALUES
(1, 'Alimentos e Bebidas', 'Fornecedores de produtos alimentícios', '#28a745', 'fas fa-utensils', 1, '2025-06-06 21:56:47'),
(2, 'Descartáveis', 'Copos, pratos, guardanapos, etc.', '#17a2b8', 'fas fa-trash-alt', 1, '2025-06-06 21:56:47'),
(3, 'Limpeza', 'Produtos de higiene e limpeza', '#6f42c1', 'fas fa-broom', 1, '2025-06-06 21:56:47'),
(4, 'Equipamentos', 'Máquinas, equipamentos e utensílios', '#fd7e14', 'fas fa-tools', 1, '2025-06-06 21:56:47'),
(5, 'Matéria Prima', 'Ingredientes básicos para produção', '#e83e8c', 'fas fa-seedling', 1, '2025-06-06 21:56:47'),
(6, 'Serviços', 'Manutenção, consultoria, etc.', '#6c757d', 'fas fa-handshake', 1, '2025-06-06 21:56:47'),
(7, 'Embalagens', 'Sacolas, caixas, embalagens', '#20c997', 'fas fa-box-open', 1, '2025-06-06 21:56:47'),
(8, 'Tecnologia', 'Software, hardware, sistemas', '#007bff', 'fas fa-laptop', 1, '2025-06-06 21:56:47');

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int NOT NULL,
  `nome` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `telefone` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `empresa` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `limite_compra` decimal(10,2) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome`, `telefone`, `empresa`, `limite_compra`, `data_cadastro`) VALUES
(1, 'Josuel Menezes', '61991190352', 'Dunka', 1000.00, '2025-02-26 00:43:15'),
(2, 'Estefany Menezes', '61992086827', 'Cantinho Niely', 1000.00, '2025-02-26 01:39:39'),
(3, 'Rodrigo Santos', '61998607252', 'Domaria', 1000.00, '2025-02-26 15:18:39'),
(4, 'Rita de Cassaio ', '61993838813', 'Domaria', 200.00, '2025-02-26 20:01:57'),
(5, 'ADM GRATIDÃO LIFE', '(61) 98360-8471', 'GRATIDÃO LIFE', 600.00, '2025-02-26 21:14:35'),
(8, 'EMANUELY GRATIDÃO LIFE', '(61) 99644-6483', 'GRATIDÃO LIFE', 300.00, '2025-02-26 21:14:35'),
(9, 'GABRIELA GRATIDÃO LIFE', '(61) 99516-9762', 'GRATIDÃO LIFE', 300.00, '2025-02-26 21:14:35'),
(10, 'MARCONI GRATIDÃO LIFE', '(61) 98143-1753', 'GRATIDÃO LIFE', 600.00, '2025-02-26 21:14:35'),
(11, 'RAISSA GRATIDÃO LIFE', '61 9288-0712', 'GRATIDÃO LIFE', 300.00, '2025-02-26 21:14:35'),
(12, 'SAMUEL GRATIDÃO LIFE', '61 98492-5618', 'GRATIDÃO LIFE', 600.00, '2025-02-26 21:14:35'),
(14, 'YASMIN GRATIDÃO LIFE', '61 9854-1324', 'GRATIDÃO LIFE', 300.00, '2025-02-26 21:14:35'),
(15, 'KAUANE KAKA  INFINITI', '(61) 991491924', 'INFINITI', 300.00, '2025-02-26 21:14:35'),
(16, 'MARCOS INFINITI', '(61) 99208-1308', 'INFINITI', 300.00, '2025-02-26 21:14:35'),
(17, 'RAIANE CAJU INFINITI', '(61) 99340-4125', 'INFINITI', 600.00, '2025-02-26 21:14:35'),
(18, 'THAINAH INFINITI', '', 'INFINITI', 0.00, '2025-02-26 21:14:35'),
(19, 'NICOLE INFINITI', '(61) 98569-2833', 'INFINITI', 300.00, '2025-02-26 21:14:35'),
(20, 'LISSANDRA INFINITI', '(61) 99299-7481', 'INFINITI', 300.00, '2025-02-26 21:14:35'),
(21, 'PRISCILA INFINITI', '(61) 99527-2417', 'INFINITI', 300.00, '2025-02-26 21:14:35'),
(22, 'HELAINE IPHAC', '(79) 99932-5922', 'IPHAC', 200.00, '2025-02-26 21:14:35'),
(23, 'LOURDES IPHAC', '(61) 99966-8750', 'IPHAC', 600.00, '2025-02-26 21:14:35'),
(24, 'PATY IPHAC', '', 'IPHAC', 0.00, '2025-02-26 21:14:35'),
(25, 'ROBERTO RUSIVELT IPHAC', '', 'IPHAC', 0.00, '2025-02-26 21:14:35'),
(26, 'SANDY IPHAC', '', 'IPHAC', 0.00, '2025-02-26 21:14:35'),
(27, 'BARBARA SUPERVISORA', '', 'MANTEVIDA', 0.00, '2025-02-26 21:14:35'),
(28, 'DANI', '(61) 99345-1089', 'MANTEVIDA', 300.00, '2025-02-26 21:14:35'),
(29, 'HORTENCIA', '(61) 99208-4893', 'MANTEVIDA', 200.00, '2025-02-26 21:14:35'),
(30, 'JOANA', '', 'MANTEVIDA', 0.00, '2025-02-26 21:14:35'),
(31, 'RAY RAIANE', '', 'MANTEVIDA', 0.00, '2025-02-26 21:14:35'),
(32, 'RENATA', '(61) 99219-8684', 'MANTEVIDA', 300.00, '2025-02-26 21:14:35'),
(33, 'JESSICA', '', 'MANTEVIDA', 0.00, '2025-02-26 21:14:35'),
(34, 'KELLY', '', 'MANTEVIDA', 0.00, '2025-02-26 21:14:35'),
(35, 'SARA SILVA', '', 'MANTEVIDA', 0.00, '2025-02-26 21:14:35'),
(36, 'ANA MARIA - sala 106', '(61) 993691857', 'CLINICA TASSIO MACIEL', 300.00, '2025-02-26 21:14:35'),
(38, 'LILIANE - sala 106', '(61) 99812-9695', 'CLINICA TASSIO MACIEL', 300.00, '2025-02-26 21:14:35'),
(39, 'LUCAS - sala 106', '(61) 98371-0777', 'CLINICA TASSIO MACIEL', 300.00, '2025-02-26 21:14:35'),
(40, 'NAYARA - sala 106', '(61) 98148-0173', 'CLINICA TASSIO MACIEL', 600.00, '2025-02-26 21:14:35'),
(41, 'TASSIO - sala 106', '', 'CLINICA TASSIO MACIEL', 0.00, '2025-02-26 21:14:35'),
(42, 'DANUZIA', '61991492223', 'VIVER E SER', 300.00, '2025-02-26 21:14:35'),
(43, 'REBECA', '', 'VIVER E SER', 0.00, '2025-02-26 21:14:35'),
(44, 'RENATA', '', 'VIVER E SER', 0.00, '2025-02-26 21:14:35'),
(45, 'FLAVIA', '(61) 99221-6464', 'COMPASSIO', 0.00, '2025-02-26 21:14:35'),
(46, 'JULIANA', '(61) 98128-0158', 'COMPASSIO', 300.00, '2025-02-26 21:14:35'),
(47, 'LARISSA', '(61) 98412-5762', 'COMPASSIO', 300.00, '2025-02-26 21:14:35'),
(48, 'HUMBERTO', '(61) 99557-5067', 'ENGENHEIRO', 600.00, '2025-02-26 21:14:35'),
(49, 'teste', '61991190352', 'Mais nos', 2.00, '2025-02-27 01:56:02'),
(50, 'Jéssica Ohara ', '(61) 98133-1581', 'TLK', 200.00, '2025-02-28 17:49:17'),
(51, 'ADM INFINITI', '6199999999', 'INFINITI', 600.00, '2025-02-28 21:14:17'),
(52, 'Diana TLK', '6199999999', 'TLK', 100.00, '2025-02-28 21:18:42'),
(53, 'SIMONE TLK', '619999999', 'TLK', 100.00, '2025-02-28 21:21:29'),
(54, 'Viviane Salão', '(61) 98144-6447', 'Salão 217', 300.00, '2025-02-28 21:54:40'),
(55, 'Rose ', '(61) 99520-7979', 'Rose Borba sala 218', 300.00, '2025-03-07 20:38:29'),
(56, 'DONA VANDI', '(61) 99999-9999', 'INFINITI', 200.00, '2025-03-07 20:40:37'),
(57, 'Dudu Porteiro', '(61) 99999-9999', 'Dr Carlos Mangueira', 200.00, '2025-03-07 20:44:09'),
(59, 'Joyce TLK', '6196343931', 'TLK', 300.00, '2025-04-29 20:54:56'),
(60, 'Felipe Ferreira PILATES', '(61) 98141-0406', 'PILATES', 300.00, '2025-04-29 21:08:16'),
(61, 'HELEN TLK', '(61) 99517-4224', 'TLK', 300.00, '2025-05-22 21:55:49'),
(62, 'CRIS - FISIOTERAPEUTA ', '(61) 98240-7847', 'CRIS FISIO ', 400.00, '2025-05-30 16:54:32');

-- --------------------------------------------------------

--
-- Estrutura para tabela `comunicacoes_fornecedores`
--

CREATE TABLE `comunicacoes_fornecedores` (
  `id` int NOT NULL,
  `fornecedor_id` int NOT NULL,
  `tipo` enum('whatsapp','email','telefone','visita','outro') NOT NULL,
  `assunto` varchar(255) DEFAULT NULL,
  `mensagem` text,
  `data_comunicacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` int DEFAULT NULL,
  `arquivo_anexo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `comunicacoes_fornecedores`
--

INSERT INTO `comunicacoes_fornecedores` (`id`, `fornecedor_id`, `tipo`, `assunto`, `mensagem`, `data_comunicacao`, `usuario_id`, `arquivo_anexo`) VALUES
(1, 1, 'whatsapp', 'Comunicação via Whatsapp', 'dadv d', '2025-06-07 05:12:00', 1, NULL),
(2, 1, 'outro', 'Avaliação do fornecedor', 'Avaliação: 5 estrelas\nComentário: tudo certo', '2025-06-07 02:13:28', 1, NULL),
(3, 1, 'email', 'Comunicação via Email', 'teste', '2025-06-06 05:13:00', 1, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int NOT NULL,
  `chave` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `valor` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `descricao` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `tipo` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT 'texto',
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `chave`, `valor`, `descricao`, `tipo`, `data_atualizacao`) VALUES
(1, 'nome_empresa', 'Domaria Café', 'Nome da empresa exibido no sistema e comprovantes', 'texto', '2025-02-26 18:38:09'),
(2, 'logo_url', 'uploads/logo_1749389360_logo.png', 'URL da logomarca da empresa', 'imagem', '2025-06-08 13:29:20'),
(3, 'telefone_empresa', '61 4103-6787', 'Telefone de contato da empresa', 'texto', '2025-02-26 18:38:09'),
(4, 'email_empresa', 'domariacafe@gmail.com', 'Email de contato da empresa', 'texto', '2025-02-26 18:38:09'),
(5, 'endereco_empresa', 'Qs 5 Rua 600', 'Endereço da empresa', 'texto', '2025-02-26 18:38:09'),
(6, 'mensagem_comprovante', 'Agradecemos pela preferência!\r\nSe precisar fazer um pagamento - esse é nosso Pix\r\nCNPJ: 48.111.122/0001-81', 'Mensagem exibida no final dos comprovantes', 'textarea', '2025-03-07 02:51:53'),
(7, 'cor_primaria', '#2d3034', 'Cor primária do sistema', 'cor', '2025-02-27 01:06:08'),
(8, 'cor_secundaria', '#966e6e', 'Cor secundária do sistema', 'cor', '2025-02-28 01:00:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes_ponto`
--

CREATE TABLE `configuracoes_ponto` (
  `id` int NOT NULL,
  `tolerancia_entrada` int DEFAULT '15' COMMENT 'Tolerância em minutos para entrada',
  `tolerancia_saida` int DEFAULT '15' COMMENT 'Tolerância em minutos para saída',
  `calcular_horas_extras` tinyint(1) DEFAULT '1',
  `hora_almoco_obrigatoria` tinyint(1) DEFAULT '1',
  `ativo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `configuracoes_ponto`
--

INSERT INTO `configuracoes_ponto` (`id`, `tolerancia_entrada`, `tolerancia_saida`, `calcular_horas_extras`, `hora_almoco_obrigatoria`, `ativo`, `created_at`, `updated_at`) VALUES
(1, 15, 15, 1, 1, 1, '2025-06-01 23:49:53', '2025-06-01 23:49:53');

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas`
--

CREATE TABLE `contas` (
  `id` int NOT NULL,
  `tipo` enum('pagar','receber') NOT NULL,
  `categoria_id` int DEFAULT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor_original` decimal(10,2) NOT NULL,
  `valor_pago` decimal(10,2) DEFAULT '0.00',
  `valor_pendente` decimal(10,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_competencia` date NOT NULL,
  `data_cadastro` date DEFAULT (curdate()),
  `status` enum('pendente','pago_parcial','pago','vencido','cancelado') DEFAULT 'pendente',
  `prioridade` enum('baixa','media','alta','urgente') DEFAULT 'media',
  `cliente_id` int DEFAULT NULL,
  `fornecedor_id` int DEFAULT NULL,
  `venda_id` int DEFAULT NULL,
  `observacoes` text,
  `documento` varchar(100) DEFAULT NULL,
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `recorrente` tinyint(1) DEFAULT '0',
  `periodicidade` enum('mensal','bimestral','trimestral','semestral','anual') DEFAULT NULL,
  `dia_vencimento` int DEFAULT NULL,
  `usuario_cadastro` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Acionadores `contas`
--
DELIMITER $$
CREATE TRIGGER `tr_contas_historico_insert` AFTER INSERT ON `contas` FOR EACH ROW BEGIN
    INSERT INTO historico_contas (conta_id, acao, dados_novos, usuario_id)
    VALUES (NEW.id, 'criacao', JSON_OBJECT(
        'descricao', NEW.descricao,
        'valor_original', NEW.valor_original,
        'data_vencimento', NEW.data_vencimento,
        'status', NEW.status
    ), NEW.usuario_cadastro);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_contas_historico_update` AFTER UPDATE ON `contas` FOR EACH ROW BEGIN
    INSERT INTO historico_contas (conta_id, acao, dados_anteriores, dados_novos, usuario_id)
    VALUES (NEW.id, 'edicao', JSON_OBJECT(
        'status_anterior', OLD.status,
        'valor_pago_anterior', OLD.valor_pago
    ), JSON_OBJECT(
        'status_novo', NEW.status,
        'valor_pago_novo', NEW.valor_pago
    ), NEW.usuario_cadastro);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_contas_valor_pendente` BEFORE UPDATE ON `contas` FOR EACH ROW BEGIN
    SET NEW.valor_pendente = NEW.valor_original - NEW.valor_pago;
    
    -- Atualizar status baseado no valor pendente
    IF NEW.valor_pago >= NEW.valor_original THEN
        SET NEW.status = 'pago';
    ELSEIF NEW.valor_pago > 0 THEN
        SET NEW.status = 'pago_parcial';
    ELSEIF NEW.data_vencimento < CURDATE() AND NEW.status = 'pendente' THEN
        SET NEW.status = 'vencido';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contatos_fornecedores`
--

CREATE TABLE `contatos_fornecedores` (
  `id` int NOT NULL,
  `fornecedor_id` int NOT NULL,
  `nome` varchar(255) NOT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `eh_principal` tinyint(1) DEFAULT '0',
  `observacoes` text,
  `data_cadastro` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cotacoes_fornecedores`
--

CREATE TABLE `cotacoes_fornecedores` (
  `id` int NOT NULL,
  `envio_id` int NOT NULL,
  `item_lista_id` int NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `quantidade_disponivel` decimal(10,2) DEFAULT NULL,
  `prazo_entrega` int DEFAULT '0',
  `observacoes` text,
  `data_cotacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `valida_ate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Cotações recebidas dos fornecedores para os itens';

-- --------------------------------------------------------

--
-- Estrutura para tabela `envios_lista_fornecedores`
--

CREATE TABLE `envios_lista_fornecedores` (
  `id` int NOT NULL,
  `lista_id` int NOT NULL,
  `fornecedor_id` int NOT NULL,
  `data_envio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `meio_envio` enum('whatsapp','email','telefone','presencial') DEFAULT 'whatsapp',
  `status_resposta` enum('enviado','visualizado','respondido','cotacao_recebida','sem_resposta') DEFAULT 'enviado',
  `prazo_resposta` date DEFAULT NULL,
  `valor_cotacao` decimal(10,2) DEFAULT '0.00',
  `observacoes_fornecedor` text,
  `data_resposta` timestamp NULL DEFAULT NULL,
  `usuario_envio` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Controle de envios das listas para fornecedores';

-- --------------------------------------------------------

--
-- Estrutura para tabela `extras_tipos`
--

CREATE TABLE `extras_tipos` (
  `id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text,
  `valor_padrao` decimal(10,2) DEFAULT '0.00',
  `cor` varchar(7) DEFAULT '#28a745',
  `icone` varchar(50) DEFAULT 'fas fa-star',
  `ativo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `extras_tipos`
--

INSERT INTO `extras_tipos` (`id`, `nome`, `descricao`, `valor_padrao`, `cor`, `icone`, `ativo`, `created_at`, `updated_at`) VALUES
(1, 'Meta 1 Atingida', 'Bônus por atingir metas de vendas/produtividade', 250.00, '#28a745', 'fas fa-trophy', 1, '2025-06-01 21:26:42', '2025-06-01 22:22:05'),
(2, 'Zero Reclamações', 'Prêmio por mês sem reclamações de clientes', 100.00, '#17a2b8', 'fas fa-thumbs-up', 1, '2025-06-01 21:26:42', '2025-06-01 22:24:07'),
(3, 'Pontualidade', 'Bônus por pontualidade perfeita no mês', 1.00, '#ffc107', 'fas fa-clock', 1, '2025-06-01 21:26:42', '2025-06-01 22:26:21'),
(4, 'Excelência no Atendimento', 'Prêmio por excelência no atendimento ao cliente', 30.00, '#6f42c1', 'fas fa-star', 1, '2025-06-01 21:26:42', '2025-06-01 22:21:21'),
(5, 'Vendedor do Mês', 'Prêmio para o melhor vendedor do mês', 500.00, '#fd7e14', 'fas fa-medal', 0, '2025-06-01 21:26:42', '2025-06-01 22:23:44'),
(6, 'Meta 2 Atingida', 'Bônus especial por superar metas estabelecidas', 200.00, '#dc3545', 'fas fa-rocket', 1, '2025-06-01 21:26:42', '2025-06-01 22:23:15'),
(7, 'Colaboração em Equipe', 'Reconhecimento por trabalho em equipe exemplar', 100.00, '#20c997', 'fas fa-users', 1, '2025-06-01 21:26:42', '2025-06-01 22:24:57'),
(8, 'Inovação', 'Prêmio por sugestões e melhorias implementadas', 30.00, '#e83e8c', 'fas fa-lightbulb', 1, '2025-06-01 21:26:42', '2025-06-01 22:21:38');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedores`
--

CREATE TABLE `fornecedores` (
  `id` int NOT NULL,
  `nome` varchar(255) NOT NULL,
  `empresa` varchar(255) DEFAULT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `endereco` text,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `observacoes` text,
  `tipo_fornecedor` enum('produtos','servicos','ambos') DEFAULT 'produtos',
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `avaliacao` decimal(2,1) DEFAULT '5.0',
  `prazo_entrega_padrao` int DEFAULT '7' COMMENT 'dias',
  `forma_pagamento_preferida` varchar(100) DEFAULT NULL,
  `limite_credito` decimal(10,2) DEFAULT '0.00',
  `data_cadastro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_ultima_atualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cadastrado_por` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `fornecedores`
--

INSERT INTO `fornecedores` (`id`, `nome`, `empresa`, `cnpj`, `telefone`, `whatsapp`, `email`, `endereco`, `cidade`, `estado`, `cep`, `observacoes`, `tipo_fornecedor`, `status`, `avaliacao`, `prazo_entrega_padrao`, `forma_pagamento_preferida`, `limite_credito`, `data_cadastro`, `data_ultima_atualizacao`, `cadastrado_por`) VALUES
(1, 'João Silva', 'Distribuidora Silva & Cia', NULL, '(11) 98765-4321', '5511987654321', 'joao@silva.com', 'Rua das Flores, 123', 'São Paulo', 'SP', NULL, NULL, 'produtos', 'ativo', 5.0, 7, NULL, 0.00, '2025-06-06 21:56:48', '2025-06-06 21:56:48', NULL),
(2, 'Maria Santos', 'Café Premium Ltda', NULL, '(11) 97654-3210', '5511976543210', 'maria@cafepremium.com', 'Av. Paulista, 456', 'São Paulo', 'SP', NULL, NULL, 'produtos', 'ativo', 5.0, 7, NULL, 0.00, '2025-06-06 21:56:48', '2025-06-06 21:56:48', NULL),
(4, 'Josue', 'Dunka', '33.389.659/0001-30', '(61) 99119-0352', '(61) 99119-0352', 'jghoste@gmail.com', 'QNP 5 CONJUNTO E', 'Brasília', 'DF', '72250-309', '', 'produtos', 'ativo', 4.0, 1, 'Boleto', 2000.00, '2025-06-06 23:15:50', '2025-06-06 23:15:50', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedor_categorias`
--

CREATE TABLE `fornecedor_categorias` (
  `fornecedor_id` int NOT NULL,
  `categoria_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `fornecedor_categorias`
--

INSERT INTO `fornecedor_categorias` (`fornecedor_id`, `categoria_id`) VALUES
(1, 1),
(2, 1),
(4, 1),
(1, 2),
(2, 5);

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcionarios`
--

CREATE TABLE `funcionarios` (
  `id` int NOT NULL,
  `codigo` varchar(10) DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `rg` varchar(20) DEFAULT NULL,
  `cargo` varchar(50) DEFAULT NULL,
  `departamento` varchar(50) DEFAULT NULL,
  `salario` decimal(10,2) DEFAULT NULL,
  `data_admissao` date DEFAULT NULL,
  `data_demissao` date DEFAULT NULL,
  `telefone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `endereco` text,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('ativo','inativo','ferias','licenca') DEFAULT NULL,
  `horario_entrada` time DEFAULT '08:00:00',
  `horario_saida` time DEFAULT '18:00:00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `funcionarios`
--

INSERT INTO `funcionarios` (`id`, `codigo`, `nome`, `cpf`, `rg`, `cargo`, `departamento`, `salario`, `data_admissao`, `data_demissao`, `telefone`, `email`, `endereco`, `foto`, `status`, `horario_entrada`, `horario_saida`, `created_at`, `updated_at`) VALUES
(1, '0001', 'JOSUEL DA SILVA MENEZES', '99846748191', '2313975', 'Diretor', 'Administrativo', 1.00, '2023-01-31', NULL, '61991190352', 'jghoste@gmail.com', 'QNP 5 CONJUNTO E\r\nCasa 32a', 'uploads/funcionarios/0001_1748803120.png', 'ativo', '08:00:00', '18:00:00', '2025-06-01 18:38:40', '2025-06-01 22:36:15'),
(2, '0002', 'Estefany Menezes', '01825821160', '', 'Coordenador', 'Financeiro', 1.00, '2023-01-31', NULL, '61992086827', 'niely.sp@gmail.com', 'Qs 5 Rua 600', 'uploads/funcionarios/0002_1748817280.jpg', 'ativo', '07:00:00', '18:00:00', '2025-06-01 22:34:40', '2025-06-01 22:35:54');

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcionarios_extras`
--

CREATE TABLE `funcionarios_extras` (
  `id` int NOT NULL,
  `funcionario_id` int NOT NULL,
  `extra_tipo_id` int NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `mes_referencia` date NOT NULL,
  `observacao` text,
  `concedido_por` int NOT NULL,
  `concedido_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `senha_verificada` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `funcionarios_extras`
--

INSERT INTO `funcionarios_extras` (`id`, `funcionario_id`, `extra_tipo_id`, `valor`, `mes_referencia`, `observacao`, `concedido_por`, `concedido_em`, `senha_verificada`, `created_at`) VALUES
(1, 2, 3, 1.00, '2025-06-01', '', 1, '2025-06-01 23:13:23', 1, '2025-06-01 23:13:23');

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_contas`
--

CREATE TABLE `historico_contas` (
  `id` int NOT NULL,
  `conta_id` int NOT NULL,
  `acao` enum('criacao','edicao','pagamento','cancelamento','reativacao') NOT NULL,
  `dados_anteriores` json DEFAULT NULL,
  `dados_novos` json DEFAULT NULL,
  `observacoes` text,
  `usuario_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_listas_compras`
--

CREATE TABLE `historico_listas_compras` (
  `id` int NOT NULL,
  `lista_id` int NOT NULL,
  `acao` varchar(100) NOT NULL,
  `descricao` text,
  `usuario_id` int NOT NULL,
  `data_acao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `dados_anteriores` json DEFAULT NULL,
  `dados_novos` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Histórico de alterações nas listas de compras';

-- --------------------------------------------------------

--
-- Estrutura para tabela `horarios_trabalho`
--

CREATE TABLE `horarios_trabalho` (
  `id` int NOT NULL,
  `funcionario_id` int NOT NULL,
  `dia_semana` tinyint NOT NULL COMMENT '1=Segunda, 2=Terça, ..., 7=Domingo',
  `hora_entrada` time NOT NULL DEFAULT '08:00:00',
  `hora_almoco_saida` time DEFAULT '12:00:00',
  `hora_almoco_volta` time DEFAULT '13:00:00',
  `hora_saida` time NOT NULL DEFAULT '17:00:00',
  `ativo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `horarios_trabalho`
--

INSERT INTO `horarios_trabalho` (`id`, `funcionario_id`, `dia_semana`, `hora_entrada`, `hora_almoco_saida`, `hora_almoco_volta`, `hora_saida`, `ativo`, `created_at`, `updated_at`) VALUES
(1, 2, 1, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 1, '2025-06-01 23:49:53', '2025-06-01 23:49:53'),
(2, 1, 1, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 1, '2025-06-01 23:49:53', '2025-06-01 23:49:53'),
(3, 2, 2, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 1, '2025-06-01 23:49:53', '2025-06-01 23:49:53'),
(4, 1, 2, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 1, '2025-06-01 23:49:53', '2025-06-01 23:49:53'),
(5, 2, 3, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 1, '2025-06-01 23:49:53', '2025-06-01 23:49:53'),
(6, 1, 3, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 1, '2025-06-01 23:49:53', '2025-06-01 23:49:53'),
(7, 2, 4, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 1, '2025-06-01 23:49:53', '2025-06-01 23:49:53'),
(8, 1, 4, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 1, '2025-06-01 23:49:53', '2025-06-01 23:49:53'),
(9, 2, 5, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 1, '2025-06-01 23:49:53', '2025-06-01 23:49:53'),
(10, 1, 5, '08:00:00', '12:00:00', '13:00:00', '17:00:00', 1, '2025-06-01 23:49:53', '2025-06-01 23:49:53');

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_lista_compras`
--

CREATE TABLE `itens_lista_compras` (
  `id` int NOT NULL,
  `lista_id` int NOT NULL,
  `produto_descricao` varchar(300) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `unidade` varchar(20) DEFAULT 'un',
  `preco_estimado` decimal(10,2) DEFAULT '0.00',
  `preco_final` decimal(10,2) DEFAULT '0.00',
  `fornecedor_sugerido_id` int DEFAULT NULL,
  `fornecedor_escolhido_id` int DEFAULT NULL,
  `observacoes` text,
  `status_item` enum('pendente','cotado','aprovado','comprado') DEFAULT 'pendente',
  `ordem` int DEFAULT '0',
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Itens individuais de cada lista de compras';

--
-- Despejando dados para a tabela `itens_lista_compras`
--

INSERT INTO `itens_lista_compras` (`id`, `lista_id`, `produto_descricao`, `categoria`, `quantidade`, `unidade`, `preco_estimado`, `preco_final`, `fornecedor_sugerido_id`, `fornecedor_escolhido_id`, `observacoes`, `status_item`, `ordem`, `data_criacao`) VALUES
(1, 1, 'Detergente neutro 5L', 'Limpeza', 2.00, 'un', 15.50, 0.00, NULL, NULL, 'Marca de boa qualidade', 'pendente', 0, '2025-06-07 19:43:46'),
(2, 1, 'Papel higiênico dupla folha', 'Higiene', 20.00, 'un', 8.90, 0.00, NULL, NULL, 'Pacote com 4 rolos', 'pendente', 0, '2025-06-07 19:43:46'),
(3, 1, 'Café em grãos premium', 'Alimentação', 5.00, 'kg', 35.00, 0.00, NULL, NULL, 'Torrado e moído na hora', 'pendente', 0, '2025-06-07 19:43:46'),
(4, 1, 'Açúcar cristal', 'Alimentação', 10.00, 'kg', 4.50, 0.00, NULL, NULL, 'Pacote de 1kg', 'pendente', 0, '2025-06-07 19:43:46'),
(5, 1, 'Guardanapos de papel', 'Descartáveis', 50.00, 'pc', 2.80, 0.00, NULL, NULL, 'Pacote com 50 unidades', 'pendente', 0, '2025-06-07 19:43:46');

--
-- Acionadores `itens_lista_compras`
--
DELIMITER $$
CREATE TRIGGER `tr_atualizar_valor_estimado_lista` AFTER INSERT ON `itens_lista_compras` FOR EACH ROW BEGIN
    UPDATE listas_compras 
    SET valor_estimado = (
        SELECT COALESCE(SUM(quantidade * preco_estimado), 0)
        FROM itens_lista_compras 
        WHERE lista_id = NEW.lista_id
    )
    WHERE id = NEW.lista_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_atualizar_valor_estimado_lista_delete` AFTER DELETE ON `itens_lista_compras` FOR EACH ROW BEGIN
    UPDATE listas_compras 
    SET valor_estimado = (
        SELECT COALESCE(SUM(quantidade * preco_estimado), 0)
        FROM itens_lista_compras 
        WHERE lista_id = OLD.lista_id
    )
    WHERE id = OLD.lista_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_atualizar_valor_estimado_lista_update` AFTER UPDATE ON `itens_lista_compras` FOR EACH ROW BEGIN
    UPDATE listas_compras 
    SET valor_estimado = (
        SELECT COALESCE(SUM(quantidade * preco_estimado), 0)
        FROM itens_lista_compras 
        WHERE lista_id = NEW.lista_id
    )
    WHERE id = NEW.lista_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_atualizar_valor_final_lista` AFTER UPDATE ON `itens_lista_compras` FOR EACH ROW BEGIN
    IF NEW.preco_final != OLD.preco_final THEN
        UPDATE listas_compras 
        SET valor_final = (
            SELECT COALESCE(SUM(quantidade * preco_final), 0)
            FROM itens_lista_compras 
            WHERE lista_id = NEW.lista_id AND preco_final > 0
        )
        WHERE id = NEW.lista_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_pedido_fornecedor`
--

CREATE TABLE `itens_pedido_fornecedor` (
  `id` int NOT NULL,
  `pedido_id` int NOT NULL,
  `produto_id` int DEFAULT NULL,
  `descricao_item` varchar(255) NOT NULL,
  `quantidade` decimal(10,3) NOT NULL,
  `unidade` varchar(20) DEFAULT 'UN',
  `valor_unitario` decimal(10,2) NOT NULL,
  `valor_total` decimal(10,2) GENERATED ALWAYS AS ((`quantidade` * `valor_unitario`)) STORED,
  `observacoes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `itens_pedido_fornecedor`
--

INSERT INTO `itens_pedido_fornecedor` (`id`, `pedido_id`, `produto_id`, `descricao_item`, `quantidade`, `unidade`, `valor_unitario`, `observacoes`) VALUES
(1, 1, NULL, 'pao de queijo', 8.000, 'PCT', 25.00, '');

--
-- Acionadores `itens_pedido_fornecedor`
--
DELIMITER $$
CREATE TRIGGER `atualizar_valor_pedido_fornecedor` AFTER INSERT ON `itens_pedido_fornecedor` FOR EACH ROW BEGIN
    UPDATE pedidos_fornecedores 
    SET valor_total = (
        SELECT COALESCE(SUM(valor_total), 0) 
        FROM itens_pedido_fornecedor 
        WHERE pedido_id = NEW.pedido_id
    )
    WHERE id = NEW.pedido_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `atualizar_valor_pedido_fornecedor_delete` AFTER DELETE ON `itens_pedido_fornecedor` FOR EACH ROW BEGIN
    UPDATE pedidos_fornecedores 
    SET valor_total = (
        SELECT COALESCE(SUM(valor_total), 0) 
        FROM itens_pedido_fornecedor 
        WHERE pedido_id = OLD.pedido_id
    )
    WHERE id = OLD.pedido_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `atualizar_valor_pedido_fornecedor_update` AFTER UPDATE ON `itens_pedido_fornecedor` FOR EACH ROW BEGIN
    UPDATE pedidos_fornecedores 
    SET valor_total = (
        SELECT COALESCE(SUM(valor_total), 0) 
        FROM itens_pedido_fornecedor 
        WHERE pedido_id = NEW.pedido_id
    )
    WHERE id = NEW.pedido_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_venda`
--

CREATE TABLE `itens_venda` (
  `id` int NOT NULL,
  `venda_id` int NOT NULL,
  `produto_id` int NOT NULL,
  `quantidade` int NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

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
(172, 95, 61, 1, 4.50),
(173, 96, 183, 1, 7.00),
(174, 96, 61, 1, 4.50),
(175, 97, 192, 1, 13.90),
(176, 97, 128, 4, 8.00),
(177, 98, 230, 6, 1.00),
(178, 98, 90, 1, 2.00),
(179, 99, 93, 1, 9.90),
(180, 100, 184, 1, 7.00),
(181, 101, 200, 1, 9.00),
(182, 101, 162, 1, 7.90),
(183, 101, 64, 1, 5.00),
(184, 101, 68, 1, 11.90),
(185, 101, 126, 1, 8.00),
(186, 102, 2, 5, 1.00),
(187, 102, 68, 1, 11.90),
(188, 103, 2, 3, 1.00),
(189, 104, 183, 1, 7.00),
(190, 104, 2, 5, 1.00),
(191, 105, 208, 1, 9.90),
(192, 105, 147, 1, 1.00),
(193, 105, 2, 4, 1.00),
(194, 106, 239, 1, 16.00),
(195, 106, 6, 1, 3.00),
(196, 107, 235, 2, 13.50),
(197, 107, 1, 1, 18.90),
(198, 107, 64, 1, 5.00),
(199, 108, 1, 1, 18.90),
(200, 108, 61, 1, 4.50),
(201, 109, 68, 1, 11.90),
(202, 110, 234, 1, 12.50),
(203, 110, 6, 1, 3.00),
(204, 111, 221, 1, 18.90),
(205, 112, 122, 1, 10.00),
(206, 113, 1, 1, 18.90),
(207, 114, 1, 1, 18.90),
(208, 115, 81, 1, 9.90),
(209, 116, 122, 1, 10.00),
(210, 117, 27, 1, 5.50),
(211, 118, 2, 4, 1.00),
(212, 119, 119, 1, 11.90),
(213, 120, 41, 1, 7.00),
(214, 120, 61, 1, 4.50),
(215, 120, 206, 1, 14.90),
(216, 120, 135, 1, 1.00),
(217, 120, 145, 1, 2.00),
(218, 120, 147, 1, 1.00),
(219, 120, 64, 1, 5.00),
(220, 120, 157, 12, 0.50),
(221, 121, 32, 1, 183.41),
(222, 122, 33, 1, 22.00),
(223, 123, 64, 1, 5.00),
(224, 123, 200, 1, 9.00),
(225, 123, 1, 1, 18.90),
(226, 124, 96, 1, 4.00),
(227, 124, 230, 3, 1.00),
(228, 124, 61, 1, 4.50),
(229, 124, 191, 1, 13.90),
(230, 124, 145, 1, 2.00),
(231, 124, 135, 1, 1.00),
(232, 125, 221, 1, 18.90),
(233, 126, 61, 1, 4.50),
(234, 127, 2, 3, 1.00),
(235, 128, 2, 4, 1.00),
(236, 129, 2, 5, 1.00),
(237, 129, 78, 1, 3.00),
(238, 130, 2, 4, 1.00),
(239, 130, 90, 1, 2.00),
(240, 131, 192, 2, 13.90),
(241, 131, 196, 1, 8.00),
(242, 131, 63, 1, 6.00),
(243, 132, 90, 3, 2.00),
(244, 133, 96, 2, 4.00),
(245, 133, 61, 1, 4.50),
(246, 133, 57, 1, 3.00),
(247, 134, 200, 1, 9.00),
(248, 134, 104, 1, 8.50),
(249, 135, 90, 1, 2.00),
(250, 136, 1, 1, 18.90),
(251, 137, 126, 2, 4.90),
(252, 138, 1, 1, 18.90),
(253, 138, 168, 1, 5.00),
(254, 139, 64, 1, 5.00),
(255, 140, 128, 1, 4.90),
(256, 141, 206, 1, 14.90),
(257, 141, 146, 1, 3.00),
(258, 142, 1, 1, 18.90),
(259, 142, 61, 1, 4.50),
(260, 143, 1, 1, 18.90),
(261, 143, 57, 1, 3.00),
(262, 144, 122, 1, 10.00),
(263, 145, 122, 1, 10.00),
(264, 146, 81, 1, 9.90),
(265, 146, 227, 1, 14.90),
(266, 146, 2, 1, 1.00),
(267, 147, 186, 1, 7.00),
(268, 148, 68, 1, 11.90),
(269, 149, 68, 1, 11.90),
(270, 150, 90, 2, 2.00),
(271, 151, 166, 1, 4.00),
(272, 151, 61, 1, 4.50),
(273, 151, 178, 1, 8.00),
(274, 152, 86, 1, 4.00),
(275, 153, 230, 8, 1.00),
(276, 154, 1, 1, 18.90),
(277, 154, 64, 1, 5.00),
(278, 155, 207, 1, 13.90),
(279, 155, 2, 3, 1.00),
(280, 156, 116, 1, 7.00),
(281, 156, 200, 1, 9.00),
(282, 157, 221, 1, 18.90),
(283, 157, 58, 1, 6.00),
(284, 158, 56, 1, 3.50),
(285, 159, 185, 1, 7.00),
(286, 159, 96, 1, 4.00),
(287, 159, 99, 1, 8.50),
(288, 160, 194, 1, 10.00),
(289, 160, 90, 1, 2.00),
(290, 160, 2, 4, 1.00),
(291, 160, 4, 1, 3.00),
(292, 161, 231, 1, 5.50),
(293, 161, 226, 1, 8.00),
(294, 162, 2, 5, 1.00),
(295, 163, 192, 1, 13.90),
(296, 163, 68, 1, 11.90),
(297, 164, 239, 1, 16.00),
(298, 164, 200, 1, 9.00),
(299, 165, 198, 1, 14.00),
(300, 165, 68, 1, 11.90),
(301, 166, 96, 5, 4.00),
(302, 166, 81, 1, 9.90),
(303, 167, 90, 1, 2.00),
(304, 168, 123, 1, 6.00),
(305, 169, 123, 1, 6.00),
(306, 170, 6, 1, 3.00),
(307, 171, 58, 1, 6.00),
(308, 171, 177, 1, 7.00),
(309, 172, 225, 1, 7.00),
(310, 173, 64, 1, 5.00),
(311, 173, 186, 1, 7.00),
(312, 174, 227, 1, 14.90),
(313, 174, 61, 1, 4.50),
(314, 175, 41, 1, 7.00),
(315, 175, 64, 1, 5.00),
(316, 175, 10, 1, 7.90),
(317, 176, 232, 1, 12.50),
(318, 176, 234, 1, 12.50),
(319, 177, 1, 1, 18.90),
(320, 177, 1, 1, 18.90),
(321, 177, 64, 2, 5.00),
(322, 178, 162, 1, 7.90),
(323, 179, 1, 1, 18.90),
(324, 179, 56, 1, 3.50),
(325, 180, 81, 1, 9.90),
(326, 180, 1, 1, 18.90),
(327, 181, 122, 1, 10.00),
(328, 181, 163, 1, 4.00),
(329, 182, 230, 3, 1.00),
(330, 183, 122, 1, 10.00),
(331, 184, 230, 2, 1.00),
(332, 184, 123, 1, 6.00),
(333, 185, 4, 1, 3.00),
(334, 186, 3, 1, 9.90),
(335, 187, 64, 1, 5.00),
(336, 187, 96, 1, 4.00),
(337, 188, 164, 2, 2.00),
(338, 189, 122, 1, 10.00),
(339, 189, 57, 1, 3.00),
(340, 190, 86, 1, 4.00),
(341, 191, 32, 1, 169.10),
(342, 192, 201, 1, 7.00),
(343, 193, 2, 4, 1.00),
(344, 194, 10, 1, 7.90),
(345, 195, 3, 1, 9.90),
(346, 196, 61, 1, 4.50),
(347, 197, 27, 1, 5.50),
(348, 197, 74, 1, 3.00),
(349, 198, 224, 1, 8.00),
(350, 199, 194, 1, 10.00),
(351, 199, 162, 1, 7.90),
(352, 200, 4, 1, 3.00),
(353, 201, 64, 1, 5.00),
(354, 201, 237, 1, 11.00),
(355, 202, 63, 1, 6.00),
(356, 203, 195, 1, 10.00),
(357, 204, 192, 1, 13.90),
(358, 205, 64, 1, 5.00),
(359, 205, 240, 1, 18.90),
(360, 206, 240, 1, 18.90),
(361, 206, 64, 1, 5.00),
(362, 207, 230, 5, 1.00),
(363, 207, 90, 1, 2.00),
(364, 207, 240, 1, 18.90),
(365, 207, 61, 1, 4.50),
(366, 208, 222, 1, 7.00),
(367, 209, 32, 1, 217.70),
(368, 210, 210, 1, 16.90),
(369, 210, 145, 1, 2.00),
(370, 210, 147, 1, 1.00),
(371, 211, 157, 3, 0.50),
(372, 212, 237, 1, 11.00),
(373, 213, 192, 1, 13.90),
(374, 214, 164, 6, 2.00),
(375, 215, 122, 1, 10.00),
(376, 216, 126, 1, 8.00),
(377, 217, 199, 1, 9.00),
(378, 217, 142, 1, 2.00),
(379, 217, 105, 1, 7.00),
(380, 217, 238, 1, 10.00),
(381, 217, 240, 1, 18.90),
(382, 218, 164, 7, 2.00),
(383, 219, 64, 1, 5.00),
(384, 220, 90, 3, 2.00),
(385, 220, 2, 4, 1.00),
(386, 221, 115, 1, 7.00),
(387, 221, 185, 1, 7.00),
(388, 222, 182, 1, 7.00),
(389, 223, 27, 1, 5.50),
(390, 223, 58, 1, 6.00),
(391, 224, 178, 1, 8.00),
(392, 225, 90, 3, 2.00),
(393, 225, 2, 4, 1.00),
(394, 226, 221, 1, 18.90),
(395, 227, 189, 1, 16.00),
(396, 227, 145, 1, 2.00),
(397, 227, 62, 1, 4.50),
(398, 228, 4, 1, 3.00),
(399, 228, 186, 1, 7.00),
(400, 229, 61, 1, 4.50),
(401, 230, 224, 1, 8.00),
(402, 230, 147, 1, 1.00),
(403, 230, 145, 1, 2.00),
(404, 230, 64, 1, 5.00),
(405, 231, 231, 1, 5.50),
(406, 232, 163, 1, 4.00),
(407, 233, 63, 1, 6.00),
(408, 234, 221, 1, 18.90),
(409, 234, 73, 1, 6.00),
(410, 235, 221, 1, 18.90),
(411, 235, 73, 1, 6.00),
(412, 236, 64, 1, 5.00),
(413, 236, 122, 1, 10.00),
(414, 237, 221, 1, 18.90),
(415, 237, 83, 1, 9.90),
(416, 238, 71, 1, 4.50),
(417, 239, 64, 1, 5.00),
(418, 240, 239, 1, 16.00),
(419, 240, 64, 1, 5.00),
(420, 241, 231, 1, 5.50),
(421, 242, 192, 1, 13.90),
(422, 243, 241, 1, 18.90),
(423, 244, 182, 1, 7.00),
(424, 245, 1, 1, 18.90),
(425, 245, 61, 1, 4.50),
(426, 246, 241, 1, 18.90),
(427, 246, 61, 1, 4.50),
(428, 247, 241, 1, 18.90),
(429, 247, 57, 1, 3.00),
(430, 247, 6, 1, 3.00),
(431, 247, 61, 1, 4.50),
(432, 248, 241, 1, 18.90),
(433, 248, 64, 1, 5.00),
(434, 249, 214, 1, 14.90),
(435, 249, 142, 1, 2.00),
(436, 249, 241, 1, 18.90),
(437, 249, 64, 1, 5.00),
(438, 249, 232, 1, 12.50),
(439, 249, 162, 1, 7.90),
(440, 250, 221, 1, 18.90),
(441, 251, 196, 1, 8.00),
(442, 252, 4, 1, 3.00),
(443, 252, 185, 1, 7.00),
(444, 253, 2, 4, 1.00),
(445, 254, 2, 4, 1.00),
(446, 254, 90, 1, 2.00),
(447, 255, 2, 2, 1.00),
(448, 255, 90, 1, 2.00),
(449, 256, 4, 1, 3.00),
(450, 256, 180, 1, 7.00),
(451, 257, 2, 3, 1.00),
(452, 258, 201, 1, 7.00),
(453, 258, 68, 1, 11.90),
(454, 259, 115, 1, 7.00),
(455, 260, 197, 1, 7.00),
(456, 261, 60, 1, 4.50),
(457, 261, 230, 16, 1.00),
(458, 262, 195, 1, 10.00),
(459, 263, 185, 1, 7.00),
(460, 263, 101, 1, 8.50),
(461, 264, 55, 1, 6.00),
(462, 265, 192, 1, 13.90),
(463, 266, 182, 2, 7.00),
(464, 266, 61, 1, 4.50),
(465, 267, 81, 1, 9.90),
(466, 268, 4, 1, 3.00),
(467, 269, 164, 1, 2.00),
(468, 269, 238, 1, 10.00),
(469, 270, 64, 1, 5.00),
(470, 271, 73, 1, 6.00),
(471, 271, 185, 1, 7.00),
(472, 271, 168, 1, 5.00),
(473, 272, 1, 1, 18.90),
(474, 272, 56, 1, 3.50),
(475, 272, 81, 2, 9.90),
(476, 273, 237, 1, 11.00),
(477, 274, 1, 1, 18.90),
(478, 274, 109, 1, 7.00),
(479, 274, 158, 1, 4.00),
(480, 274, 68, 1, 11.90),
(481, 275, 1, 1, 18.90),
(482, 275, 64, 1, 5.00),
(483, 276, 1, 2, 18.90),
(484, 276, 62, 1, 4.50),
(485, 277, 186, 1, 7.00),
(486, 277, 61, 1, 4.50),
(487, 278, 1, 1, 18.90),
(488, 278, 61, 1, 4.50),
(489, 278, 123, 1, 6.00),
(490, 279, 1, 1, 18.90),
(491, 280, 122, 1, 10.00),
(492, 281, 236, 1, 12.50),
(493, 281, 3, 1, 9.90),
(494, 282, 178, 1, 8.00),
(495, 282, 17, 1, 7.00),
(496, 283, 51, 1, 6.00),
(497, 283, 166, 1, 4.00),
(498, 284, 232, 1, 12.50),
(499, 285, 2, 7, 1.00),
(500, 286, 3, 1, 9.90),
(501, 286, 186, 1, 7.00),
(502, 287, 166, 1, 4.00),
(503, 288, 90, 2, 2.00),
(504, 289, 182, 1, 7.00),
(505, 290, 90, 2, 2.00),
(506, 291, 189, 1, 16.00),
(507, 291, 145, 1, 2.00),
(508, 291, 135, 1, 1.00),
(509, 291, 61, 1, 4.50),
(510, 292, 56, 1, 3.50),
(511, 293, 224, 1, 8.00),
(512, 293, 147, 1, 1.00),
(513, 293, 146, 1, 3.00),
(514, 293, 90, 1, 2.00),
(515, 294, 178, 1, 8.00),
(516, 294, 3, 1, 9.90),
(517, 295, 96, 4, 4.00),
(518, 295, 3, 1, 9.90),
(519, 296, 162, 1, 7.90),
(520, 296, 237, 1, 11.00),
(521, 297, 64, 1, 5.00),
(522, 298, 168, 2, 5.00),
(523, 299, 158, 1, 4.00),
(524, 300, 200, 1, 9.00),
(525, 300, 56, 1, 3.50),
(526, 301, 1, 1, 18.90),
(527, 301, 61, 1, 4.50),
(528, 302, 200, 1, 9.00),
(529, 303, 1, 1, 18.90),
(530, 303, 63, 1, 6.00),
(531, 303, 185, 1, 7.00),
(532, 304, 55, 1, 6.00),
(533, 304, 230, 5, 1.00),
(534, 304, 90, 1, 2.00),
(535, 305, 195, 1, 10.00),
(536, 305, 57, 1, 3.00),
(537, 306, 230, 3, 1.00),
(538, 307, 1, 1, 18.90),
(539, 307, 133, 1, 1.50),
(540, 308, 238, 1, 10.00),
(541, 309, 185, 1, 7.00),
(542, 310, 27, 1, 5.50),
(543, 310, 162, 1, 7.90),
(544, 311, 238, 1, 10.00),
(545, 312, 155, 1, 4.00),
(546, 313, 200, 1, 9.00),
(547, 313, 236, 1, 12.50),
(548, 314, 27, 1, 5.50),
(549, 315, 61, 1, 4.50),
(550, 316, 68, 1, 11.90),
(551, 316, 2, 3, 1.00),
(552, 316, 177, 1, 7.00),
(553, 317, 115, 1, 7.00),
(554, 318, 61, 1, 4.50),
(555, 318, 178, 1, 8.00),
(556, 319, 2, 4, 1.00),
(557, 319, 74, 1, 3.00),
(558, 320, 201, 1, 7.00),
(559, 320, 17, 1, 7.00),
(560, 321, 2, 3, 1.00),
(561, 321, 90, 1, 2.00),
(562, 322, 27, 1, 5.50),
(563, 323, 90, 1, 2.00),
(564, 324, 208, 1, 9.90),
(565, 324, 154, 1, 1.00),
(566, 324, 107, 1, 7.00),
(567, 325, 201, 1, 7.00),
(568, 326, 238, 1, 10.00),
(569, 327, 56, 1, 3.50),
(570, 327, 162, 1, 7.90),
(571, 328, 122, 1, 10.00),
(572, 329, 90, 1, 2.00),
(573, 329, 64, 1, 5.00),
(574, 330, 61, 1, 4.50),
(575, 331, 199, 1, 9.00),
(576, 331, 142, 1, 2.00),
(577, 331, 115, 1, 7.00),
(578, 332, 199, 2, 9.00),
(579, 332, 142, 2, 2.00),
(580, 333, 240, 1, 18.90),
(581, 333, 162, 1, 7.90),
(582, 334, 64, 1, 5.00),
(583, 335, 221, 1, 18.90),
(584, 335, 116, 1, 7.00),
(585, 336, 238, 1, 10.00),
(586, 337, 232, 1, 12.50),
(587, 338, 166, 1, 4.00),
(588, 339, 107, 1, 7.00),
(589, 339, 6, 1, 3.00),
(590, 340, 238, 1, 10.00),
(591, 341, 185, 1, 7.00),
(592, 341, 64, 1, 5.00),
(593, 342, 2, 4, 1.00),
(594, 342, 63, 1, 6.00),
(595, 343, 201, 1, 7.00),
(596, 343, 64, 1, 5.00),
(597, 344, 31, 7, 10.00),
(598, 345, 158, 1, 4.00),
(599, 346, 2, 4, 1.00),
(600, 347, 56, 1, 3.50),
(601, 348, 223, 1, 2.50),
(602, 348, 2, 2, 1.00),
(603, 349, 96, 1, 4.00),
(604, 350, 185, 1, 7.00),
(605, 350, 58, 1, 6.00),
(606, 351, 61, 1, 4.50),
(607, 351, 200, 1, 9.00),
(608, 352, 230, 5, 1.00),
(609, 352, 231, 1, 5.50),
(610, 352, 115, 1, 7.00),
(611, 353, 240, 2, 18.90),
(612, 353, 64, 1, 5.00),
(613, 353, 162, 1, 7.90),
(614, 354, 138, 1, 6.00),
(615, 354, 133, 1, 1.50),
(616, 355, 182, 2, 7.00),
(617, 355, 61, 1, 4.50),
(618, 356, 199, 1, 9.00),
(619, 356, 93, 1, 9.90),
(620, 356, 200, 1, 9.00),
(621, 356, 2, 3, 1.00),
(622, 356, 90, 1, 2.00),
(623, 357, 202, 1, 4.00),
(624, 357, 115, 1, 7.00),
(625, 358, 78, 1, 3.00),
(626, 358, 186, 1, 7.00),
(627, 358, 240, 1, 18.90),
(628, 359, 64, 1, 5.00),
(629, 360, 238, 1, 10.00),
(630, 361, 90, 1, 2.00),
(631, 362, 64, 1, 5.00),
(632, 363, 64, 1, 5.00),
(633, 364, 231, 1, 5.50),
(634, 365, 68, 1, 11.90),
(635, 366, 230, 4, 1.00),
(636, 366, 61, 1, 4.50),
(637, 367, 2, 4, 1.00),
(638, 367, 74, 1, 3.00),
(639, 368, 200, 1, 9.00),
(640, 368, 155, 1, 4.00),
(641, 368, 164, 1, 2.00),
(642, 369, 118, 2, 11.90),
(643, 370, 197, 1, 7.00),
(644, 370, 80, 1, 9.90),
(645, 371, 200, 1, 9.00),
(646, 371, 155, 1, 4.00),
(647, 371, 164, 1, 2.00),
(648, 372, 232, 1, 12.50),
(649, 373, 185, 1, 7.00),
(650, 374, 6, 1, 3.00),
(651, 375, 185, 1, 7.00),
(652, 375, 58, 1, 6.00),
(653, 376, 185, 1, 7.00),
(654, 376, 2, 2, 1.00),
(655, 376, 90, 1, 2.00),
(656, 377, 27, 1, 5.50),
(657, 378, 90, 2, 2.00),
(658, 379, 200, 1, 9.00),
(659, 379, 81, 1, 9.90),
(660, 380, 2, 4, 1.00),
(661, 381, 2, 5, 1.00),
(662, 381, 3, 1, 9.90),
(663, 382, 200, 1, 9.00),
(664, 383, 2, 5, 1.00),
(665, 383, 90, 1, 2.00),
(666, 384, 200, 1, 9.00),
(667, 384, 96, 1, 4.00),
(668, 385, 224, 1, 8.00),
(669, 385, 147, 1, 1.00),
(670, 385, 145, 1, 2.00),
(671, 385, 65, 1, 5.00),
(672, 386, 123, 2, 6.00),
(673, 387, 223, 2, 2.50),
(674, 388, 2, 3, 1.00),
(675, 389, 2, 3, 1.00),
(676, 389, 90, 1, 2.00),
(677, 390, 3, 1, 9.90),
(678, 391, 240, 1, 18.90),
(679, 391, 64, 1, 5.00),
(680, 392, 238, 1, 10.00),
(681, 393, 27, 1, 5.50),
(682, 394, 200, 1, 9.00),
(683, 394, 230, 3, 1.00),
(684, 395, 240, 1, 18.90),
(685, 395, 64, 1, 5.00),
(686, 395, 238, 1, 10.00),
(687, 396, 207, 1, 13.90),
(688, 396, 145, 1, 2.00),
(689, 396, 147, 1, 1.00),
(690, 396, 61, 1, 4.50),
(691, 396, 180, 1, 7.00),
(692, 396, 231, 1, 5.50),
(693, 397, 58, 1, 6.00),
(694, 398, 208, 1, 9.90),
(695, 398, 61, 1, 4.50),
(696, 399, 181, 1, 7.00),
(697, 399, 4, 1, 3.00),
(698, 400, 237, 1, 11.00),
(699, 401, 61, 1, 4.50),
(700, 401, 185, 1, 7.00),
(701, 402, 96, 1, 4.00),
(702, 403, 90, 1, 2.00),
(703, 403, 223, 1, 2.50),
(704, 403, 2, 2, 1.00),
(705, 404, 56, 1, 3.50),
(706, 405, 56, 1, 3.50),
(707, 406, 68, 1, 11.90),
(708, 406, 2, 4, 1.00),
(709, 407, 178, 1, 8.00),
(710, 407, 200, 1, 9.00),
(711, 407, 61, 1, 4.50),
(712, 408, 194, 1, 10.00),
(713, 408, 64, 1, 5.00),
(714, 409, 90, 1, 2.00),
(715, 409, 2, 3, 1.00),
(716, 410, 228, 1, 14.90),
(717, 411, 165, 5, 1.00),
(718, 412, 228, 1, 14.90),
(719, 413, 126, 1, 9.00),
(720, 414, 206, 1, 14.90),
(721, 414, 147, 1, 1.00),
(722, 414, 135, 1, 1.00),
(723, 414, 154, 1, 1.00),
(724, 414, 2, 9, 1.00),
(725, 414, 61, 1, 4.50),
(726, 415, 56, 1, 3.50),
(727, 416, 238, 1, 10.00),
(728, 416, 2, 7, 1.00),
(729, 417, 63, 1, 6.00),
(730, 418, 1, 1, 18.90),
(731, 419, 1, 1, 18.90),
(732, 419, 127, 1, 9.00),
(733, 420, 228, 1, 14.90),
(734, 420, 79, 1, 3.00),
(735, 421, 122, 1, 10.00),
(736, 422, 61, 1, 4.50),
(737, 423, 59, 1, 12.00),
(738, 424, 2, 5, 1.00),
(739, 424, 4, 1, 3.00),
(740, 425, 2, 3, 1.00),
(741, 426, 178, 1, 8.00),
(742, 426, 74, 1, 3.00),
(743, 426, 185, 1, 7.00),
(744, 427, 182, 1, 7.00),
(745, 427, 178, 1, 8.00),
(746, 427, 63, 1, 6.00),
(747, 427, 126, 2, 8.00),
(748, 427, 58, 1, 6.00),
(749, 428, 2, 4, 1.00),
(750, 429, 200, 1, 9.00),
(751, 429, 90, 1, 2.00),
(752, 429, 61, 1, 4.50),
(753, 430, 200, 1, 9.00),
(754, 430, 90, 1, 2.00),
(755, 430, 178, 1, 8.00),
(756, 431, 90, 2, 2.00),
(757, 432, 56, 1, 3.50),
(758, 432, 124, 1, 6.00),
(759, 432, 185, 1, 7.00),
(760, 433, 192, 1, 13.90),
(761, 434, 162, 1, 7.90),
(762, 435, 238, 1, 10.00),
(763, 436, 2, 3, 1.00),
(764, 437, 166, 1, 4.00),
(765, 438, 200, 1, 9.00),
(766, 438, 83, 1, 9.90),
(767, 439, 166, 1, 4.00),
(768, 439, 58, 1, 6.00),
(769, 440, 180, 1, 7.00),
(770, 441, 179, 1, 14.90),
(771, 441, 64, 1, 5.00),
(772, 442, 207, 1, 13.90),
(773, 443, 1, 1, 18.90),
(774, 444, 166, 1, 4.00),
(775, 445, 232, 1, 12.50),
(776, 446, 203, 1, 16.90),
(777, 446, 224, 1, 8.00),
(778, 446, 90, 1, 2.00),
(779, 446, 9, 1, 2.00),
(780, 446, 101, 1, 8.50),
(781, 447, 206, 1, 14.90),
(782, 447, 61, 1, 4.50),
(783, 447, 166, 1, 4.00),
(784, 448, 221, 1, 18.90),
(785, 448, 107, 1, 7.00),
(786, 449, 1, 1, 18.90),
(787, 449, 4, 1, 3.00),
(788, 449, 237, 1, 11.00),
(789, 450, 63, 1, 6.00),
(790, 450, 187, 1, 7.00),
(791, 451, 1, 1, 18.90),
(792, 451, 147, 1, 1.00),
(793, 451, 135, 1, 1.00),
(794, 451, 133, 1, 1.50),
(795, 451, 59, 1, 12.00),
(796, 451, 166, 1, 4.00),
(797, 451, 198, 1, 14.00),
(798, 452, 2, 3, 1.00),
(799, 452, 90, 1, 2.00),
(800, 453, 189, 2, 16.00),
(801, 453, 145, 2, 2.00),
(802, 453, 115, 1, 7.00),
(803, 453, 90, 1, 2.00),
(804, 454, 2, 3, 1.00),
(805, 454, 90, 1, 2.00),
(806, 455, 66, 1, 3.50),
(807, 455, 185, 1, 7.00),
(808, 456, 63, 1, 6.00),
(809, 457, 237, 1, 11.00),
(810, 458, 238, 1, 10.00),
(811, 459, 227, 1, 14.90),
(812, 459, 67, 1, 3.50),
(813, 460, 166, 1, 4.00),
(814, 461, 223, 1, 2.50),
(815, 461, 2, 2, 1.00),
(816, 461, 90, 1, 2.00),
(817, 462, 179, 2, 12.00),
(818, 462, 64, 2, 5.00),
(819, 462, 126, 1, 8.00),
(820, 463, 232, 1, 12.50),
(821, 464, 61, 2, 4.50),
(822, 465, 240, 1, 18.90),
(823, 465, 64, 1, 5.00),
(824, 465, 2, 2, 1.00),
(825, 466, 240, 1, 18.90),
(826, 466, 63, 1, 6.00),
(827, 466, 238, 1, 10.00),
(828, 467, 189, 2, 16.00),
(829, 467, 145, 2, 2.00),
(830, 467, 144, 2, 1.50),
(831, 468, 240, 1, 18.90),
(832, 468, 61, 1, 4.50),
(833, 469, 240, 1, 18.90),
(834, 469, 60, 1, 4.50),
(835, 469, 2, 4, 1.00),
(836, 470, 198, 1, 14.00),
(837, 470, 4, 1, 3.00),
(838, 471, 229, 1, 14.90),
(839, 471, 238, 1, 10.00),
(840, 472, 198, 1, 14.00),
(841, 473, 2, 6, 1.00),
(842, 473, 90, 1, 2.00),
(843, 474, 116, 3, 7.00),
(844, 475, 180, 1, 7.00),
(845, 475, 4, 1, 3.00),
(846, 475, 164, 2, 2.00),
(847, 476, 74, 1, 3.00),
(848, 476, 182, 1, 7.00),
(849, 476, 2, 9, 1.00),
(850, 477, 199, 1, 9.00),
(851, 477, 93, 1, 9.90),
(852, 477, 200, 1, 9.00),
(853, 477, 230, 3, 1.00),
(854, 477, 178, 1, 8.00),
(855, 478, 61, 1, 4.50),
(856, 479, 72, 1, 5.00),
(857, 480, 2, 2, 1.00),
(858, 480, 223, 1, 2.50),
(859, 480, 6, 1, 3.00),
(860, 480, 18, 1, 4.00),
(861, 481, 2, 5, 1.00),
(862, 482, 197, 1, 7.00),
(863, 482, 142, 1, 2.00),
(864, 482, 96, 1, 4.00),
(865, 483, 198, 1, 14.00),
(866, 484, 80, 1, 9.90),
(867, 484, 82, 1, 9.90),
(868, 485, 2, 2, 1.00),
(869, 485, 180, 1, 7.00),
(870, 485, 61, 1, 4.50),
(871, 486, 240, 1, 18.90),
(872, 486, 231, 1, 5.50),
(873, 487, 96, 2, 4.00),
(874, 487, 2, 2, 1.00),
(875, 487, 90, 1, 2.00),
(876, 487, 240, 2, 18.90),
(877, 487, 64, 2, 5.00),
(878, 488, 240, 1, 18.90),
(879, 489, 240, 1, 18.90),
(880, 489, 59, 1, 12.00),
(881, 490, 1, 1, 18.90),
(882, 491, 240, 1, 18.90),
(883, 492, 17, 1, 7.00),
(884, 493, 10, 1, 7.90),
(885, 494, 124, 1, 6.00),
(886, 495, 2, 3, 1.00),
(887, 495, 185, 1, 7.00),
(888, 495, 62, 1, 4.50),
(889, 496, 180, 1, 7.00),
(890, 496, 4, 1, 3.00),
(891, 497, 111, 1, 7.00),
(892, 498, 96, 1, 4.00),
(893, 498, 196, 1, 8.00),
(894, 499, 2, 7, 1.00),
(895, 500, 223, 2, 2.50),
(896, 500, 62, 1, 4.50),
(897, 501, 182, 1, 7.00),
(898, 501, 66, 1, 3.50),
(899, 502, 238, 1, 10.00),
(900, 503, 2, 4, 1.00),
(901, 503, 239, 1, 16.00),
(902, 504, 2, 4, 1.00),
(903, 504, 239, 1, 16.00),
(904, 505, 2, 4, 1.00),
(905, 505, 239, 1, 16.00),
(906, 506, 64, 1, 5.00),
(907, 507, 238, 1, 10.00),
(908, 507, 127, 1, 8.00),
(909, 507, 198, 1, 14.00),
(910, 507, 64, 1, 5.00),
(911, 507, 169, 1, 3.00),
(912, 508, 240, 1, 18.90),
(913, 508, 63, 1, 6.00),
(914, 509, 122, 1, 10.00),
(915, 510, 194, 1, 10.00),
(916, 510, 115, 1, 7.00),
(917, 511, 192, 1, 13.90),
(918, 511, 72, 1, 5.00),
(919, 512, 200, 1, 9.00),
(920, 513, 162, 1, 7.90),
(921, 513, 68, 1, 11.90),
(922, 514, 242, 1, 7.00),
(923, 514, 238, 1, 10.00),
(924, 515, 196, 1, 8.00),
(925, 516, 238, 1, 10.00),
(926, 517, 158, 1, 4.00),
(927, 517, 17, 1, 7.00),
(928, 518, 90, 2, 2.00),
(929, 519, 90, 2, 2.00),
(930, 520, 84, 1, 7.90),
(931, 520, 158, 1, 4.00),
(932, 521, 198, 1, 14.00),
(933, 521, 4, 1, 3.00),
(934, 522, 198, 1, 14.00),
(935, 523, 182, 1, 7.00),
(936, 523, 74, 1, 3.00),
(937, 524, 2, 5, 1.00),
(938, 525, 230, 4, 1.00),
(939, 526, 189, 1, 16.00),
(940, 526, 145, 1, 2.00),
(941, 526, 73, 1, 6.00),
(942, 527, 2, 5, 1.00),
(943, 527, 68, 1, 11.90),
(944, 528, 2, 5, 1.00),
(945, 529, 158, 1, 4.00),
(946, 530, 162, 1, 7.90),
(947, 531, 243, 1, 9.00),
(948, 531, 1, 1, 18.90),
(949, 531, 61, 1, 4.50),
(950, 532, 231, 1, 5.50),
(951, 532, 6, 1, 3.00),
(952, 533, 237, 1, 11.00),
(953, 534, 1, 1, 18.90),
(954, 534, 61, 1, 4.50),
(955, 534, 10, 1, 7.90),
(956, 535, 1, 1, 18.90),
(957, 536, 1, 1, 18.90),
(958, 536, 64, 1, 5.00),
(959, 536, 165, 3, 1.00),
(960, 537, 1, 2, 18.90),
(961, 537, 244, 1, 14.50),
(962, 537, 238, 1, 10.00),
(963, 538, 198, 1, 14.00),
(964, 538, 83, 1, 9.90),
(965, 539, 245, 1, 8.00),
(966, 540, 68, 1, 11.90),
(967, 541, 198, 1, 14.00),
(968, 541, 73, 1, 6.00),
(969, 542, 239, 1, 16.00),
(970, 543, 237, 1, 11.00),
(971, 544, 179, 1, 12.00),
(972, 544, 58, 1, 6.00),
(973, 545, 162, 1, 7.90),
(974, 546, 179, 1, 14.90),
(975, 546, 231, 1, 5.50),
(976, 547, 62, 1, 4.50),
(977, 547, 126, 1, 8.00),
(978, 548, 2, 5, 1.00),
(979, 549, 186, 1, 7.00),
(980, 550, 182, 1, 7.00),
(981, 550, 4, 1, 3.00),
(982, 551, 68, 1, 11.90),
(983, 552, 57, 1, 3.00),
(984, 553, 122, 1, 10.00),
(985, 553, 186, 1, 7.00),
(986, 553, 2, 5, 1.00),
(987, 553, 68, 1, 11.90),
(988, 554, 68, 1, 11.90),
(989, 554, 201, 1, 7.00),
(990, 555, 223, 2, 2.50),
(991, 555, 2, 1, 1.00),
(992, 556, 162, 1, 7.90),
(993, 557, 162, 1, 7.90),
(994, 558, 222, 1, 7.00),
(995, 559, 201, 1, 7.00),
(996, 559, 147, 1, 1.00),
(997, 560, 1, 1, 18.90),
(998, 561, 206, 1, 14.90),
(999, 561, 147, 1, 1.00),
(1000, 561, 107, 1, 7.00),
(1001, 562, 122, 1, 10.00),
(1002, 563, 73, 1, 6.00),
(1003, 563, 185, 1, 7.00),
(1004, 564, 157, 6, 0.50),
(1005, 565, 1, 2, 18.90),
(1006, 565, 64, 1, 5.00),
(1007, 566, 210, 1, 16.90),
(1008, 566, 135, 1, 1.00),
(1009, 566, 147, 1, 1.00),
(1010, 566, 145, 1, 2.00),
(1011, 566, 154, 1, 1.00),
(1012, 566, 133, 1, 1.50),
(1013, 567, 192, 1, 13.90),
(1014, 568, 200, 1, 9.00),
(1015, 568, 83, 1, 9.90),
(1016, 569, 178, 1, 8.00),
(1017, 569, 185, 1, 7.00),
(1018, 569, 61, 1, 4.50),
(1019, 570, 2, 5, 1.00),
(1020, 571, 221, 1, 18.90),
(1021, 572, 2, 2, 1.00),
(1022, 572, 93, 1, 9.90),
(1023, 573, 222, 1, 7.00),
(1024, 574, 60, 1, 4.50),
(1025, 574, 185, 1, 7.00),
(1026, 575, 182, 1, 7.00),
(1027, 575, 169, 1, 3.00),
(1028, 575, 162, 1, 7.90),
(1029, 575, 166, 2, 4.00),
(1030, 576, 240, 1, 18.90),
(1031, 576, 63, 1, 6.00),
(1032, 576, 198, 1, 14.00),
(1033, 576, 68, 1, 11.90),
(1034, 576, 128, 1, 8.00),
(1035, 576, 168, 1, 5.00),
(1036, 577, 240, 1, 18.90),
(1037, 577, 68, 1, 11.90),
(1038, 578, 240, 1, 18.90),
(1039, 578, 64, 1, 5.00),
(1040, 578, 168, 1, 5.00),
(1041, 579, 68, 1, 11.90),
(1042, 580, 240, 1, 18.90),
(1043, 580, 6, 1, 3.00),
(1044, 581, 199, 1, 9.00),
(1045, 581, 200, 1, 9.00),
(1046, 581, 68, 1, 11.90),
(1047, 582, 115, 1, 7.00),
(1048, 583, 10, 1, 7.90),
(1049, 583, 2, 4, 1.00),
(1050, 584, 187, 1, 7.00),
(1051, 585, 189, 2, 16.00),
(1052, 585, 145, 2, 2.00),
(1053, 585, 144, 2, 1.50),
(1054, 586, 185, 1, 7.00),
(1055, 587, 240, 1, 18.90),
(1056, 587, 122, 2, 10.00),
(1057, 587, 96, 1, 4.00),
(1058, 588, 200, 1, 9.00),
(1059, 589, 2, 5, 1.00),
(1060, 589, 67, 1, 3.50),
(1061, 590, 57, 1, 3.00),
(1062, 591, 158, 1, 4.00),
(1063, 591, 168, 1, 5.00),
(1064, 591, 164, 1, 2.00),
(1065, 592, 240, 1, 18.90),
(1066, 592, 64, 1, 5.00),
(1067, 592, 164, 1, 2.00),
(1068, 593, 107, 1, 7.00),
(1069, 594, 64, 1, 5.00),
(1070, 594, 61, 1, 4.50),
(1071, 595, 246, 1, 8.00),
(1072, 596, 237, 1, 11.00),
(1073, 597, 243, 1, 9.00),
(1074, 598, 61, 1, 4.50),
(1075, 599, 240, 1, 18.90),
(1076, 599, 114, 1, 7.00),
(1077, 600, 96, 1, 4.00),
(1078, 600, 2, 3, 1.00),
(1079, 601, 240, 1, 18.90),
(1080, 601, 61, 1, 4.50),
(1081, 601, 124, 1, 6.00),
(1082, 602, 114, 1, 7.00),
(1083, 602, 123, 1, 6.00),
(1084, 603, 182, 1, 7.00),
(1085, 604, 2, 10, 1.00),
(1086, 605, 201, 1, 7.00),
(1087, 605, 166, 1, 4.00),
(1088, 606, 58, 1, 6.00),
(1089, 607, 224, 1, 8.00),
(1090, 607, 63, 1, 6.00),
(1091, 607, 237, 1, 11.00),
(1092, 608, 10, 1, 7.90),
(1093, 609, 240, 1, 18.90),
(1094, 609, 73, 1, 6.00),
(1095, 610, 196, 2, 8.00),
(1096, 611, 234, 1, 12.50),
(1097, 612, 10, 1, 7.90),
(1098, 613, 240, 1, 18.90),
(1099, 613, 114, 1, 7.00),
(1100, 613, 68, 1, 11.90),
(1101, 613, 231, 1, 5.50),
(1102, 614, 162, 1, 7.90),
(1103, 614, 240, 1, 18.90),
(1104, 614, 10, 1, 7.90),
(1105, 615, 240, 1, 18.90),
(1106, 616, 68, 1, 11.90),
(1107, 617, 2, 4, 1.00),
(1108, 617, 240, 1, 18.90),
(1109, 617, 6, 1, 3.00),
(1110, 618, 223, 2, 2.50),
(1111, 619, 177, 1, 7.00),
(1112, 620, 222, 1, 7.00),
(1113, 621, 222, 1, 7.00),
(1114, 622, 222, 1, 7.00),
(1115, 623, 222, 1, 7.00),
(1116, 624, 222, 1, 7.00),
(1117, 625, 222, 1, 7.00),
(1118, 626, 222, 1, 7.00),
(1119, 627, 222, 1, 7.00),
(1120, 628, 222, 1, 7.00),
(1121, 629, 222, 1, 7.00),
(1122, 630, 222, 1, 7.00),
(1123, 631, 222, 1, 7.00),
(1124, 632, 58, 1, 6.00),
(1125, 632, 164, 2, 2.00),
(1126, 633, 201, 1, 7.00),
(1127, 634, 195, 1, 10.00),
(1128, 635, 57, 1, 3.00),
(1129, 636, 182, 1, 7.00),
(1130, 637, 191, 1, 13.90),
(1131, 637, 145, 1, 2.00),
(1132, 637, 81, 1, 9.90),
(1133, 638, 186, 1, 7.00),
(1134, 638, 78, 1, 3.00),
(1135, 639, 201, 1, 7.00),
(1136, 640, 191, 1, 13.90),
(1137, 640, 145, 1, 2.00),
(1138, 640, 3, 1, 9.90),
(1139, 641, 177, 1, 7.00),
(1140, 641, 17, 1, 7.00),
(1141, 642, 61, 1, 4.50),
(1142, 643, 1, 1, 18.90),
(1143, 643, 2, 4, 1.00),
(1144, 644, 1, 1, 18.90),
(1145, 644, 61, 1, 4.50),
(1146, 645, 1, 1, 18.90),
(1147, 646, 1, 1, 18.90),
(1148, 646, 243, 1, 9.00),
(1149, 647, 1, 1, 18.90),
(1150, 648, 126, 1, 8.00),
(1151, 649, 206, 1, 14.90),
(1152, 649, 145, 1, 2.00),
(1153, 649, 147, 1, 1.00),
(1154, 649, 61, 1, 4.50),
(1155, 649, 115, 1, 7.00),
(1156, 650, 200, 1, 9.00),
(1157, 650, 1, 1, 18.90),
(1158, 650, 2, 4, 1.00),
(1159, 650, 61, 1, 4.50),
(1160, 650, 175, 2, 7.00),
(1161, 651, 1, 2, 18.90),
(1162, 651, 235, 1, 13.50),
(1163, 652, 3, 1, 9.90),
(1164, 652, 203, 1, 16.90),
(1165, 653, 3, 1, 9.90),
(1166, 653, 203, 1, 16.90),
(1167, 654, 1, 1, 18.90),
(1168, 655, 201, 1, 7.00),
(1169, 656, 236, 1, 12.50),
(1170, 656, 122, 1, 10.00),
(1171, 657, 186, 1, 7.00),
(1172, 658, 68, 1, 11.90),
(1173, 659, 246, 1, 8.00),
(1174, 660, 85, 2, 9.00),
(1175, 661, 90, 1, 2.00),
(1176, 662, 201, 1, 7.00),
(1177, 663, 55, 1, 6.00),
(1178, 663, 182, 1, 7.00),
(1179, 664, 186, 1, 7.00),
(1180, 664, 6, 1, 3.00),
(1181, 665, 68, 1, 11.90),
(1182, 666, 61, 1, 4.50),
(1183, 667, 195, 1, 10.00),
(1184, 668, 90, 1, 2.00),
(1185, 669, 243, 2, 9.00),
(1186, 669, 64, 1, 5.00),
(1187, 670, 238, 1, 10.00),
(1188, 671, 72, 1, 5.00),
(1189, 672, 115, 1, 7.00),
(1190, 673, 1, 1, 18.90),
(1191, 673, 61, 1, 4.50),
(1192, 674, 114, 1, 7.00),
(1193, 675, 1, 1, 18.90),
(1194, 676, 243, 1, 9.00),
(1195, 676, 124, 1, 6.00),
(1196, 676, 156, 10, 0.20),
(1197, 677, 221, 1, 18.90),
(1198, 677, 1, 1, 18.90),
(1199, 677, 61, 1, 4.50),
(1200, 677, 63, 1, 6.00),
(1201, 678, 3, 1, 9.90),
(1202, 679, 60, 1, 4.50),
(1203, 680, 229, 1, 14.90),
(1204, 680, 182, 1, 7.00),
(1205, 681, 2, 4, 1.00),
(1206, 682, 200, 2, 9.00),
(1207, 682, 64, 1, 5.00),
(1208, 683, 202, 1, 4.00),
(1209, 683, 115, 1, 7.00),
(1210, 684, 221, 1, 18.90),
(1211, 684, 64, 1, 5.00),
(1212, 685, 122, 1, 10.00),
(1213, 686, 214, 1, 14.90),
(1214, 687, 114, 1, 7.00),
(1215, 688, 64, 2, 5.00),
(1216, 688, 200, 1, 9.00),
(1217, 689, 240, 1, 18.90),
(1218, 689, 61, 1, 4.50),
(1219, 690, 64, 1, 5.00),
(1220, 691, 61, 1, 4.50),
(1221, 692, 240, 1, 18.90),
(1222, 692, 122, 1, 10.00),
(1223, 692, 115, 1, 7.00),
(1224, 693, 240, 1, 18.90),
(1225, 694, 122, 1, 10.00),
(1226, 694, 131, 1, 8.00),
(1227, 695, 122, 1, 10.00),
(1228, 695, 131, 1, 8.00),
(1229, 696, 122, 1, 10.00),
(1230, 696, 131, 1, 8.00),
(1231, 697, 122, 1, 10.00),
(1232, 697, 131, 1, 8.00),
(1233, 698, 122, 1, 10.00),
(1234, 698, 131, 1, 8.00),
(1235, 699, 17, 1, 7.00),
(1236, 700, 2, 5, 1.00),
(1237, 700, 64, 1, 5.00),
(1238, 701, 126, 1, 8.00),
(1239, 702, 195, 1, 10.00),
(1240, 702, 145, 1, 2.00),
(1241, 703, 195, 1, 10.00),
(1242, 703, 145, 1, 2.00),
(1243, 704, 123, 1, 6.00),
(1244, 704, 78, 1, 3.00),
(1245, 705, 162, 1, 7.90),
(1246, 705, 165, 5, 1.00),
(1247, 706, 64, 1, 5.00),
(1248, 707, 96, 1, 4.00),
(1249, 707, 2, 3, 1.00),
(1250, 708, 68, 1, 11.90),
(1251, 709, 240, 1, 18.90),
(1252, 709, 10, 1, 7.90),
(1253, 709, 110, 1, 7.00),
(1254, 710, 90, 1, 2.00),
(1255, 711, 240, 1, 18.90),
(1256, 712, 240, 1, 18.90),
(1257, 712, 248, 1, 9.90),
(1258, 713, 240, 1, 18.90),
(1259, 713, 64, 1, 5.00),
(1260, 714, 1, 1, 18.90),
(1261, 714, 183, 1, 7.00),
(1262, 714, 64, 1, 5.00),
(1263, 715, 240, 1, 18.90),
(1264, 716, 240, 1, 18.90),
(1265, 716, 10, 1, 7.90),
(1266, 716, 2, 4, 1.00),
(1267, 717, 115, 1, 7.00),
(1268, 717, 10, 1, 7.90),
(1269, 718, 164, 2, 2.00),
(1270, 719, 240, 1, 18.90),
(1271, 719, 90, 2, 2.00),
(1272, 719, 2, 2, 1.00),
(1273, 719, 64, 2, 5.00),
(1274, 720, 240, 1, 18.90),
(1275, 720, 10, 1, 7.90),
(1276, 720, 247, 1, 6.00),
(1277, 721, 240, 1, 18.90),
(1278, 722, 2, 7, 1.00),
(1279, 723, 93, 1, 9.90),
(1280, 723, 200, 1, 9.00),
(1281, 723, 90, 1, 2.00),
(1282, 723, 2, 2, 1.00),
(1283, 724, 200, 1, 9.00),
(1284, 724, 61, 1, 4.50),
(1285, 725, 200, 1, 9.00),
(1286, 726, 116, 1, 7.00),
(1287, 727, 162, 1, 7.90),
(1288, 728, 2, 1, 1.00),
(1289, 729, 182, 1, 7.00),
(1290, 730, 64, 1, 5.00),
(1291, 730, 240, 1, 18.90),
(1292, 731, 201, 1, 7.00),
(1293, 731, 240, 1, 18.90),
(1294, 731, 247, 1, 6.00),
(1295, 732, 247, 1, 6.00),
(1296, 733, 96, 1, 4.00),
(1297, 733, 243, 1, 9.00),
(1298, 734, 240, 2, 18.90),
(1299, 734, 114, 2, 7.00),
(1300, 735, 2, 5, 1.00),
(1301, 735, 90, 1, 2.00),
(1302, 735, 240, 1, 18.90),
(1303, 735, 64, 1, 5.00),
(1304, 735, 250, 1, 7.00),
(1305, 736, 172, 1, 12.00),
(1306, 737, 240, 1, 18.90),
(1307, 737, 57, 1, 3.00),
(1308, 738, 251, 1, 7.00),
(1309, 738, 164, 1, 2.00),
(1310, 739, 158, 1, 4.00),
(1311, 740, 247, 1, 6.00),
(1312, 741, 198, 1, 14.00),
(1313, 741, 61, 1, 4.50),
(1314, 742, 10, 1, 7.90),
(1315, 742, 61, 1, 4.50),
(1316, 742, 64, 1, 5.00),
(1317, 743, 1, 1, 18.90),
(1318, 743, 63, 1, 6.00),
(1319, 743, 229, 1, 14.90),
(1320, 743, 243, 1, 9.00),
(1321, 743, 68, 1, 11.90),
(1322, 744, 64, 1, 5.00),
(1323, 745, 239, 1, 16.00),
(1324, 746, 57, 1, 3.00),
(1325, 747, 189, 2, 16.00),
(1326, 747, 145, 2, 2.00),
(1327, 747, 144, 2, 1.50),
(1328, 747, 238, 1, 10.00),
(1329, 747, 64, 1, 5.00),
(1330, 748, 231, 1, 5.50),
(1331, 749, 68, 1, 11.90),
(1332, 749, 6, 1, 3.00),
(1333, 750, 158, 1, 4.00),
(1334, 751, 231, 1, 5.50),
(1335, 752, 162, 1, 7.90),
(1336, 752, 182, 1, 7.00),
(1337, 753, 2, 3, 1.00),
(1338, 754, 105, 1, 7.00),
(1339, 755, 221, 1, 18.90),
(1340, 755, 58, 1, 6.00),
(1341, 755, 187, 1, 7.00),
(1342, 755, 74, 1, 3.00),
(1343, 756, 1, 1, 18.90),
(1344, 757, 242, 1, 7.00),
(1345, 757, 114, 1, 7.00),
(1346, 758, 67, 1, 3.50),
(1347, 758, 250, 1, 7.00),
(1348, 759, 231, 1, 5.50),
(1349, 760, 250, 2, 7.00),
(1350, 761, 200, 1, 9.00),
(1351, 761, 90, 1, 2.00),
(1352, 762, 90, 2, 2.00),
(1353, 762, 64, 1, 5.00),
(1354, 763, 182, 1, 7.00),
(1355, 763, 185, 1, 7.00),
(1356, 763, 58, 1, 6.00),
(1357, 763, 123, 1, 6.00),
(1358, 764, 1, 1, 18.90),
(1359, 764, 61, 1, 4.50),
(1360, 765, 199, 1, 9.00),
(1361, 765, 238, 1, 10.00),
(1362, 765, 68, 1, 11.90),
(1363, 766, 1, 1, 18.90),
(1364, 766, 59, 1, 12.00),
(1365, 766, 1, 1, 18.90),
(1366, 766, 115, 1, 7.00),
(1367, 766, 168, 4, 5.00),
(1368, 767, 168, 2, 5.00),
(1369, 767, 6, 2, 3.00),
(1370, 768, 205, 1, 12.00),
(1371, 768, 182, 1, 7.00),
(1372, 768, 64, 2, 5.00),
(1373, 769, 126, 1, 8.00),
(1374, 769, 3, 1, 9.90),
(1375, 769, 2, 4, 1.00),
(1376, 769, 64, 1, 5.00),
(1377, 769, 182, 1, 7.00),
(1378, 770, 123, 2, 6.00),
(1379, 771, 73, 1, 6.00),
(1380, 771, 57, 1, 3.00),
(1381, 772, 64, 1, 5.00),
(1382, 772, 57, 2, 3.00),
(1383, 773, 78, 1, 3.00),
(1384, 773, 182, 1, 7.00),
(1385, 774, 2, 3, 1.00),
(1386, 774, 61, 1, 4.50),
(1387, 775, 158, 1, 4.00),
(1388, 775, 6, 1, 3.00),
(1389, 775, 161, 1, 3.00),
(1390, 776, 61, 1, 4.50),
(1391, 777, 84, 1, 7.90),
(1392, 778, 85, 2, 9.00),
(1393, 779, 2, 4, 1.00),
(1394, 780, 68, 1, 11.90),
(1395, 780, 2, 5, 1.00),
(1396, 781, 17, 1, 7.00),
(1397, 781, 2, 2, 1.00),
(1398, 782, 234, 1, 12.50),
(1399, 783, 234, 1, 12.50),
(1400, 784, 64, 1, 5.00),
(1401, 785, 64, 1, 5.00),
(1402, 786, 4, 1, 3.00),
(1403, 787, 189, 2, 16.00),
(1404, 787, 145, 2, 2.00),
(1405, 787, 144, 2, 1.50),
(1406, 787, 64, 1, 5.00),
(1407, 787, 157, 2, 0.50),
(1408, 788, 237, 1, 11.00),
(1409, 789, 122, 1, 10.00),
(1410, 790, 51, 1, 6.00),
(1411, 791, 214, 1, 14.90),
(1412, 791, 145, 1, 2.00),
(1413, 791, 64, 1, 5.00),
(1414, 791, 6, 1, 3.00),
(1415, 792, 115, 1, 7.00),
(1416, 793, 192, 1, 13.90),
(1417, 793, 4, 1, 3.00),
(1418, 794, 1, 1, 18.90),
(1419, 794, 61, 1, 4.50),
(1420, 795, 200, 1, 9.00),
(1421, 795, 90, 1, 2.00),
(1422, 796, 2, 4, 1.00),
(1423, 796, 90, 1, 2.00),
(1424, 797, 78, 1, 3.00),
(1425, 797, 182, 1, 7.00),
(1426, 798, 243, 1, 9.00),
(1427, 799, 240, 1, 18.90),
(1428, 799, 81, 1, 9.90),
(1429, 800, 240, 1, 18.90),
(1430, 800, 238, 1, 10.00),
(1431, 801, 68, 1, 11.90),
(1432, 802, 90, 1, 2.00),
(1433, 803, 90, 1, 2.00),
(1434, 803, 64, 1, 5.00),
(1435, 804, 240, 1, 18.90),
(1436, 804, 65, 1, 5.00),
(1437, 805, 90, 1, 2.00),
(1438, 805, 178, 1, 8.00),
(1439, 805, 2, 3, 1.00),
(1440, 806, 252, 1, 10.00),
(1441, 807, 2, 5, 1.00),
(1442, 807, 90, 1, 2.00),
(1443, 807, 63, 1, 6.00),
(1444, 808, 78, 1, 3.50),
(1445, 809, 240, 1, 18.90),
(1446, 809, 65, 2, 5.00),
(1447, 810, 77, 1, 3.50),
(1448, 810, 237, 1, 11.00),
(1449, 811, 200, 1, 9.00),
(1450, 812, 221, 1, 18.90),
(1451, 812, 206, 1, 14.90),
(1452, 812, 145, 1, 2.00),
(1453, 813, 200, 1, 9.00),
(1454, 813, 117, 1, 7.00),
(1455, 814, 181, 1, 7.00),
(1456, 814, 96, 1, 4.00),
(1457, 815, 243, 1, 9.00),
(1458, 815, 199, 1, 9.00),
(1459, 815, 161, 1, 3.00),
(1460, 816, 195, 1, 10.00),
(1461, 817, 195, 1, 10.00),
(1462, 818, 240, 1, 18.90),
(1463, 819, 240, 1, 18.90),
(1464, 819, 3, 1, 9.90),
(1465, 819, 2, 4, 1.00),
(1466, 819, 90, 1, 2.00),
(1467, 820, 90, 1, 2.00),
(1468, 820, 64, 1, 5.00),
(1469, 820, 243, 1, 9.00),
(1470, 821, 240, 1, 18.90),
(1471, 821, 10, 1, 7.90),
(1472, 821, 58, 1, 6.00),
(1473, 821, 239, 1, 16.00),
(1474, 822, 240, 1, 18.90),
(1475, 823, 10, 1, 7.90),
(1476, 823, 2, 4, 1.00),
(1477, 823, 74, 1, 3.00),
(1478, 824, 240, 1, 18.90),
(1479, 824, 10, 1, 7.90),
(1480, 824, 58, 2, 6.00),
(1481, 824, 182, 1, 7.00),
(1482, 825, 4, 3, 3.50),
(1483, 825, 68, 1, 11.90),
(1484, 825, 125, 1, 5.00),
(1485, 826, 1, 1, 18.90),
(1486, 826, 158, 1, 4.00),
(1487, 827, 238, 1, 10.00),
(1488, 828, 64, 1, 5.00),
(1489, 828, 2, 7, 1.00),
(1490, 829, 3, 1, 9.90),
(1491, 830, 200, 1, 9.00),
(1492, 830, 64, 1, 5.00),
(1493, 831, 68, 1, 11.90),
(1494, 832, 228, 1, 14.90),
(1495, 832, 122, 1, 10.00),
(1496, 833, 122, 1, 10.00),
(1497, 834, 115, 1, 7.00),
(1498, 834, 192, 1, 13.90),
(1499, 835, 228, 1, 14.90),
(1500, 835, 67, 1, 3.50),
(1501, 835, 237, 1, 11.00),
(1502, 836, 73, 1, 6.00),
(1503, 837, 110, 1, 7.00),
(1504, 837, 58, 1, 6.00),
(1505, 837, 2, 2, 1.00),
(1506, 837, 185, 1, 7.00),
(1507, 838, 115, 1, 7.00),
(1508, 838, 191, 1, 13.90),
(1509, 838, 145, 1, 2.00),
(1510, 839, 187, 1, 7.00),
(1511, 839, 51, 1, 6.00),
(1512, 839, 61, 1, 4.50),
(1513, 839, 239, 1, 16.00),
(1514, 840, 228, 1, 14.90),
(1515, 840, 3, 1, 9.90),
(1516, 841, 68, 1, 11.90),
(1517, 841, 6, 1, 3.00),
(1518, 841, 229, 1, 14.90),
(1519, 841, 182, 1, 7.00),
(1520, 842, 57, 1, 3.00),
(1521, 843, 2, 2, 1.00),
(1522, 843, 90, 1, 2.00),
(1523, 843, 1, 1, 18.90),
(1524, 843, 68, 1, 11.90),
(1525, 843, 64, 1, 5.00),
(1526, 844, 240, 1, 18.90),
(1527, 845, 2, 5, 1.00),
(1528, 846, 184, 1, 7.00),
(1529, 846, 72, 1, 5.00),
(1530, 847, 68, 1, 11.90),
(1531, 848, 68, 1, 11.90),
(1532, 848, 51, 1, 6.00),
(1533, 849, 234, 1, 12.50),
(1534, 850, 199, 1, 9.00),
(1535, 850, 68, 1, 11.90),
(1536, 850, 184, 1, 7.00),
(1537, 850, 221, 1, 18.90),
(1538, 850, 178, 1, 8.00),
(1539, 850, 181, 1, 7.00),
(1540, 850, 61, 1, 4.50),
(1541, 851, 78, 1, 3.50),
(1542, 852, 115, 1, 7.00),
(1543, 853, 106, 1, 7.00),
(1544, 854, 158, 2, 4.00),
(1545, 855, 2, 8, 1.00),
(1546, 855, 90, 1, 2.00),
(1547, 855, 78, 1, 3.50),
(1548, 856, 192, 1, 13.90),
(1549, 857, 1, 1, 18.90),
(1550, 858, 61, 1, 4.50),
(1551, 859, 240, 1, 18.90),
(1552, 859, 246, 1, 8.00),
(1553, 859, 162, 1, 7.90),
(1554, 859, 157, 12, 0.50),
(1555, 859, 68, 1, 11.90),
(1556, 859, 64, 1, 5.00),
(1557, 859, 1, 1, 18.90),
(1558, 860, 10, 1, 7.90),
(1559, 861, 240, 1, 18.90),
(1560, 861, 246, 1, 8.00),
(1561, 861, 10, 1, 7.90),
(1562, 862, 3, 1, 9.90),
(1563, 863, 2, 2, 1.00),
(1564, 863, 78, 1, 3.50),
(1565, 864, 198, 1, 14.00),
(1566, 865, 185, 1, 7.00),
(1567, 866, 14, 1, 15.00),
(1568, 866, 240, 1, 18.90),
(1569, 866, 71, 1, 4.50),
(1570, 866, 235, 1, 13.50),
(1571, 867, 240, 1, 18.90),
(1572, 867, 248, 1, 9.90),
(1573, 867, 10, 1, 7.90),
(1574, 867, 177, 1, 7.00),
(1575, 868, 10, 1, 7.90),
(1576, 869, 56, 1, 3.50),
(1577, 870, 229, 1, 14.90),
(1578, 870, 64, 1, 5.00),
(1579, 871, 58, 1, 6.00),
(1580, 871, 178, 1, 8.00),
(1581, 871, 2, 2, 1.00),
(1582, 872, 185, 1, 7.00),
(1583, 872, 81, 1, 9.90),
(1584, 872, 123, 4, 6.00),
(1585, 873, 123, 4, 6.00),
(1586, 874, 250, 1, 7.00),
(1587, 875, 198, 1, 14.00),
(1588, 875, 61, 1, 4.50),
(1589, 876, 224, 1, 8.00),
(1590, 876, 2, 1, 1.00),
(1591, 877, 185, 1, 7.00),
(1592, 877, 61, 1, 4.50),
(1593, 877, 2, 2, 1.00),
(1594, 878, 201, 1, 7.00),
(1595, 879, 240, 2, 18.90),
(1596, 879, 85, 1, 9.00),
(1597, 879, 248, 1, 9.90),
(1598, 880, 240, 1, 18.90),
(1599, 880, 57, 1, 3.00),
(1600, 881, 164, 2, 2.00),
(1601, 882, 122, 2, 10.00),
(1602, 883, 182, 1, 7.00),
(1603, 883, 221, 1, 18.90),
(1604, 883, 63, 1, 6.00),
(1605, 884, 90, 1, 2.00),
(1606, 884, 200, 1, 9.00),
(1607, 884, 240, 1, 18.90),
(1608, 885, 2, 4, 1.00),
(1609, 885, 5, 1, 7.00),
(1610, 885, 198, 1, 14.00),
(1611, 885, 65, 1, 5.00),
(1612, 886, 196, 1, 8.00),
(1613, 887, 200, 1, 9.00),
(1614, 888, 74, 2, 3.00),
(1615, 888, 185, 2, 7.00),
(1616, 889, 155, 1, 4.00),
(1617, 890, 182, 1, 7.00),
(1618, 890, 200, 1, 9.00),
(1619, 891, 228, 1, 14.90),
(1620, 891, 80, 1, 9.90),
(1621, 892, 188, 1, 22.00),
(1622, 893, 1, 1, 18.90),
(1623, 893, 205, 1, 12.00),
(1624, 893, 162, 1, 7.90),
(1625, 894, 61, 1, 4.50),
(1626, 895, 74, 1, 3.00),
(1627, 895, 2, 4, 1.00),
(1628, 895, 243, 2, 9.00),
(1629, 895, 1, 1, 18.90),
(1630, 895, 64, 1, 5.00),
(1631, 896, 61, 1, 4.50),
(1632, 896, 2, 4, 1.00),
(1633, 897, 249, 1, 7.00),
(1634, 897, 2, 4, 1.00),
(1635, 897, 1, 1, 18.90),
(1636, 897, 3, 1, 9.90),
(1637, 897, 58, 1, 6.00),
(1638, 897, 228, 1, 14.90),
(1639, 898, 211, 1, 14.90),
(1640, 898, 145, 1, 2.00),
(1641, 898, 61, 1, 4.50),
(1642, 898, 115, 1, 7.00),
(1643, 898, 228, 1, 14.90),
(1644, 899, 228, 1, 14.90),
(1645, 899, 17, 1, 7.00),
(1646, 899, 1, 1, 18.90),
(1647, 899, 64, 1, 5.00),
(1648, 900, 189, 1, 16.00),
(1649, 900, 145, 1, 2.00),
(1650, 900, 135, 1, 1.00),
(1651, 900, 90, 1, 2.00),
(1652, 900, 228, 1, 14.90),
(1653, 901, 3, 1, 9.90),
(1654, 901, 242, 1, 7.00),
(1655, 902, 2, 4, 1.00),
(1656, 903, 243, 1, 9.00),
(1657, 903, 80, 1, 9.90),
(1658, 903, 185, 1, 7.00),
(1659, 903, 192, 1, 13.90),
(1660, 904, 187, 1, 7.00),
(1661, 904, 63, 1, 6.00),
(1662, 904, 122, 1, 10.00),
(1663, 904, 200, 1, 9.00),
(1664, 905, 243, 1, 9.00),
(1665, 905, 186, 1, 7.00),
(1666, 906, 189, 2, 16.00),
(1667, 906, 145, 2, 2.00),
(1668, 906, 144, 2, 1.50),
(1669, 907, 185, 1, 7.00),
(1670, 907, 51, 1, 6.00),
(1671, 907, 61, 1, 4.50),
(1672, 908, 122, 2, 10.00),
(1673, 908, 194, 1, 10.00),
(1674, 908, 1, 1, 18.90),
(1675, 909, 88, 1, 6.00),
(1676, 909, 189, 1, 16.00),
(1677, 909, 145, 1, 2.00),
(1678, 909, 147, 1, 1.00),
(1679, 909, 96, 1, 4.00),
(1680, 909, 90, 1, 2.00),
(1681, 910, 200, 1, 9.00),
(1682, 910, 243, 1, 9.00),
(1683, 911, 196, 1, 8.00),
(1684, 911, 90, 1, 2.00),
(1685, 912, 120, 1, 11.90),
(1686, 913, 253, 1, 450.00),
(1687, 914, 202, 1, 4.00),
(1688, 915, 122, 1, 10.00),
(1689, 915, 68, 1, 11.90),
(1690, 916, 63, 1, 6.00),
(1691, 916, 162, 1, 7.90),
(1692, 916, 164, 1, 2.00),
(1693, 916, 240, 1, 18.90),
(1694, 916, 59, 1, 12.00),
(1695, 916, 238, 1, 10.00),
(1696, 916, 68, 1, 11.90),
(1697, 916, 200, 1, 9.00),
(1698, 917, 235, 1, 13.50),
(1699, 918, 200, 1, 9.00),
(1700, 919, 240, 1, 18.90),
(1701, 919, 114, 1, 7.00),
(1702, 919, 178, 1, 8.00),
(1703, 919, 90, 1, 2.00),
(1704, 920, 240, 1, 18.90),
(1705, 920, 65, 1, 5.00),
(1706, 921, 122, 1, 10.00),
(1707, 922, 240, 1, 18.90),
(1708, 922, 90, 2, 2.00),
(1709, 922, 64, 1, 5.00),
(1710, 923, 169, 1, 3.00),
(1711, 924, 62, 1, 4.50),
(1712, 924, 2, 5, 1.00),
(1713, 924, 90, 1, 2.00),
(1714, 924, 1, 1, 18.90),
(1715, 924, 62, 1, 4.50),
(1716, 924, 61, 1, 4.50),
(1717, 924, 243, 1, 9.00),
(1718, 924, 2, 51, 1.00),
(1719, 924, 182, 1, 7.00),
(1720, 924, 177, 1, 7.00),
(1721, 924, 2, 5, 1.00),
(1722, 924, 90, 1, 2.00),
(1723, 924, 182, 1, 7.00),
(1724, 924, 1, 1, 18.90),
(1725, 925, 240, 1, 18.90),
(1726, 925, 235, 1, 13.50),
(1727, 925, 56, 1, 3.50),
(1728, 926, 192, 1, 13.90),
(1729, 926, 64, 2, 5.00),
(1730, 926, 72, 1, 5.00),
(1731, 926, 68, 1, 11.90),
(1732, 926, 196, 1, 8.00),
(1733, 927, 235, 1, 13.50),
(1734, 928, 1, 1, 18.90),
(1735, 928, 243, 2, 9.00),
(1736, 929, 195, 1, 10.00),
(1737, 930, 123, 1, 6.00),
(1738, 931, 200, 1, 9.00),
(1739, 931, 58, 1, 6.00),
(1740, 931, 243, 1, 9.00),
(1741, 932, 176, 1, 10.00),
(1742, 933, 56, 1, 3.50),
(1743, 934, 254, 1, 9.90),
(1744, 934, 178, 1, 8.00),
(1745, 935, 6, 1, 3.00),
(1746, 936, 200, 1, 9.00),
(1747, 937, 188, 1, 22.00),
(1748, 937, 58, 1, 6.00),
(1749, 938, 77, 2, 3.50),
(1750, 939, 1, 1, 18.90),
(1751, 939, 68, 1, 11.90),
(1752, 940, 158, 1, 4.00),
(1753, 941, 96, 1, 4.00),
(1754, 941, 254, 1, 9.90),
(1755, 942, 2, 3, 1.00),
(1756, 942, 123, 1, 6.00),
(1757, 943, 2, 3, 1.00),
(1758, 943, 115, 1, 7.00),
(1759, 943, 79, 1, 3.50),
(1760, 943, 61, 1, 4.50),
(1761, 944, 90, 1, 2.00),
(1762, 944, 182, 1, 7.00),
(1763, 945, 254, 1, 9.90),
(1764, 945, 17, 1, 7.00),
(1765, 945, 185, 1, 7.00),
(1766, 945, 58, 1, 6.00),
(1767, 946, 122, 1, 10.00),
(1768, 947, 64, 1, 5.00),
(1769, 948, 64, 1, 5.00),
(1770, 949, 122, 1, 10.00),
(1771, 950, 55, 1, 6.00),
(1772, 950, 48, 1, 10.00),
(1773, 951, 90, 1, 2.00),
(1774, 951, 200, 1, 9.00),
(1775, 951, 78, 1, 3.50),
(1776, 952, 2, 4, 1.00),
(1777, 952, 90, 1, 2.00),
(1778, 953, 230, 7, 1.00),
(1779, 953, 177, 1, 7.00),
(1780, 953, 164, 1, 2.00),
(1781, 953, 183, 1, 7.00),
(1782, 953, 236, 1, 12.50),
(1783, 953, 2, 7, 1.00),
(1784, 953, 74, 1, 3.00),
(1785, 953, 163, 1, 4.00),
(1786, 954, 79, 1, 3.50),
(1787, 954, 168, 1, 5.00),
(1788, 954, 79, 1, 3.50),
(1789, 955, 2, 6, 1.00),
(1790, 955, 90, 1, 2.00),
(1791, 955, 2, 6, 1.00),
(1792, 955, 2, 5, 1.00),
(1793, 955, 90, 1, 2.00),
(1794, 956, 2, 4, 1.00),
(1795, 956, 2, 4, 1.00),
(1796, 957, 191, 1, 13.90),
(1797, 957, 145, 1, 2.00),
(1798, 957, 78, 1, 3.50),
(1799, 957, 61, 1, 4.50),
(1800, 957, 192, 1, 13.90),
(1801, 957, 145, 1, 2.00),
(1802, 958, 68, 1, 11.90),
(1803, 959, 191, 1, 13.90),
(1804, 959, 147, 1, 1.00),
(1805, 959, 107, 1, 7.00),
(1806, 960, 177, 1, 7.00),
(1807, 960, 61, 1, 4.50),
(1808, 960, 17, 1, 7.00),
(1809, 960, 201, 1, 7.00),
(1810, 961, 1, 1, 18.90),
(1811, 962, 2, 3, 1.00),
(1812, 963, 78, 1, 3.50),
(1813, 964, 63, 1, 6.00),
(1814, 964, 48, 1, 15.00),
(1815, 965, 182, 1, 7.00),
(1816, 965, 158, 1, 4.00),
(1817, 965, 1, 1, 18.90),
(1818, 966, 1, 1, 18.90),
(1819, 967, 1, 1, 18.90),
(1820, 968, 197, 1, 7.00),
(1821, 968, 144, 1, 1.50),
(1822, 968, 142, 1, 2.00),
(1823, 968, 107, 1, 7.00),
(1824, 969, 79, 1, 3.50),
(1825, 969, 164, 1, 2.00),
(1826, 970, 99, 1, 8.50),
(1827, 970, 164, 3, 2.00),
(1828, 971, 200, 1, 9.00),
(1829, 971, 237, 1, 11.00),
(1830, 971, 28, 1, 10.00),
(1831, 972, 83, 1, 9.90),
(1832, 972, 2, 3, 1.00),
(1833, 972, 228, 1, 14.90),
(1834, 972, 255, 1, 10.00),
(1835, 973, 191, 1, 13.90),
(1836, 973, 147, 1, 1.00),
(1837, 973, 107, 1, 7.00),
(1838, 974, 200, 1, 9.00),
(1839, 974, 2, 4, 1.00),
(1840, 975, 57, 1, 3.00),
(1841, 976, 61, 1, 4.50),
(1842, 977, 203, 1, 16.90),
(1843, 977, 83, 1, 9.90),
(1844, 978, 210, 1, 16.90),
(1845, 978, 135, 1, 1.00),
(1846, 978, 145, 1, 2.00),
(1847, 978, 153, 1, 1.50),
(1848, 978, 65, 1, 5.00),
(1849, 979, 1, 1, 18.90),
(1850, 979, 59, 1, 12.00),
(1851, 979, 238, 1, 10.00),
(1852, 979, 63, 2, 6.00),
(1853, 979, 48, 1, 15.00),
(1854, 979, 68, 1, 11.90),
(1855, 979, 68, 1, 11.90),
(1856, 979, 201, 1, 7.00),
(1857, 979, 48, 2, 15.00),
(1858, 979, 64, 1, 5.00),
(1859, 980, 63, 1, 6.00),
(1860, 981, 123, 1, 6.00),
(1861, 981, 178, 1, 8.00),
(1862, 981, 123, 1, 6.00),
(1863, 982, 2, 3, 1.00),
(1864, 983, 201, 1, 7.00),
(1865, 983, 90, 1, 2.00),
(1866, 984, 2, 4, 1.00),
(1867, 984, 185, 1, 7.00),
(1868, 984, 63, 1, 6.00),
(1869, 984, 158, 1, 4.00),
(1870, 984, 78, 1, 3.50),
(1871, 985, 2, 5, 1.00),
(1872, 985, 199, 1, 9.00),
(1873, 985, 90, 1, 2.00),
(1874, 985, 57, 1, 3.00),
(1875, 986, 2, 3, 1.00),
(1876, 986, 90, 1, 2.00),
(1877, 987, 227, 1, 14.90),
(1878, 988, 1, 1, 18.90),
(1879, 988, 61, 1, 4.50),
(1880, 988, 68, 1, 11.90),
(1881, 988, 256, 1, 8.90),
(1882, 989, 68, 1, 11.90),
(1883, 989, 158, 1, 4.00),
(1884, 990, 61, 1, 4.50),
(1885, 991, 61, 1, 4.50),
(1886, 992, 63, 1, 6.00),
(1887, 993, 240, 1, 18.90),
(1888, 993, 231, 1, 5.50),
(1889, 994, 240, 1, 18.90),
(1890, 994, 77, 1, 3.50),
(1891, 994, 90, 1, 2.00),
(1892, 994, 235, 1, 13.50),
(1893, 995, 240, 1, 18.90),
(1894, 995, 80, 2, 9.90),
(1895, 995, 123, 1, 6.00),
(1896, 996, 178, 1, 8.00),
(1897, 997, 189, 1, 16.00),
(1898, 997, 145, 1, 2.00),
(1899, 997, 240, 1, 18.90),
(1900, 997, 61, 1, 4.50),
(1901, 997, 64, 1, 5.00),
(1902, 997, 238, 1, 10.00),
(1903, 998, 80, 1, 9.90),
(1904, 998, 2, 4, 1.00),
(1905, 998, 229, 1, 14.90),
(1906, 999, 219, 1, 9.90),
(1907, 999, 145, 1, 2.00),
(1908, 999, 249, 2, 7.00),
(1909, 999, 185, 1, 7.00),
(1910, 1000, 200, 1, 9.00),
(1911, 1000, 90, 1, 2.00),
(1912, 1001, 2, 5, 1.00),
(1913, 1002, 61, 1, 4.50),
(1914, 1003, 240, 1, 18.90),
(1915, 1004, 240, 1, 18.90),
(1916, 1004, 64, 1, 5.00),
(1917, 1004, 158, 1, 4.00),
(1918, 1005, 240, 1, 18.90),
(1919, 1006, 240, 1, 18.90),
(1920, 1006, 61, 1, 4.50),
(1921, 1007, 61, 1, 4.50),
(1922, 1007, 182, 1, 7.00),
(1923, 1008, 240, 2, 18.90),
(1924, 1008, 64, 1, 5.00),
(1925, 1009, 240, 1, 18.90),
(1926, 1009, 10, 1, 7.90),
(1927, 1009, 68, 1, 11.90),
(1928, 1010, 177, 1, 7.00),
(1929, 1011, 240, 1, 18.90),
(1930, 1012, 199, 1, 9.00),
(1931, 1012, 3, 2, 9.90),
(1932, 1012, 177, 1, 7.00),
(1933, 1012, 240, 1, 18.90),
(1934, 1012, 61, 1, 4.50),
(1935, 1013, 117, 1, 7.00),
(1936, 1014, 240, 1, 18.90),
(1937, 1014, 61, 2, 4.50),
(1938, 1014, 227, 1, 14.90),
(1939, 1015, 1, 1, 18.90),
(1940, 1016, 56, 1, 3.50),
(1941, 1016, 61, 1, 4.50),
(1942, 1016, 178, 1, 8.00),
(1943, 1016, 118, 1, 11.90),
(1944, 1016, 178, 1, 8.00),
(1945, 1016, 254, 1, 9.90),
(1946, 1016, 2, 3, 1.00),
(1947, 1017, 226, 1, 8.00),
(1948, 1017, 178, 1, 8.00),
(1949, 1017, 254, 1, 9.90),
(1950, 1018, 194, 1, 10.00),
(1951, 1019, 2, 5, 1.00),
(1952, 1020, 78, 1, 3.50),
(1953, 1020, 257, 1, 2.00),
(1954, 1021, 90, 1, 2.00),
(1955, 1021, 239, 1, 16.00),
(1956, 1021, 184, 1, 7.00),
(1957, 1022, 63, 1, 6.00),
(1958, 1022, 186, 1, 7.00),
(1959, 1023, 114, 1, 7.00),
(1960, 1023, 80, 1, 9.90),
(1961, 1023, 240, 1, 18.90),
(1962, 1023, 80, 1, 9.90),
(1963, 1023, 155, 1, 4.00),
(1964, 1023, 81, 1, 9.90),
(1965, 1024, 158, 1, 4.00),
(1966, 1024, 2, 5, 1.00),
(1967, 1024, 178, 1, 8.00),
(1968, 1025, 2, 6, 1.00),
(1969, 1025, 57, 1, 3.00),
(1970, 1026, 240, 1, 18.90),
(1971, 1026, 58, 3, 6.00),
(1972, 1026, 90, 2, 2.00),
(1973, 1026, 68, 1, 11.90),
(1974, 1026, 56, 1, 3.50),
(1975, 1026, 237, 1, 11.00),
(1976, 1026, 221, 1, 18.90),
(1977, 1026, 249, 1, 7.00),
(1978, 1026, 178, 1, 8.00),
(1979, 1027, 114, 1, 7.00),
(1980, 1028, 200, 1, 9.00),
(1981, 1028, 77, 2, 3.50),
(1982, 1028, 1, 1, 18.90),
(1983, 1028, 114, 1, 7.00),
(1984, 1029, 238, 1, 10.00),
(1985, 1029, 257, 1, 2.00),
(1986, 1029, 199, 1, 9.00),
(1987, 1029, 115, 1, 7.00),
(1988, 1030, 240, 1, 18.90),
(1989, 1031, 222, 1, 7.00),
(1990, 1031, 134, 1, 3.50),
(1991, 1031, 115, 1, 7.00),
(1992, 1031, 201, 1, 7.00),
(1993, 1031, 95, 1, 2.50),
(1994, 1032, 10, 1, 7.90),
(1995, 1032, 62, 1, 4.50),
(1996, 1032, 185, 1, 7.00),
(1997, 1032, 78, 1, 3.50),
(1998, 1032, 68, 2, 11.90),
(1999, 1032, 239, 1, 16.00),
(2000, 1032, 252, 1, 10.00),
(2001, 1033, 200, 1, 9.00),
(2002, 1034, 191, 1, 13.90),
(2003, 1034, 145, 1, 2.00),
(2004, 1034, 115, 1, 7.00),
(2005, 1035, 81, 1, 9.90),
(2006, 1035, 114, 1, 7.00),
(2007, 1036, 90, 2, 2.00),
(2008, 1036, 2, 4, 1.00),
(2009, 1036, 64, 1, 5.00),
(2010, 1036, 240, 1, 18.90),
(2011, 1036, 64, 1, 5.00),
(2012, 1037, 77, 1, 3.50),
(2013, 1037, 1, 1, 18.90),
(2014, 1037, 64, 1, 5.00),
(2015, 1037, 238, 1, 10.00),
(2016, 1038, 221, 1, 18.90),
(2017, 1039, 188, 1, 22.00),
(2018, 1039, 185, 1, 7.00),
(2019, 1039, 96, 1, 4.00),
(2020, 1039, 189, 1, 16.00),
(2021, 1039, 145, 1, 2.00),
(2022, 1039, 68, 1, 11.90),
(2023, 1039, 221, 1, 18.90),
(2024, 1039, 1, 1, 18.90),
(2025, 1040, 4, 2, 3.50),
(2026, 1040, 2, 6, 1.00),
(2027, 1041, 2, 3, 1.00),
(2028, 1041, 96, 1, 4.00),
(2029, 1042, 59, 2, 12.00),
(2030, 1043, 235, 1, 13.50),
(2031, 1043, 61, 1, 4.50),
(2032, 1043, 2, 2, 1.00),
(2033, 1044, 58, 1, 6.00),
(2034, 1044, 182, 1, 7.00),
(2035, 1044, 56, 1, 3.50),
(2036, 1044, 229, 1, 14.90),
(2037, 1045, 71, 1, 4.50),
(2038, 1046, 14, 1, 15.00),
(2039, 1046, 2, 6, 1.00),
(2040, 1046, 90, 1, 2.00),
(2041, 1046, 86, 1, 4.00),
(2042, 1046, 1, 1, 18.90),
(2043, 1046, 62, 1, 4.50),
(2044, 1047, 56, 1, 3.50),
(2045, 1048, 240, 1, 18.90),
(2046, 1049, 64, 1, 5.00),
(2047, 1049, 2, 2, 1.00),
(2048, 1050, 201, 1, 7.00),
(2049, 1050, 79, 1, 3.50),
(2050, 1051, 189, 1, 16.00),
(2051, 1051, 145, 1, 2.00),
(2052, 1051, 116, 1, 7.00),
(2053, 1052, 185, 1, 7.00),
(2054, 1052, 64, 1, 5.00),
(2055, 1052, 185, 1, 7.00),
(2056, 1052, 192, 1, 13.90),
(2057, 1052, 192, 1, 13.90),
(2058, 1053, 68, 1, 11.90),
(2059, 1053, 2, 2, 1.00),
(2060, 1053, 90, 1, 2.00),
(2061, 1053, 240, 1, 18.90),
(2062, 1053, 249, 1, 7.00),
(2063, 1054, 240, 1, 18.90),
(2064, 1055, 85, 2, 9.00),
(2065, 1056, 185, 3, 7.00),
(2066, 1056, 96, 3, 4.00),
(2067, 1056, 201, 1, 7.00),
(2068, 1056, 115, 1, 7.00),
(2069, 1057, 240, 1, 18.90),
(2070, 1058, 240, 1, 18.90),
(2071, 1059, 240, 1, 18.90);
INSERT INTO `itens_venda` (`id`, `venda_id`, `produto_id`, `quantidade`, `valor_unitario`) VALUES
(2072, 1060, 243, 1, 9.00),
(2073, 1060, 6, 1, 3.00),
(2074, 1060, 90, 1, 2.00),
(2075, 1061, 252, 1, 10.00),
(2076, 1061, 90, 1, 2.00),
(2077, 1062, 4, 1, 3.50),
(2078, 1062, 2, 3, 1.00),
(2079, 1063, 2, 3, 1.00),
(2080, 1064, 201, 1, 7.00),
(2081, 1064, 90, 1, 2.00),
(2082, 1065, 58, 1, 6.00),
(2083, 1065, 90, 1, 2.00),
(2084, 1066, 94, 1, 4.50),
(2085, 1066, 200, 1, 9.00),
(2086, 1067, 86, 1, 4.00),
(2087, 1068, 123, 1, 6.00),
(2088, 1068, 177, 1, 7.00),
(2089, 1069, 198, 1, 14.00),
(2090, 1069, 61, 1, 4.50),
(2091, 1070, 240, 1, 18.90),
(2092, 1070, 199, 1, 9.00),
(2093, 1070, 75, 1, 6.00),
(2094, 1070, 63, 1, 6.00),
(2095, 1070, 63, 1, 6.00),
(2096, 1071, 63, 1, 6.00),
(2097, 1072, 68, 1, 11.90),
(2098, 1072, 257, 2, 2.00),
(2099, 1073, 2, 2, 1.00),
(2100, 1074, 240, 1, 18.90),
(2101, 1074, 10, 1, 7.90),
(2102, 1075, 2, 5, 1.00),
(2103, 1075, 158, 1, 4.00),
(2104, 1075, 90, 1, 2.00),
(2105, 1075, 123, 1, 6.00),
(2106, 1075, 94, 1, 4.50),
(2107, 1076, 198, 1, 14.00),
(2108, 1076, 80, 1, 9.90),
(2109, 1077, 62, 1, 4.50),
(2110, 1078, 240, 1, 18.90),
(2111, 1079, 1, 1, 18.90),
(2112, 1079, 45, 1, 5.00),
(2113, 1080, 240, 1, 18.90),
(2114, 1080, 64, 1, 5.00),
(2115, 1080, 10, 1, 7.90),
(2116, 1081, 240, 1, 18.90),
(2117, 1081, 10, 1, 7.90),
(2118, 1081, 122, 1, 10.00),
(2119, 1082, 229, 1, 14.90),
(2120, 1083, 238, 1, 10.00),
(2121, 1084, 221, 1, 18.90),
(2122, 1084, 91, 1, 12.90),
(2123, 1085, 238, 1, 10.00),
(2124, 1085, 2, 7, 1.00),
(2125, 1086, 90, 3, 2.00),
(2126, 1086, 185, 1, 7.00),
(2127, 1086, 10, 1, 7.90),
(2128, 1087, 10, 1, 7.90),
(2129, 1088, 184, 1, 7.00),
(2130, 1088, 61, 1, 4.50),
(2131, 1089, 240, 1, 18.90),
(2132, 1089, 3, 1, 9.90),
(2133, 1090, 61, 1, 4.50),
(2134, 1091, 240, 1, 18.90),
(2135, 1091, 64, 1, 5.00),
(2136, 1091, 68, 1, 11.90),
(2137, 1092, 258, 1, 8.90),
(2138, 1093, 240, 1, 18.90),
(2139, 1093, 64, 1, 5.00),
(2140, 1094, 4, 1, 3.50),
(2141, 1095, 252, 1, 10.00),
(2142, 1095, 26, 1, 10.00),
(2143, 1095, 90, 1, 2.00),
(2144, 1096, 2, 5, 1.00),
(2145, 1096, 90, 1, 2.00),
(2146, 1097, 249, 1, 7.00),
(2147, 1097, 26, 1, 10.00),
(2148, 1097, 240, 1, 18.90),
(2149, 1097, 199, 1, 9.00),
(2150, 1097, 64, 1, 5.00),
(2151, 1098, 190, 1, 13.90),
(2152, 1098, 249, 1, 7.00),
(2153, 1098, 58, 1, 6.00),
(2154, 1098, 68, 1, 11.90),
(2155, 1099, 2, 4, 1.00),
(2156, 1099, 90, 1, 2.00),
(2157, 1100, 124, 1, 6.00),
(2158, 1100, 6, 1, 3.00),
(2159, 1100, 158, 1, 4.00),
(2160, 1101, 200, 1, 9.00),
(2161, 1101, 115, 1, 7.00),
(2162, 1102, 155, 1, 4.00),
(2163, 1103, 6, 1, 3.00),
(2164, 1104, 252, 1, 10.00),
(2165, 1104, 26, 1, 10.00),
(2166, 1105, 252, 1, 10.00),
(2167, 1106, 200, 1, 9.00),
(2168, 1106, 74, 1, 3.00),
(2169, 1107, 90, 1, 2.00),
(2170, 1107, 259, 1, 15.00),
(2171, 1108, 186, 1, 7.00),
(2172, 1109, 4, 1, 3.50),
(2173, 1110, 2, 3, 1.00),
(2174, 1111, 260, 1, 20.00),
(2175, 1111, 234, 1, 12.50),
(2176, 1112, 260, 1, 20.00),
(2177, 1112, 72, 1, 5.00),
(2178, 1112, 122, 1, 10.00),
(2179, 1113, 260, 1, 20.00),
(2180, 1113, 68, 1, 11.90),
(2181, 1114, 260, 1, 20.00),
(2182, 1114, 72, 1, 5.00),
(2183, 1115, 4, 1, 3.50),
(2184, 1116, 2, 7, 1.00),
(2185, 1116, 238, 1, 10.00),
(2186, 1117, 122, 1, 10.00),
(2187, 1117, 259, 1, 15.00),
(2188, 1118, 194, 1, 10.00),
(2189, 1118, 94, 1, 4.50),
(2190, 1118, 260, 2, 20.00),
(2191, 1118, 162, 3, 7.90),
(2192, 1118, 64, 1, 5.00),
(2193, 1118, 68, 1, 11.90),
(2194, 1118, 257, 2, 2.00),
(2195, 1118, 58, 1, 6.00),
(2196, 1118, 237, 1, 11.00),
(2197, 1119, 198, 1, 14.00),
(2198, 1119, 26, 1, 10.00),
(2199, 1119, 17, 1, 7.00),
(2200, 1119, 260, 1, 20.00),
(2201, 1120, 92, 1, 12.90),
(2202, 1120, 185, 1, 7.00),
(2203, 1121, 2, 5, 1.00),
(2204, 1121, 260, 1, 20.00),
(2205, 1121, 65, 1, 5.00),
(2206, 1121, 192, 1, 13.90),
(2207, 1122, 198, 1, 14.00),
(2208, 1122, 64, 1, 5.00),
(2209, 1123, 259, 1, 15.00),
(2210, 1123, 162, 1, 7.90),
(2211, 1124, 64, 1, 5.00),
(2212, 1125, 193, 1, 12.00),
(2213, 1126, 63, 1, 6.00),
(2214, 1127, 201, 1, 7.00),
(2215, 1128, 202, 2, 4.00),
(2216, 1129, 96, 1, 4.00),
(2217, 1129, 2, 3, 1.00),
(2218, 1129, 6, 1, 3.00),
(2219, 1130, 185, 1, 7.00),
(2220, 1130, 61, 1, 4.50),
(2221, 1131, 2, 5, 1.00),
(2222, 1131, 58, 1, 6.00),
(2223, 1131, 190, 1, 13.90),
(2224, 1132, 1, 1, 18.90),
(2225, 1132, 86, 1, 4.00),
(2226, 1132, 116, 1, 7.00),
(2227, 1132, 90, 1, 2.00),
(2228, 1133, 3, 1, 9.90),
(2229, 1134, 238, 1, 10.00),
(2230, 1134, 185, 2, 7.00),
(2231, 1135, 42, 1, 14.00),
(2232, 1136, 1, 1, 18.90),
(2233, 1137, 170, 1, 35.00),
(2234, 1138, 178, 1, 8.00),
(2235, 1138, 4, 1, 3.50),
(2236, 1138, 2, 3, 1.00),
(2237, 1139, 240, 1, 18.90),
(2238, 1139, 164, 2, 2.00),
(2239, 1140, 58, 1, 6.00),
(2240, 1141, 1, 1, 18.90),
(2241, 1142, 192, 1, 13.90),
(2242, 1142, 10, 1, 7.90),
(2243, 1143, 240, 1, 18.90),
(2244, 1144, 208, 1, 9.90),
(2245, 1144, 62, 1, 4.50),
(2246, 1145, 221, 1, 18.90),
(2247, 1146, 238, 1, 10.00),
(2248, 1147, 92, 1, 12.90),
(2249, 1147, 169, 1, 3.00),
(2250, 1147, 26, 1, 10.00),
(2251, 1148, 202, 1, 4.00),
(2252, 1148, 68, 1, 11.90),
(2253, 1149, 240, 1, 18.90),
(2254, 1150, 240, 2, 18.90),
(2255, 1150, 68, 1, 11.90),
(2256, 1150, 184, 1, 7.00),
(2257, 1150, 249, 1, 7.00),
(2258, 1151, 240, 1, 18.90),
(2259, 1151, 223, 2, 2.50),
(2260, 1152, 214, 1, 14.90),
(2261, 1152, 142, 1, 2.00),
(2262, 1152, 58, 1, 6.00),
(2263, 1152, 192, 1, 13.90),
(2264, 1152, 65, 1, 5.00),
(2265, 1152, 10, 1, 7.90),
(2266, 1152, 240, 1, 18.90),
(2267, 1152, 157, 7, 0.50),
(2268, 1152, 156, 10, 0.20),
(2269, 1153, 2, 7, 1.00),
(2270, 1153, 64, 1, 5.00),
(2271, 1154, 180, 1, 7.00),
(2272, 1154, 61, 1, 4.50),
(2273, 1154, 200, 1, 9.00),
(2274, 1154, 94, 1, 4.50),
(2275, 1155, 201, 1, 7.00),
(2276, 1155, 249, 1, 7.00),
(2277, 1155, 65, 1, 5.00),
(2278, 1155, 63, 1, 6.00),
(2279, 1155, 123, 1, 6.00),
(2280, 1155, 186, 1, 7.00),
(2281, 1155, 192, 1, 13.90),
(2282, 1155, 240, 1, 18.90),
(2283, 1155, 10, 1, 7.90),
(2284, 1156, 240, 1, 18.90),
(2285, 1156, 10, 1, 7.90),
(2286, 1156, 64, 1, 5.00),
(2287, 1156, 64, 1, 5.00),
(2288, 1157, 238, 1, 10.00),
(2289, 1158, 240, 1, 18.90),
(2290, 1158, 122, 1, 10.00),
(2291, 1159, 2, 3, 1.00),
(2292, 1159, 4, 1, 3.50),
(2293, 1159, 162, 1, 7.90),
(2294, 1159, 1, 1, 18.90),
(2295, 1159, 164, 3, 2.00),
(2296, 1159, 261, 1, 3.00),
(2297, 1160, 254, 1, 9.90),
(2298, 1161, 221, 1, 18.90),
(2299, 1161, 65, 1, 5.00),
(2300, 1162, 2, 3, 1.00),
(2301, 1162, 90, 1, 2.00),
(2302, 1162, 240, 1, 18.90),
(2303, 1162, 4, 1, 3.50),
(2304, 1163, 185, 1, 7.00),
(2305, 1164, 240, 1, 18.90),
(2306, 1165, 198, 1, 14.00),
(2307, 1165, 240, 1, 18.90),
(2308, 1166, 96, 1, 4.00),
(2309, 1166, 2, 2, 1.00),
(2310, 1167, 191, 1, 13.90),
(2311, 1167, 145, 1, 2.00),
(2312, 1168, 187, 1, 7.00),
(2313, 1168, 17, 1, 7.00),
(2314, 1168, 63, 1, 6.00),
(2315, 1169, 240, 1, 18.90),
(2316, 1169, 65, 1, 5.00),
(2317, 1169, 58, 1, 6.00),
(2318, 1169, 63, 1, 6.00),
(2319, 1169, 257, 2, 2.50),
(2320, 1170, 240, 1, 18.90),
(2321, 1170, 64, 1, 5.00),
(2322, 1170, 237, 1, 11.00),
(2323, 1171, 225, 1, 7.00),
(2324, 1171, 61, 1, 4.50),
(2325, 1171, 240, 1, 18.90),
(2326, 1171, 62, 1, 4.50),
(2327, 1171, 62, 1, 4.50),
(2328, 1171, 164, 1, 2.00),
(2329, 1171, 2, 5, 1.00),
(2330, 1171, 61, 1, 4.50),
(2331, 1171, 240, 1, 18.90),
(2332, 1171, 62, 1, 4.50),
(2333, 1171, 61, 1, 4.50),
(2334, 1172, 262, 1, 6.00),
(2335, 1173, 2, 6, 1.00),
(2336, 1173, 90, 1, 2.00),
(2337, 1173, 180, 1, 7.00),
(2338, 1173, 254, 1, 9.90),
(2339, 1173, 90, 2, 2.00),
(2340, 1173, 3, 1, 9.90),
(2341, 1173, 2, 6, 1.00),
(2342, 1173, 90, 1, 2.00),
(2343, 1173, 179, 1, 14.90),
(2344, 1174, 1, 1, 18.90),
(2345, 1174, 116, 1, 7.00),
(2346, 1175, 90, 1, 2.00),
(2347, 1175, 68, 1, 11.90),
(2348, 1175, 2, 6, 1.00),
(2349, 1175, 90, 1, 2.00),
(2350, 1175, 1, 1, 18.90),
(2351, 1176, 162, 1, 7.90),
(2352, 1177, 106, 1, 7.00),
(2353, 1178, 3, 1, 9.90),
(2354, 1179, 1, 1, 18.90),
(2355, 1179, 64, 1, 5.00),
(2356, 1180, 240, 1, 18.90),
(2357, 1180, 64, 1, 5.00),
(2358, 1181, 92, 1, 12.90),
(2359, 1182, 261, 1, 3.00),
(2360, 1182, 4, 1, 3.50),
(2361, 1182, 181, 1, 7.00),
(2362, 1183, 202, 2, 4.00),
(2363, 1183, 99, 1, 8.50),
(2364, 1183, 182, 1, 7.00),
(2365, 1184, 187, 1, 7.00),
(2366, 1184, 61, 1, 4.50),
(2367, 1185, 214, 1, 14.90),
(2368, 1185, 142, 1, 2.00),
(2369, 1185, 116, 1, 7.00),
(2370, 1185, 263, 1, 6.00),
(2371, 1186, 2, 5, 1.00),
(2372, 1186, 3, 1, 9.90),
(2373, 1187, 64, 1, 5.00),
(2374, 1188, 6, 1, 3.00),
(2375, 1189, 185, 1, 7.00),
(2376, 1190, 192, 2, 13.90),
(2377, 1190, 63, 1, 6.00),
(2378, 1191, 223, 2, 2.50),
(2379, 1192, 179, 1, 14.90),
(2380, 1192, 63, 1, 6.00),
(2381, 1192, 14, 1, 15.00),
(2382, 1193, 90, 2, 2.00),
(2383, 1193, 2, 6, 1.00),
(2384, 1193, 90, 1, 2.00),
(2385, 1193, 1, 1, 18.90),
(2386, 1193, 116, 1, 7.00),
(2387, 1194, 200, 1, 9.00),
(2388, 1194, 123, 1, 6.00),
(2389, 1194, 4, 1, 3.50),
(2390, 1195, 162, 1, 7.90),
(2391, 1196, 229, 1, 14.90),
(2392, 1197, 243, 1, 9.00),
(2393, 1198, 237, 1, 11.00),
(2394, 1198, 221, 1, 18.90),
(2395, 1199, 198, 1, 14.00),
(2396, 1199, 123, 1, 6.00),
(2397, 1199, 64, 1, 5.00),
(2398, 1200, 61, 1, 4.50),
(2399, 1201, 227, 1, 14.90),
(2400, 1201, 45, 1, 5.00),
(2401, 1202, 249, 1, 7.00),
(2402, 1202, 221, 1, 18.90),
(2403, 1203, 3, 2, 9.90),
(2404, 1203, 1, 1, 18.90),
(2405, 1204, 1, 1, 18.90),
(2406, 1204, 64, 1, 5.00),
(2407, 1205, 64, 1, 5.00),
(2408, 1205, 227, 1, 14.90),
(2409, 1206, 2, 4, 1.00),
(2410, 1206, 74, 1, 3.00),
(2411, 1206, 243, 1, 9.00),
(2412, 1207, 4, 1, 3.50),
(2413, 1207, 2, 3, 1.00),
(2414, 1207, 257, 1, 2.50),
(2415, 1207, 4, 1, 3.50),
(2416, 1208, 90, 2, 2.00),
(2417, 1208, 64, 1, 5.00),
(2418, 1209, 56, 1, 3.50),
(2419, 1209, 263, 1, 6.00),
(2420, 1209, 185, 1, 7.00),
(2421, 1210, 1, 1, 18.90),
(2422, 1211, 2, 5, 1.00),
(2423, 1212, 161, 1, 3.00),
(2424, 1212, 6, 2, 3.00),
(2425, 1213, 63, 1, 6.00),
(2426, 1213, 10, 1, 7.90),
(2427, 1214, 155, 1, 4.00),
(2428, 1215, 240, 1, 18.90),
(2429, 1215, 237, 1, 11.00),
(2430, 1216, 186, 1, 7.00),
(2431, 1216, 61, 1, 4.50),
(2432, 1216, 61, 1, 4.50),
(2433, 1216, 68, 1, 11.90),
(2434, 1217, 90, 1, 2.00),
(2435, 1217, 2, 4, 1.00),
(2436, 1218, 240, 1, 18.90),
(2437, 1218, 240, 1, 18.90),
(2438, 1218, 63, 2, 6.00),
(2439, 1219, 194, 1, 10.00),
(2440, 1219, 78, 1, 3.50),
(2441, 1219, 163, 1, 4.00),
(2442, 1219, 10, 1, 7.90),
(2443, 1219, 79, 1, 3.50),
(2444, 1219, 168, 1, 5.00),
(2445, 1220, 240, 2, 18.90),
(2446, 1221, 240, 1, 18.90),
(2447, 1221, 61, 1, 4.50),
(2448, 1221, 200, 1, 9.00),
(2449, 1221, 90, 1, 2.00),
(2450, 1222, 236, 1, 12.50),
(2451, 1222, 263, 1, 6.00),
(2452, 1222, 28, 1, 10.00),
(2453, 1222, 240, 1, 18.90),
(2454, 1222, 10, 1, 7.90),
(2455, 1222, 263, 1, 6.00),
(2456, 1222, 236, 1, 12.50),
(2457, 1223, 261, 1, 3.00),
(2458, 1224, 261, 1, 3.00),
(2459, 1225, 240, 1, 18.90),
(2460, 1225, 4, 1, 3.50),
(2461, 1225, 168, 1, 5.00),
(2462, 1225, 237, 1, 11.00),
(2463, 1226, 240, 1, 18.90),
(2464, 1227, 61, 1, 4.50),
(2465, 1228, 240, 1, 18.90),
(2466, 1229, 214, 1, 14.90),
(2467, 1229, 145, 1, 2.00),
(2468, 1230, 2, 6, 1.00),
(2469, 1230, 86, 1, 4.00),
(2470, 1231, 64, 1, 5.00),
(2471, 1231, 182, 1, 7.00),
(2472, 1232, 4, 1, 3.50),
(2473, 1232, 178, 1, 8.00),
(2474, 1232, 182, 1, 7.00),
(2475, 1233, 122, 1, 10.00),
(2476, 1234, 243, 1, 9.00),
(2477, 1235, 189, 1, 16.00),
(2478, 1235, 145, 1, 2.00),
(2479, 1235, 147, 1, 1.00),
(2480, 1235, 115, 1, 7.00),
(2481, 1236, 240, 1, 18.90),
(2482, 1236, 68, 1, 11.90),
(2483, 1237, 184, 2, 7.00),
(2484, 1237, 6, 1, 3.00),
(2485, 1238, 65, 2, 5.00),
(2486, 1238, 85, 2, 9.00),
(2487, 1239, 192, 1, 14.90),
(2488, 1239, 145, 1, 2.00),
(2489, 1240, 180, 2, 7.00),
(2490, 1240, 58, 1, 6.00),
(2491, 1241, 64, 1, 5.00),
(2492, 1241, 240, 1, 18.90),
(2493, 1241, 243, 1, 9.00),
(2494, 1242, 58, 1, 6.00),
(2495, 1243, 180, 1, 7.00),
(2496, 1243, 4, 1, 3.50),
(2497, 1244, 240, 1, 18.90),
(2498, 1244, 65, 1, 5.00),
(2499, 1244, 182, 1, 7.00),
(2500, 1244, 74, 1, 3.00),
(2501, 1244, 249, 1, 7.00),
(2502, 1245, 4, 1, 3.50),
(2503, 1245, 257, 1, 2.50),
(2504, 1245, 197, 1, 7.00),
(2505, 1246, 240, 1, 18.90),
(2506, 1246, 3, 1, 9.90),
(2507, 1246, 155, 1, 4.00),
(2508, 1247, 240, 1, 18.90),
(2509, 1247, 214, 1, 14.90),
(2510, 1247, 142, 1, 2.00),
(2511, 1247, 64, 1, 5.00),
(2512, 1247, 263, 1, 6.00),
(2513, 1247, 162, 2, 7.90),
(2514, 1248, 201, 1, 7.00),
(2515, 1249, 2, 6, 1.00),
(2516, 1249, 90, 1, 2.00),
(2517, 1250, 264, 1, 25.00),
(2518, 1251, 2, 3, 1.00),
(2519, 1251, 4, 1, 3.50),
(2520, 1251, 257, 1, 2.50),
(2521, 1251, 237, 1, 11.00),
(2522, 1251, 4, 1, 3.50),
(2523, 1252, 4, 1, 3.50),
(2524, 1252, 185, 1, 7.00),
(2525, 1252, 164, 2, 2.00),
(2526, 1253, 90, 1, 2.00),
(2527, 1253, 90, 1, 2.00),
(2528, 1253, 2, 4, 1.00),
(2529, 1254, 264, 1, 25.00),
(2530, 1255, 185, 1, 7.00),
(2531, 1255, 249, 1, 7.00),
(2532, 1255, 260, 1, 20.00),
(2533, 1255, 63, 1, 6.00),
(2534, 1256, 2, 4, 1.00),
(2535, 1256, 74, 1, 3.00),
(2536, 1257, 2, 4, 1.00),
(2537, 1258, 63, 1, 6.00),
(2538, 1259, 260, 1, 20.00),
(2539, 1260, 260, 1, 20.00),
(2540, 1261, 264, 1, 25.00),
(2541, 1262, 165, 15, 1.00),
(2542, 1262, 6, 3, 3.00),
(2543, 1262, 161, 2, 3.00),
(2544, 1262, 260, 1, 20.00),
(2545, 1263, 62, 1, 4.50),
(2546, 1264, 1, 1, 18.90),
(2547, 1264, 65, 1, 5.00),
(2548, 1264, 243, 1, 9.00),
(2549, 1265, 260, 1, 20.00),
(2550, 1265, 57, 1, 3.00),
(2551, 1266, 260, 1, 20.00),
(2552, 1266, 64, 1, 5.00),
(2553, 1267, 32, 1, 100.00),
(2554, 1267, 35, 1, 50.00),
(2555, 1267, 31, 1, 10.00),
(2556, 1267, 30, 6, 1.00),
(2557, 1268, 227, 1, 14.90),
(2558, 1268, 122, 1, 10.00),
(2559, 1268, 71, 1, 4.50),
(2560, 1269, 228, 1, 14.90),
(2561, 1269, 4, 1, 3.50),
(2562, 1270, 168, 1, 5.00),
(2563, 1270, 56, 1, 3.50),
(2564, 1272, 2, 1, 1.00),
(2565, 1273, 2, 1, 1.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `listas_compras`
--

CREATE TABLE `listas_compras` (
  `id` int NOT NULL,
  `nome` varchar(200) NOT NULL,
  `descricao` text,
  `status` enum('rascunho','enviada','em_cotacao','finalizada','cancelada') DEFAULT 'rascunho',
  `prioridade` enum('baixa','media','alta','urgente') DEFAULT 'media',
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_envio` timestamp NULL DEFAULT NULL,
  `data_prazo` date DEFAULT NULL,
  `criado_por` int NOT NULL,
  `valor_estimado` decimal(10,2) DEFAULT '0.00',
  `valor_final` decimal(10,2) DEFAULT '0.00',
  `observacoes` text,
  `data_ultima_atualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Tabela principal para armazenar listas de compras do sistema';

--
-- Despejando dados para a tabela `listas_compras`
--

INSERT INTO `listas_compras` (`id`, `nome`, `descricao`, `status`, `prioridade`, `data_criacao`, `data_envio`, `data_prazo`, `criado_por`, `valor_estimado`, `valor_final`, `observacoes`, `data_ultima_atualizacao`) VALUES
(1, 'Lista Exemplo - Produtos de Limpeza', 'Lista exemplo para demonstração do sistema', 'rascunho', 'media', '2025-06-07 19:43:46', NULL, NULL, 1, 569.00, 0.00, NULL, '2025-06-07 19:43:46');

--
-- Acionadores `listas_compras`
--
DELIMITER $$
CREATE TRIGGER `tr_historico_lista_update` AFTER UPDATE ON `listas_compras` FOR EACH ROW BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO historico_listas_compras (lista_id, acao, descricao, usuario_id)
        VALUES (NEW.id, 'status_alterado', 
                CONCAT('Status alterado de "', OLD.status, '" para "', NEW.status, '"'), 
                NEW.criado_por);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs`
--

CREATE TABLE `logs` (
  `id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `acao` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `descricao` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `ip` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `data_hora` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_sistema`
--

CREATE TABLE `logs_sistema` (
  `id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `usuario_nome` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `acao` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `descricao` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `tipo` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT 'info',
  `ip` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `data_hora` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

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
(12, 1, 'Josuel Menezes', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-03-04 20:59:14'),
(13, 1, 'Josuel Menezes', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-03-07 02:51:53'),
(14, 1, 'Josuel Menezes', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-03-07 02:53:06'),
(15, 1, 'Josuel Menezes', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-03-07 02:54:23'),
(16, 1, 'Josuel Menezes', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-03-07 02:54:58'),
(17, 1, 'Josuel Menezes', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-03-07 03:17:03'),
(18, 1, 'Josuel Menezes', 'Atualização de Logo', 'Logo da empresa atualizada: uploads/logo_1741317449_DOMARIA.png', 'info', '189.6.15.97', '2025-03-07 03:17:29'),
(19, 1, 'Josuel Menezes', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-03-07 03:17:29'),
(20, 1, 'Josuel Menezes', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '189.6.15.97', '2025-03-07 03:17:59'),
(21, 1, 'Josuel Menezes', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, logo_url, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '::1', '2025-06-08 13:28:48'),
(22, 1, 'Josuel Menezes', 'Atualização de Logo', 'Logo da empresa atualizada: uploads/logo_1749389360_logo.png', 'info', '::1', '2025-06-08 13:29:20'),
(23, 1, 'Josuel Menezes', 'Atualização de Configurações', 'Configurações atualizadas: nome_empresa, telefone_empresa, email_empresa, endereco_empresa, cnpj_empresa, site_empresa, whatsapp_empresa, cor_primaria, cor_secundaria, cor_botoes, cor_texto, tema, mensagem_comprovante, formato_comprovante, incluir_logo_comprovante, itens_por_pagina, moeda, formato_data, timezone, backup_automatico, versao_sistema, manutencao, mensagem_manutencao', 'info', '::1', '2025-06-08 13:29:20');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id` int NOT NULL,
  `venda_id` int NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_pagamento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observacao` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

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
(32, 72, 22.00, '2025-03-06 00:00:00', ''),
(33, 99, 9.90, '2025-03-07 00:00:00', ''),
(34, 35, 220.00, '2025-03-07 00:00:00', ''),
(35, 58, 5.00, '2025-03-07 00:00:00', ''),
(36, 59, 241.90, '2025-03-07 00:00:00', ''),
(37, 88, 3.10, '2025-03-07 00:00:00', ''),
(38, 53, 7.90, '2025-03-07 00:00:00', ''),
(39, 54, 120.30, '2025-03-07 00:00:00', ''),
(40, 57, 23.90, '2025-03-07 00:00:00', ''),
(41, 63, 21.90, '2025-03-07 00:00:00', ''),
(42, 93, 27.00, '2025-03-07 00:00:00', ''),
(43, 101, 41.80, '2025-03-07 00:00:00', ''),
(44, 110, 15.50, '2025-03-07 00:00:00', ''),
(45, 112, 10.00, '2025-03-07 00:00:00', ''),
(46, 117, 5.50, '2025-03-07 00:00:00', ''),
(47, 68, 4.50, '2025-03-07 00:00:00', 'pix'),
(48, 82, 23.90, '2025-03-07 00:00:00', 'pix'),
(49, 95, 13.00, '2025-03-07 00:00:00', 'pix'),
(50, 107, 50.90, '2025-03-07 00:00:00', 'pix'),
(51, 126, 4.50, '2025-03-07 00:00:00', 'pix'),
(52, 41, 332.90, '2025-03-10 00:00:00', 'credito'),
(53, 114, 18.90, '2025-03-10 00:00:00', 'credito'),
(54, 127, 3.00, '2025-03-10 00:00:00', 'dinheiro'),
(55, 140, 4.90, '2025-03-10 00:00:00', ''),
(56, 89, 8.50, '2025-03-10 00:00:00', ''),
(57, 111, 1.50, '2025-03-10 00:00:00', ''),
(58, 48, 230.00, '2025-03-10 00:00:00', ''),
(59, 37, 650.00, '2025-03-11 00:00:00', 'pix'),
(60, 52, 7.00, '2025-03-11 00:00:00', 'pix'),
(61, 61, 10.00, '2025-03-11 00:00:00', 'pix'),
(62, 80, 59.30, '2025-03-11 00:00:00', 'pix'),
(63, 33, 385.00, '2025-03-11 00:00:00', ''),
(64, 60, 11.50, '2025-03-11 00:00:00', ''),
(65, 91, 3.50, '2025-03-11 00:00:00', ''),
(66, 91, 27.50, '2025-03-11 00:00:00', ''),
(67, 102, 3.40, '2025-03-11 00:00:00', ''),
(68, 42, 75.80, '2025-03-12 00:00:00', ''),
(69, 208, 7.00, '2025-03-12 00:00:00', ''),
(70, 35, 0.40, '2025-03-12 00:00:00', 'debito'),
(71, 165, 25.90, '2025-03-12 00:00:00', 'debito'),
(72, 216, 8.00, '2025-03-12 00:00:00', 'debito'),
(73, 182, 3.00, '2025-03-13 00:00:00', 'dinheiro'),
(74, 122, 22.00, '2025-03-13 00:00:00', 'DINHEIRO'),
(75, 123, 32.90, '2025-03-13 00:00:00', 'DINHEIRO'),
(76, 195, 9.90, '2025-03-13 00:00:00', 'DINHEIRO'),
(77, 229, 4.50, '2025-03-13 00:00:00', 'PIX'),
(78, 230, 16.00, '2025-03-13 00:00:00', 'PIX'),
(79, 231, 5.50, '2025-03-13 00:00:00', 'PIX'),
(80, 233, 6.00, '2025-03-13 00:00:00', 'pix'),
(81, 90, 18.90, '2025-03-13 00:00:00', ''),
(82, 226, 6.00, '2025-03-13 00:00:00', ''),
(83, 73, 23.40, '2025-03-13 00:00:00', ''),
(84, 40, 214.90, '2025-03-14 00:00:00', ''),
(85, 76, 23.80, '2025-03-14 00:00:00', ''),
(86, 92, 14.00, '2025-03-14 00:00:00', ''),
(87, 116, 10.00, '2025-03-14 00:00:00', ''),
(88, 156, 16.00, '2025-03-14 00:00:00', ''),
(89, 237, 28.80, '2025-03-14 00:00:00', ''),
(90, 97, 45.90, '2025-03-14 00:00:00', ''),
(91, 131, 14.10, '2025-03-14 00:00:00', ''),
(92, 83, 6.00, '2025-03-14 00:00:00', ''),
(93, 132, 6.00, '2025-03-14 00:00:00', ''),
(94, 135, 2.00, '2025-03-14 00:00:00', ''),
(95, 155, 16.90, '2025-03-14 00:00:00', ''),
(96, 164, 25.00, '2025-03-14 00:00:00', ''),
(97, 167, 2.00, '2025-03-14 00:00:00', ''),
(98, 176, 25.00, '2025-03-14 00:00:00', ''),
(99, 177, 47.80, '2025-03-14 00:00:00', ''),
(100, 187, 9.00, '2025-03-14 00:00:00', ''),
(101, 205, 23.90, '2025-03-14 00:00:00', ''),
(102, 220, 10.00, '2025-03-14 00:00:00', ''),
(103, 225, 10.00, '2025-03-14 00:00:00', ''),
(104, 270, 5.00, '2025-03-14 00:00:00', ''),
(105, 121, 183.41, '2025-03-14 00:00:00', ''),
(106, 88, 32.70, '2025-03-17 00:00:00', ''),
(107, 120, 17.30, '2025-03-17 00:00:00', ''),
(108, 302, 9.00, '2025-03-17 00:00:00', ''),
(109, 38, 41.50, '2025-03-17 00:00:00', 'PIX'),
(110, 64, 3.50, '2025-03-17 00:00:00', 'PIX'),
(111, 65, 3.50, '2025-03-17 00:00:00', 'PIX'),
(112, 104, 12.00, '2025-03-17 00:00:00', 'PIX'),
(113, 152, 4.00, '2025-03-17 00:00:00', 'PIX'),
(114, 312, 4.00, '2025-03-17 00:00:00', 'PIX'),
(115, 25, 31.00, '2025-03-18 00:00:00', 'credito'),
(116, 47, 204.50, '2025-03-19 00:00:00', 'salario'),
(117, 32, 100.00, '2025-03-19 00:00:00', 'PIX'),
(118, 306, 3.00, '2025-03-19 00:00:00', ''),
(119, 315, 4.50, '2025-03-19 00:00:00', ''),
(120, 318, 12.50, '2025-03-19 00:00:00', ''),
(121, 330, 4.50, '2025-03-19 00:00:00', ''),
(122, 366, 8.50, '2025-03-19 00:00:00', ''),
(123, 39, 126.70, '2025-03-19 00:00:00', 'DEBITO'),
(124, 75, 30.00, '2025-03-19 00:00:00', 'DEBITO'),
(125, 87, 11.00, '2025-03-19 00:00:00', 'DEBITO'),
(126, 190, 4.00, '2025-03-19 00:00:00', 'DEBITO'),
(127, 369, 23.80, '2025-03-19 00:00:00', 'DEBITO'),
(128, 24, 18.90, '2025-03-20 00:00:00', 'debito'),
(129, 191, 100.00, '2025-03-20 00:00:00', 'DINHEIRO'),
(130, 257, 3.00, '2025-03-20 00:00:00', 'PIX'),
(131, 290, 4.00, '2025-03-20 00:00:00', 'PIX'),
(132, 387, 5.00, '2025-03-20 00:00:00', 'PIX'),
(133, 388, 3.00, '2025-03-20 00:00:00', 'PIX'),
(134, 385, 16.00, '2025-03-20 00:00:00', ''),
(135, 44, 80.30, '2025-03-21 00:00:00', 'PIX'),
(136, 141, 17.90, '2025-03-21 00:00:00', 'PIX'),
(137, 288, 4.00, '2025-03-21 00:00:00', 'CREDITO'),
(138, 329, 7.00, '2025-03-21 00:00:00', 'CREDITO'),
(139, 342, 10.00, '2025-03-21 00:00:00', 'CREDITO'),
(140, 361, 2.00, '2025-03-21 00:00:00', 'CREDITO'),
(141, 363, 5.00, '2025-03-21 00:00:00', 'CREDITO'),
(142, 378, 4.00, '2025-03-21 00:00:00', 'CREDITO'),
(143, 409, 5.00, '2025-03-21 00:00:00', 'CREDITO'),
(144, 414, 31.40, '2025-03-21 00:00:00', 'CREDITO'),
(145, 191, 69.10, '2025-03-24 00:00:00', 'CREDITO'),
(146, 196, 4.50, '2025-03-24 00:00:00', 'CREDITO'),
(147, 382, 9.00, '2025-03-24 00:00:00', 'CREDITO'),
(148, 396, 33.90, '2025-03-24 00:00:00', 'CREDITO'),
(149, 36, 104.00, '2025-03-24 00:00:00', 'debito'),
(150, 45, 19.00, '2025-03-25 00:00:00', 'PIX'),
(151, 30, 200.00, '2025-03-25 00:00:00', 'DESCONTO MASSAGENS - TOTAL ERA 504,50 \r\n- 3 MASSAGENS 240,00 '),
(152, 67, 26.40, '2025-03-25 00:00:00', 'DESCONTO MASSAGENS - TOTAL ERA 504,50 \r\n- 3 MASSAGENS 240,00 '),
(153, 84, 13.60, '2025-03-25 00:00:00', 'DESCONTO MASSAGENS - TOTAL ERA 504,50 \r\n- 3 MASSAGENS 240,00 '),
(154, 425, 3.00, '2025-03-26 00:00:00', ''),
(155, 452, 5.00, '2025-03-26 00:00:00', ''),
(156, 473, 8.00, '2025-03-26 00:00:00', ''),
(157, 28, 9.00, '2025-03-28 00:00:00', 'pix'),
(158, 124, 28.40, '2025-03-28 00:00:00', ''),
(159, 279, 18.90, '2025-03-28 00:00:00', ''),
(160, 291, 23.50, '2025-03-28 00:00:00', ''),
(161, 341, 12.00, '2025-03-28 00:00:00', ''),
(162, 418, 18.90, '2025-03-28 00:00:00', ''),
(163, 46, 143.90, '2025-03-31 00:00:00', 'PIX'),
(164, 79, 61.30, '2025-03-31 00:00:00', 'PIX'),
(165, 98, 8.00, '2025-03-31 00:00:00', 'PIX'),
(166, 109, 11.90, '2025-03-31 00:00:00', 'PIX'),
(167, 113, 18.90, '2025-03-31 00:00:00', 'PIX'),
(168, 115, 9.90, '2025-03-31 00:00:00', 'PIX'),
(169, 150, 4.00, '2025-03-31 00:00:00', 'PIX'),
(170, 186, 9.90, '2025-03-31 00:00:00', 'PIX'),
(171, 255, 4.00, '2025-03-31 00:00:00', 'PIX'),
(172, 280, 10.00, '2025-03-31 00:00:00', 'PIX'),
(173, 389, 5.00, '2025-03-31 00:00:00', 'PIX'),
(174, 404, 3.50, '2025-03-31 00:00:00', 'PIX'),
(175, 417, 6.00, '2025-03-31 00:00:00', 'PIX'),
(176, 442, 13.90, '2025-03-31 00:00:00', 'PIX'),
(177, 445, 12.50, '2025-03-31 00:00:00', 'PIX'),
(178, 490, 18.90, '2025-03-31 00:00:00', 'PIX'),
(179, 492, 7.00, '2025-03-31 00:00:00', 'PIX'),
(180, 517, 11.00, '2025-03-31 00:00:00', 'PIX'),
(181, 525, 4.00, '2025-03-31 00:00:00', 'PIX'),
(182, 535, 18.90, '2025-03-31 00:00:00', 'PIX'),
(183, 542, 16.00, '2025-03-31 00:00:00', 'PIX'),
(184, 543, 11.00, '2025-03-31 00:00:00', 'PIX'),
(185, 94, 7.90, '2025-03-31 00:00:00', 'valor lançado errado'),
(186, 43, 60.40, '2025-04-02 00:00:00', 'pix'),
(187, 81, 38.90, '2025-04-02 00:00:00', 'pix'),
(188, 103, 0.70, '2025-04-02 00:00:00', 'pix'),
(189, 73, 35.40, '2025-04-02 00:00:00', ''),
(190, 74, 3.00, '2025-04-02 00:00:00', ''),
(191, 100, 7.00, '2025-04-02 00:00:00', ''),
(192, 108, 23.40, '2025-04-02 00:00:00', ''),
(193, 118, 4.00, '2025-04-02 00:00:00', ''),
(194, 128, 4.00, '2025-04-02 00:00:00', ''),
(195, 147, 7.00, '2025-04-02 00:00:00', ''),
(196, 178, 7.90, '2025-04-02 00:00:00', ''),
(197, 184, 8.00, '2025-04-02 00:00:00', ''),
(198, 193, 4.00, '2025-04-02 00:00:00', ''),
(199, 200, 3.00, '2025-04-02 00:00:00', ''),
(200, 244, 7.00, '2025-04-02 00:00:00', ''),
(201, 245, 23.40, '2025-04-02 00:00:00', ''),
(202, 246, 23.40, '2025-04-02 00:00:00', ''),
(203, 253, 4.00, '2025-04-02 00:00:00', ''),
(204, 278, 29.40, '2025-04-02 00:00:00', ''),
(205, 301, 23.40, '2025-04-02 00:00:00', ''),
(206, 310, 13.40, '2025-04-02 00:00:00', ''),
(207, 333, 26.80, '2025-04-02 00:00:00', ''),
(208, 346, 4.00, '2025-04-02 00:00:00', ''),
(209, 355, 18.50, '2025-04-02 00:00:00', ''),
(210, 367, 7.00, '2025-04-02 00:00:00', ''),
(211, 380, 4.00, '2025-04-02 00:00:00', ''),
(212, 390, 9.00, '2025-04-02 00:00:00', ''),
(213, 31, 230.00, '2025-04-02 00:00:00', 'pix'),
(214, 84, 2.90, '2025-04-02 00:00:00', 'pix'),
(215, 96, 11.50, '2025-04-02 00:00:00', 'pix'),
(216, 133, 15.50, '2025-04-02 00:00:00', 'pix'),
(217, 142, 23.40, '2025-04-02 00:00:00', 'pix'),
(218, 162, 5.00, '2025-04-02 00:00:00', 'pix'),
(219, 175, 19.90, '2025-04-02 00:00:00', 'pix'),
(220, 189, 13.00, '2025-04-02 00:00:00', 'pix'),
(221, 207, 30.40, '2025-04-02 00:00:00', 'pix'),
(222, 247, 29.40, '2025-04-02 00:00:00', 'pix'),
(223, 254, 6.00, '2025-04-02 00:00:00', 'pix'),
(224, 264, 6.00, '2025-04-02 00:00:00', 'pix'),
(225, 266, 18.50, '2025-04-02 00:00:00', 'pix'),
(226, 277, 11.50, '2025-04-02 00:00:00', 'pix'),
(227, 304, 13.00, '2025-04-02 00:00:00', 'pix'),
(228, 321, 5.00, '2025-04-02 00:00:00', 'pix'),
(229, 343, 12.00, '2025-04-02 00:00:00', 'pix'),
(230, 375, 13.00, '2025-04-02 00:00:00', 'pix'),
(231, 383, 7.00, '2025-04-02 00:00:00', 'pix'),
(232, 401, 11.50, '2025-04-02 00:00:00', 'pix'),
(233, 421, 10.00, '2025-04-02 00:00:00', 'pix'),
(234, 465, 25.90, '2025-04-02 00:00:00', 'pix'),
(235, 485, 9.60, '2025-04-02 00:00:00', 'pix'),
(236, 138, 2.00, '2025-04-03 00:00:00', 'TIRAR A DIFERENÇA'),
(237, 120, 24.10, '2025-04-03 00:00:00', 'PIX'),
(238, 134, 17.50, '2025-04-03 00:00:00', 'PIX'),
(239, 154, 23.90, '2025-04-03 00:00:00', 'PIX'),
(240, 159, 19.50, '2025-04-03 00:00:00', 'PIX'),
(241, 173, 12.00, '2025-04-03 00:00:00', 'PIX'),
(242, 188, 4.00, '2025-04-03 00:00:00', 'PIX'),
(243, 206, 23.90, '2025-04-03 00:00:00', 'PIX'),
(244, 211, 1.50, '2025-04-03 00:00:00', 'PIX'),
(245, 221, 14.00, '2025-04-03 00:00:00', 'PIX'),
(246, 248, 9.60, '2025-04-03 00:00:00', 'PIX'),
(247, 131, 27.70, '2025-04-04 00:00:00', 'pix'),
(248, 136, 18.90, '2025-04-04 00:00:00', 'pix'),
(249, 137, 9.80, '2025-04-04 00:00:00', 'pix'),
(250, 148, 11.90, '2025-04-04 00:00:00', 'pix'),
(251, 163, 25.80, '2025-04-04 00:00:00', 'pix'),
(252, 194, 7.90, '2025-04-04 00:00:00', 'pix'),
(253, 201, 16.00, '2025-04-04 00:00:00', 'pix'),
(254, 204, 13.90, '2025-04-04 00:00:00', 'pix'),
(255, 240, 21.00, '2025-04-04 00:00:00', 'pix'),
(256, 265, 13.90, '2025-04-04 00:00:00', 'pix'),
(257, 276, 42.30, '2025-04-04 00:00:00', 'pix'),
(258, 365, 11.90, '2025-04-04 00:00:00', 'pix'),
(259, 397, 6.00, '2025-04-04 00:00:00', 'pix'),
(260, 422, 4.50, '2025-04-04 00:00:00', 'pix'),
(261, 433, 13.90, '2025-04-04 00:00:00', 'pix'),
(262, 478, 4.50, '2025-04-04 00:00:00', 'pix'),
(263, 479, 5.00, '2025-04-04 00:00:00', 'pix'),
(264, 511, 18.90, '2025-04-04 00:00:00', 'pix'),
(265, 539, 8.00, '2025-04-04 00:00:00', 'pix'),
(266, 567, 13.90, '2025-04-04 00:00:00', 'pix'),
(267, 583, 11.90, '2025-04-04 00:00:00', 'pix'),
(268, 612, 7.90, '2025-04-04 00:00:00', 'pix'),
(269, 138, 21.90, '2025-04-04 00:00:00', 'Pagou dia 03 via cartão de debito getnet R$: 255,50'),
(270, 149, 11.90, '2025-04-04 00:00:00', 'Pagou dia 03 via cartão de debito getnet R$: 255,50'),
(271, 158, 3.50, '2025-04-04 00:00:00', 'Pagou dia 03 via cartão de debito getnet R$: 255,50'),
(272, 169, 6.00, '2025-04-04 00:00:00', 'Pagou dia 03 via cartão de debito getnet R$: 255,50'),
(273, 179, 22.40, '2025-04-04 00:00:00', 'Pagou dia 03 via cartão de debito getnet R$: 255,50'),
(274, 213, 13.90, '2025-04-04 00:00:00', 'Pagou dia 03 via cartão de debito getnet R$: 255,50'),
(275, 224, 8.00, '2025-04-04 00:00:00', 'Pagou dia 03 via cartão de debito getnet R$: 255,50'),
(276, 232, 4.00, '2025-04-04 00:00:00', 'Pagou dia 03 via cartão de debito getnet R$: 255,50'),
(277, 236, 15.00, '2025-04-04 00:00:00', 'Pagou dia 03 via cartão de debito getnet R$: 255,50'),
(278, 274, 41.80, '2025-04-04 00:00:00', 'Pagou dia 03 via cartão de debito getnet R$: 255,50'),
(279, 298, 10.00, '2025-04-04 00:00:00', 'Pagou dia 03 via cartão de debito getnet R$: 255,50'),
(280, 299, 4.00, '2025-04-04 00:00:00', 'Pagou dia 03 via cartão de debito getnet R$: 255,50'),
(281, 335, 25.90, '2025-04-04 00:00:00', 'Pagou dia 03 via cartão de debito getnet R$: 255,50'),
(282, 347, 3.50, '2025-04-04 00:00:00', 'Pagou dia 03 via cartão de debito getnet R$: 255,50'),
(283, 359, 5.00, '2025-04-04 00:00:00', 'Pagou dia 03 via cartão de debito getnet R$: 255,50'),
(284, 419, 27.90, '2025-04-04 00:00:00', 'Pagou dia 03 via cartão de debito getnet R$: 255,50'),
(285, 577, 30.30, '2025-04-04 00:00:00', 'Pagou dia 03 via cartão de debito getnet R$: 255,50'),
(286, 577, 0.50, '2025-04-04 00:00:00', ''),
(287, 447, 23.40, '2025-04-04 00:00:00', 'DEBITO'),
(288, 464, 9.00, '2025-04-04 00:00:00', 'DEBITO'),
(289, 493, 7.90, '2025-04-04 00:00:00', 'DEBITO'),
(290, 506, 5.00, '2025-04-04 00:00:00', 'DEBITO'),
(291, 534, 31.30, '2025-04-04 00:00:00', 'DEBITO'),
(292, 588, 9.00, '2025-04-04 00:00:00', 'DEBITO'),
(293, 595, 8.00, '2025-04-04 00:00:00', 'DEBITO'),
(294, 608, 7.90, '2025-04-04 00:00:00', 'DEBITO'),
(295, 214, 12.00, '2025-04-04 00:00:00', ''),
(296, 238, 4.50, '2025-04-04 00:00:00', ''),
(297, 241, 5.50, '2025-04-04 00:00:00', ''),
(298, 251, 8.00, '2025-04-04 00:00:00', ''),
(299, 325, 7.00, '2025-04-04 00:00:00', ''),
(300, 558, 7.00, '2025-04-04 00:00:00', ''),
(301, 623, 7.00, '2025-04-04 00:00:00', ''),
(302, 624, 7.00, '2025-04-04 00:00:00', ''),
(303, 620, 7.00, '2025-04-04 00:00:00', ''),
(304, 621, 7.00, '2025-04-04 00:00:00', ''),
(305, 622, 7.00, '2025-04-04 00:00:00', ''),
(306, 625, 7.00, '2025-04-04 00:00:00', ''),
(307, 626, 7.00, '2025-04-04 00:00:00', ''),
(308, 628, 7.00, '2025-04-04 00:00:00', ''),
(309, 629, 7.00, '2025-04-04 00:00:00', ''),
(310, 630, 7.00, '2025-04-04 00:00:00', ''),
(311, 627, 7.00, '2025-04-04 00:00:00', ''),
(312, 631, 7.00, '2025-04-04 00:00:00', ''),
(313, 598, 4.50, '2025-04-04 00:00:00', ''),
(314, 642, 4.50, '2025-04-04 00:00:00', ''),
(315, 405, 3.50, '2025-04-04 00:00:00', 'dinheiro'),
(316, 448, 25.90, '2025-04-04 00:00:00', 'dinheiro'),
(317, 482, 13.00, '2025-04-04 00:00:00', 'dinheiro'),
(318, 552, 3.00, '2025-04-04 00:00:00', 'dinheiro'),
(319, 561, 22.90, '2025-04-04 00:00:00', 'dinheiro'),
(320, 600, 7.00, '2025-04-04 00:00:00', 'dinheiro'),
(321, 635, 3.00, '2025-04-04 00:00:00', 'dinheiro'),
(322, 102, 13.50, '2025-04-04 00:00:00', 'PIX'),
(323, 129, 8.00, '2025-04-04 00:00:00', 'PIX'),
(324, 160, 19.00, '2025-04-04 00:00:00', 'PIX'),
(325, 171, 13.00, '2025-04-04 00:00:00', 'PIX'),
(326, 172, 7.00, '2025-04-04 00:00:00', 'PIX'),
(327, 185, 3.00, '2025-04-04 00:00:00', 'PIX'),
(328, 192, 7.00, '2025-04-04 00:00:00', 'PIX'),
(329, 212, 11.00, '2025-04-04 00:00:00', 'PIX'),
(330, 228, 10.00, '2025-04-04 00:00:00', 'PIX'),
(331, 256, 10.00, '2025-04-04 00:00:00', 'PIX'),
(332, 258, 18.90, '2025-04-04 00:00:00', 'PIX'),
(333, 269, 12.00, '2025-04-04 00:00:00', 'PIX'),
(334, 286, 16.90, '2025-04-04 00:00:00', 'PIX'),
(335, 352, 17.50, '2025-04-04 00:00:00', 'PIX'),
(336, 357, 11.00, '2025-04-04 00:00:00', 'PIX'),
(337, 370, 16.90, '2025-04-04 00:00:00', 'PIX'),
(338, 374, 3.00, '2025-04-04 00:00:00', 'PIX'),
(339, 381, 14.90, '2025-04-04 00:00:00', 'PIX'),
(340, 400, 11.00, '2025-04-04 00:00:00', 'PIX'),
(341, 410, 14.90, '2025-04-04 00:00:00', 'PIX'),
(342, 420, 17.90, '2025-04-04 00:00:00', 'PIX'),
(343, 424, 8.00, '2025-04-04 00:00:00', 'PIX'),
(344, 449, 32.90, '2025-04-04 00:00:00', 'PIX'),
(345, 450, 13.00, '2025-04-04 00:00:00', 'PIX'),
(346, 457, 11.00, '2025-04-04 00:00:00', 'PIX'),
(347, 460, 4.00, '2025-04-04 00:00:00', 'PIX'),
(348, 470, 17.00, '2025-04-04 00:00:00', 'PIX'),
(349, 481, 5.00, '2025-04-04 00:00:00', 'PIX'),
(350, 486, 24.40, '2025-04-04 00:00:00', 'PIX'),
(351, 521, 17.00, '2025-04-04 00:00:00', 'PIX'),
(352, 533, 11.00, '2025-04-04 00:00:00', 'PIX'),
(353, 544, 18.00, '2025-04-04 00:00:00', 'PIX'),
(354, 545, 7.90, '2025-04-04 00:00:00', 'PIX'),
(355, 554, 18.90, '2025-04-04 00:00:00', 'PIX'),
(356, 589, 8.50, '2025-04-04 00:00:00', 'PIX'),
(357, 596, 11.00, '2025-04-04 00:00:00', 'PIX'),
(358, 604, 10.00, '2025-04-04 00:00:00', 'PIX'),
(359, 607, 25.00, '2025-04-04 00:00:00', 'PIX'),
(360, 632, 10.00, '2025-04-04 00:00:00', 'PIX'),
(361, 633, 7.00, '2025-04-04 00:00:00', 'PIX'),
(362, 77, 30.90, '2025-04-05 00:00:00', 'Pago banco Santander - dia 05/04'),
(363, 85, 10.00, '2025-04-05 00:00:00', 'Pago banco Santander - dia 05/04'),
(364, 130, 6.00, '2025-04-05 00:00:00', 'Pago banco Santander - dia 05/04'),
(365, 151, 16.50, '2025-04-05 00:00:00', 'Pago banco Santander - dia 05/04'),
(366, 197, 8.50, '2025-04-05 00:00:00', 'Pago banco Santander - dia 05/04'),
(367, 282, 15.00, '2025-04-05 00:00:00', 'Pago banco Santander - dia 05/04'),
(368, 319, 7.00, '2025-04-05 00:00:00', 'Pago banco Santander - dia 05/04'),
(369, 336, 10.00, '2025-04-05 00:00:00', 'Pago banco Santander - dia 05/04'),
(370, 384, 13.00, '2025-04-05 00:00:00', 'Pago banco Santander - dia 05/04'),
(371, 398, 14.40, '2025-04-05 00:00:00', 'Pago banco Santander - dia 05/04'),
(372, 426, 18.00, '2025-04-05 00:00:00', 'Pago banco Santander - dia 05/04'),
(373, 439, 10.00, '2025-04-05 00:00:00', 'Pago banco Santander - dia 05/04'),
(374, 476, 19.00, '2025-04-05 00:00:00', 'Pago banco Santander - dia 05/04'),
(375, 495, 14.50, '2025-04-05 00:00:00', 'Pago banco Santander - dia 05/04'),
(376, 523, 10.00, '2025-04-05 00:00:00', 'Pago banco Santander - dia 05/04'),
(377, 529, 4.00, '2025-04-05 00:00:00', 'Pago banco Santander - dia 05/04'),
(378, 548, 5.00, '2025-04-05 00:00:00', 'Pago banco Santander - dia 05/04'),
(379, 584, 7.00, '2025-04-05 00:00:00', ''),
(380, 610, 16.00, '2025-04-05 00:00:00', ''),
(381, 166, 29.90, '2025-04-07 00:00:00', 'pix'),
(382, 170, 3.00, '2025-04-07 00:00:00', 'pix'),
(383, 217, 46.90, '2025-04-07 00:00:00', 'pix'),
(384, 219, 5.00, '2025-04-07 00:00:00', 'pix'),
(385, 223, 11.50, '2025-04-07 00:00:00', 'pix'),
(386, 249, 61.20, '2025-04-07 00:00:00', 'pix'),
(387, 261, 20.50, '2025-04-07 00:00:00', 'pix'),
(388, 292, 3.50, '2025-04-07 00:00:00', 'pix'),
(389, 295, 25.90, '2025-04-07 00:00:00', 'pix'),
(390, 311, 10.00, '2025-04-07 00:00:00', 'pix'),
(391, 331, 18.00, '2025-04-07 00:00:00', 'pix'),
(392, 340, 10.00, '2025-04-07 00:00:00', 'pix'),
(393, 350, 13.00, '2025-04-07 00:00:00', 'pix'),
(394, 353, 50.70, '2025-04-07 00:00:00', 'pix'),
(395, 379, 18.90, '2025-04-07 00:00:00', 'pix'),
(396, 395, 33.90, '2025-04-07 00:00:00', 'pix'),
(397, 411, 5.00, '2025-04-07 00:00:00', 'pix'),
(398, 432, 16.50, '2025-04-07 00:00:00', 'pix'),
(399, 437, 4.00, '2025-04-07 00:00:00', 'pix'),
(400, 455, 10.50, '2025-04-07 00:00:00', 'pix'),
(401, 466, 34.90, '2025-04-07 00:00:00', 'pix'),
(402, 483, 14.00, '2025-04-07 00:00:00', 'pix'),
(403, 489, 30.90, '2025-04-07 00:00:00', 'pix'),
(404, 494, 6.00, '2025-04-07 00:00:00', 'pix'),
(405, 501, 10.50, '2025-04-07 00:00:00', 'pix'),
(406, 508, 24.90, '2025-04-07 00:00:00', 'pix'),
(407, 514, 17.00, '2025-04-07 00:00:00', 'pix'),
(408, 526, 24.00, '2025-04-07 00:00:00', 'pix'),
(409, 531, 32.40, '2025-04-07 00:00:00', 'pix'),
(410, 550, 10.00, '2025-04-07 00:00:00', 'pix'),
(411, 564, 3.00, '2025-04-07 00:00:00', 'pix'),
(412, 601, 29.40, '2025-04-07 00:00:00', 'pix'),
(413, 609, 24.90, '2025-04-07 00:00:00', 'pix'),
(414, 641, 14.00, '2025-04-07 00:00:00', 'pix'),
(415, 573, 7.00, '2025-04-07 00:00:00', 'dinheiro'),
(416, 606, 6.00, '2025-04-07 00:00:00', 'dinheiro'),
(417, 36, 100.00, '2025-04-07 00:00:00', 'DEBITO MAQUINA VERMELHA'),
(418, 36, 4.80, '2025-04-07 00:00:00', 'CREDITO MAQUINA VERMELHA'),
(419, 78, 35.00, '2025-04-07 00:00:00', 'CREDITO MAQUINA VERMELHA'),
(420, 139, 5.00, '2025-04-07 00:00:00', 'CREDITO MAQUINA VERMELHA'),
(421, 180, 28.80, '2025-04-07 00:00:00', 'CREDITO MAQUINA VERMELHA'),
(422, 202, 6.00, '2025-04-07 00:00:00', 'CREDITO MAQUINA VERMELHA'),
(423, 203, 10.00, '2025-04-07 00:00:00', 'CREDITO MAQUINA VERMELHA'),
(424, 239, 5.00, '2025-04-07 00:00:00', 'CREDITO MAQUINA VERMELHA'),
(425, 252, 10.00, '2025-04-07 00:00:00', 'CREDITO MAQUINA VERMELHA'),
(426, 262, 10.00, '2025-04-07 00:00:00', 'CREDITO MAQUINA VERMELHA'),
(427, 284, 12.50, '2025-04-07 00:00:00', 'CREDITO MAQUINA VERMELHA'),
(428, 305, 13.00, '2025-04-07 00:00:00', 'CREDITO MAQUINA VERMELHA'),
(429, 323, 2.00, '2025-04-07 00:00:00', 'CREDITO MAQUINA VERMELHA'),
(430, 337, 12.50, '2025-04-07 00:00:00', 'CREDITO MAQUINA VERMELHA'),
(431, 338, 4.00, '2025-04-07 00:00:00', 'CREDITO MAQUINA VERMELHA'),
(432, 362, 5.00, '2025-04-07 00:00:00', 'CREDITO MAQUINA VERMELHA'),
(433, 372, 12.50, '2025-04-07 00:00:00', 'CREDITO MAQUINA VERMELHA'),
(434, 391, 23.90, '2025-04-07 00:00:00', 'CREDITO MAQUINA VERMELHA'),
(435, 259, 7.00, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(436, 260, 7.00, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(437, 271, 18.00, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(438, 283, 10.00, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(439, 287, 4.00, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(440, 294, 17.90, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(441, 358, 28.90, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(442, 406, 15.90, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(443, 423, 12.00, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(444, 456, 6.00, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(445, 472, 14.00, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(446, 510, 17.00, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(447, 515, 8.00, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(448, 527, 16.90, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(449, 537, 62.30, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(450, 549, 7.00, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(451, 576, 63.80, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(452, 586, 7.00, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(453, 615, 18.90, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(454, 637, 25.80, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(455, 648, 8.00, '2025-04-07 00:00:00', 'credito maquina vermelha'),
(456, 111, 17.40, '2025-04-07 00:00:00', ''),
(457, 144, 10.00, '2025-04-07 00:00:00', ''),
(458, 145, 10.00, '2025-04-07 00:00:00', ''),
(459, 183, 10.00, '2025-04-07 00:00:00', ''),
(460, 215, 10.00, '2025-04-07 00:00:00', ''),
(461, 235, 24.90, '2025-04-07 00:00:00', ''),
(462, 285, 7.00, '2025-04-07 00:00:00', ''),
(463, 308, 10.00, '2025-04-07 00:00:00', ''),
(464, 309, 7.00, '2025-04-07 00:00:00', ''),
(465, 326, 10.00, '2025-04-07 00:00:00', ''),
(466, 360, 10.00, '2025-04-07 00:00:00', ''),
(467, 377, 5.50, '2025-04-07 00:00:00', ''),
(468, 392, 10.00, '2025-04-07 00:00:00', ''),
(469, 416, 17.00, '2025-04-07 00:00:00', ''),
(470, 435, 10.00, '2025-04-07 00:00:00', ''),
(471, 444, 4.00, '2025-04-07 00:00:00', ''),
(472, 458, 10.00, '2025-04-07 00:00:00', ''),
(473, 467, 39.00, '2025-04-07 00:00:00', ''),
(474, 488, 18.90, '2025-04-07 00:00:00', ''),
(475, 502, 10.00, '2025-04-07 00:00:00', ''),
(476, 541, 20.00, '2025-04-07 00:00:00', ''),
(477, 563, 13.00, '2025-04-07 00:00:00', ''),
(478, 585, 39.00, '2025-04-07 00:00:00', ''),
(479, 281, 22.40, '2025-04-07 00:00:00', 'Pagou no credito - maquina amarela '),
(480, 327, 11.40, '2025-04-07 00:00:00', 'Pagou no credito - maquina amarela '),
(481, 368, 15.00, '2025-04-07 00:00:00', 'Pagou no credito - maquina amarela '),
(482, 371, 15.00, '2025-04-07 00:00:00', 'Pagou no credito - maquina amarela '),
(483, 393, 5.50, '2025-04-07 00:00:00', 'Pagou no credito - maquina amarela '),
(484, 415, 3.50, '2025-04-07 00:00:00', 'Pagou no credito - maquina amarela '),
(485, 438, 18.90, '2025-04-07 00:00:00', 'Pagou no credito - maquina amarela '),
(486, 538, 23.90, '2025-04-07 00:00:00', 'Pagou no credito - maquina amarela '),
(487, 568, 18.90, '2025-04-07 00:00:00', 'Pagou no credito - maquina amarela '),
(488, 652, 26.80, '2025-04-07 00:00:00', 'Pagou no credito - maquina amarela '),
(489, 103, 2.30, '2025-04-08 00:00:00', 'Pagou no pix '),
(490, 174, 19.40, '2025-04-08 00:00:00', 'Pagou no pix '),
(491, 268, 3.00, '2025-04-08 00:00:00', 'Pagou no pix '),
(492, 273, 11.00, '2025-04-08 00:00:00', 'Pagou no pix '),
(493, 289, 7.00, '2025-04-08 00:00:00', 'Pagou no pix '),
(494, 303, 31.90, '2025-04-08 00:00:00', 'Pagou no pix '),
(495, 399, 10.00, '2025-04-08 00:00:00', 'Pagou no pix '),
(496, 459, 18.40, '2025-04-08 00:00:00', 'Pagou no pix '),
(497, 546, 20.40, '2025-04-08 00:00:00', 'Pagou no pix '),
(498, 671, 5.00, '2025-04-08 00:00:00', 'Pagou no pix '),
(499, 45, 18.90, '2025-04-08 00:00:00', ''),
(500, 45, 48.90, '2025-04-08 00:00:00', ''),
(501, 443, 18.90, '2025-04-08 00:00:00', ''),
(502, 647, 18.89, '2025-04-08 00:00:00', ''),
(503, 69, 11.00, '2025-04-09 00:00:00', 'pix'),
(504, 242, 13.90, '2025-04-09 00:00:00', 'pix'),
(505, 296, 18.90, '2025-04-09 00:00:00', 'pix'),
(506, 349, 4.00, '2025-04-09 00:00:00', 'pix'),
(507, 373, 7.00, '2025-04-09 00:00:00', 'pix'),
(508, 402, 4.00, '2025-04-09 00:00:00', 'pix'),
(509, 413, 9.00, '2025-04-09 00:00:00', 'pix'),
(510, 498, 12.00, '2025-04-09 00:00:00', 'pix'),
(511, 559, 8.00, '2025-04-09 00:00:00', 'pix'),
(512, 707, 7.00, '2025-04-09 00:00:00', 'abateu do credito'),
(513, 300, 12.50, '2025-04-10 00:00:00', 'DINHEIRO'),
(514, 436, 3.00, '2025-04-10 00:00:00', 'DINHEIRO'),
(515, 724, 13.50, '2025-04-10 00:00:00', 'DINHEIRO'),
(516, 741, 18.50, '2025-04-10 00:00:00', 'CREDITO VERMELHA'),
(517, 226, 12.90, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(518, 227, 22.50, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(519, 234, 24.90, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(520, 250, 18.90, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(521, 316, 21.90, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(522, 332, 22.00, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(523, 356, 32.90, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(524, 376, 11.00, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(525, 394, 12.00, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(526, 430, 19.00, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(527, 453, 45.00, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(528, 468, 23.40, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(529, 477, 38.90, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(530, 569, 19.50, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(531, 581, 29.90, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(532, 650, 50.40, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(533, 685, 10.00, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(534, 715, 18.90, '2025-04-11 00:00:00', 'DEBITO AMARELO'),
(535, 431, 4.00, '2025-04-11 00:00:00', ''),
(536, 441, 19.90, '2025-04-11 00:00:00', ''),
(537, 487, 59.80, '2025-04-11 00:00:00', ''),
(538, 518, 4.00, '2025-04-11 00:00:00', ''),
(539, 519, 4.00, '2025-04-11 00:00:00', ''),
(540, 706, 5.00, '2025-04-11 00:00:00', ''),
(541, 719, 34.90, '2025-04-11 00:00:00', ''),
(542, 761, 11.00, '2025-04-11 00:00:00', ''),
(543, 762, 9.00, '2025-04-11 00:00:00', ''),
(544, 94, 10.00, '2025-04-14 00:00:00', 'pix'),
(545, 209, 217.70, '2025-04-14 00:00:00', 'pix'),
(546, 475, 14.00, '2025-04-14 00:00:00', 'pix'),
(547, 496, 10.00, '2025-04-14 00:00:00', 'pix'),
(548, 499, 7.00, '2025-04-14 00:00:00', 'pix'),
(549, 509, 10.00, '2025-04-14 00:00:00', 'pix'),
(550, 524, 5.00, '2025-04-14 00:00:00', 'pix'),
(551, 530, 7.90, '2025-04-14 00:00:00', 'pix'),
(552, 557, 7.90, '2025-04-14 00:00:00', 'pix'),
(553, 556, 7.90, '2025-04-14 00:00:00', 'pix'),
(554, 562, 10.00, '2025-04-14 00:00:00', 'pix'),
(555, 737, 3.30, '2025-04-16 00:00:00', 'desconto do credito'),
(556, 32, 100.00, '2025-04-16 00:00:00', 'pix'),
(557, 199, 17.90, '2025-04-17 00:00:00', 'PIX'),
(558, 222, 7.00, '2025-04-17 00:00:00', 'PIX'),
(559, 344, 70.00, '2025-04-17 00:00:00', 'PIX'),
(560, 408, 15.00, '2025-04-17 00:00:00', 'PIX'),
(561, 427, 43.00, '2025-04-17 00:00:00', 'PIX'),
(562, 434, 7.90, '2025-04-17 00:00:00', 'PIX'),
(563, 451, 52.40, '2025-04-17 00:00:00', 'PIX'),
(564, 462, 42.00, '2025-04-17 00:00:00', 'PIX'),
(565, 507, 40.00, '2025-04-17 00:00:00', 'PIX'),
(566, 513, 19.80, '2025-04-17 00:00:00', 'PIX'),
(567, 516, 10.00, '2025-04-17 00:00:00', 'PIX'),
(568, 522, 14.00, '2025-04-17 00:00:00', 'PIX'),
(569, 594, 9.50, '2025-04-17 00:00:00', 'PIX'),
(570, 688, 19.00, '2025-04-17 00:00:00', 'PIX'),
(571, 694, 18.00, '2025-04-17 00:00:00', 'PIX'),
(572, 695, 18.00, '2025-04-17 00:00:00', 'PIX'),
(573, 696, 18.00, '2025-04-17 00:00:00', 'PIX'),
(574, 698, 7.50, '2025-04-17 00:00:00', 'PIX'),
(575, 29, 100.00, '2025-04-22 00:00:00', 'credito vermelha'),
(576, 27, 11.50, '2025-04-22 00:00:00', ''),
(577, 737, 18.60, '2025-04-22 00:00:00', ''),
(578, 814, 11.00, '2025-04-22 00:00:00', ''),
(579, 822, 18.90, '2025-04-22 00:00:00', ''),
(580, 842, 3.00, '2025-04-22 00:00:00', ''),
(581, 547, 12.50, '2025-04-22 00:00:00', ''),
(582, 579, 11.90, '2025-04-22 00:00:00', ''),
(583, 654, 18.90, '2025-04-22 00:00:00', ''),
(584, 756, 18.90, '2025-04-22 00:00:00', ''),
(585, 844, 18.90, '2025-04-22 00:00:00', ''),
(586, 31, 100.00, '2025-04-23 00:00:00', 'dinheiro'),
(587, 26, 7.00, '2025-04-24 00:00:00', 'debito'),
(588, 697, 18.00, '2025-04-24 00:00:00', 'FOI A MAIS'),
(589, 698, 0.90, '2025-04-24 00:00:00', 'FOI A MAIS'),
(590, 29, 23.90, '2025-04-24 00:00:00', ''),
(591, 31, 13.00, '2025-04-24 00:00:00', ''),
(592, 34, 97.60, '2025-04-24 00:00:00', 'pix'),
(593, 582, 7.00, '2025-04-25 00:00:00', ''),
(594, 599, 25.90, '2025-04-25 00:00:00', ''),
(595, 602, 13.00, '2025-04-25 00:00:00', ''),
(596, 613, 43.30, '2025-04-25 00:00:00', ''),
(597, 645, 18.90, '2025-04-25 00:00:00', ''),
(598, 674, 7.00, '2025-04-25 00:00:00', ''),
(599, 680, 21.90, '2025-04-25 00:00:00', ''),
(600, 687, 7.00, '2025-04-25 00:00:00', ''),
(601, 699, 7.00, '2025-04-25 00:00:00', ''),
(602, 709, 33.80, '2025-04-25 00:00:00', ''),
(603, 722, 7.00, '2025-04-25 00:00:00', ''),
(604, 734, 51.80, '2025-04-25 00:00:00', ''),
(605, 745, 16.00, '2025-04-25 00:00:00', ''),
(606, 757, 14.00, '2025-04-25 00:00:00', ''),
(607, 796, 6.00, '2025-04-25 00:00:00', ''),
(608, 799, 28.80, '2025-04-25 00:00:00', ''),
(609, 801, 11.90, '2025-04-25 00:00:00', ''),
(610, 819, 34.80, '2025-04-25 00:00:00', ''),
(611, 829, 9.90, '2025-04-25 00:00:00', ''),
(612, 840, 24.80, '2025-04-25 00:00:00', ''),
(613, 862, 9.90, '2025-04-25 00:00:00', ''),
(614, 867, 43.70, '2025-04-25 00:00:00', ''),
(615, 874, 7.00, '2025-04-25 00:00:00', ''),
(616, 879, 56.70, '2025-04-25 00:00:00', ''),
(617, 774, 7.50, '2025-04-25 00:00:00', 'pix'),
(618, 776, 4.50, '2025-04-25 00:00:00', 'pix'),
(619, 896, 8.50, '2025-04-25 00:00:00', 'pix'),
(620, 48, 111.50, '2025-04-28 00:00:00', ''),
(621, 31, 20.00, '2025-04-28 00:00:00', 'TAVA NO SISTEMA - PAGOU PARA O RODRIGO '),
(622, 272, 25.00, '2025-04-28 00:00:00', 'cobranca indevida - alegou não consumiu '),
(623, 712, 28.80, '2025-04-29 00:00:00', 'debito amarela'),
(624, 731, 31.90, '2025-04-29 00:00:00', 'debito amarela'),
(625, 748, 5.50, '2025-04-29 00:00:00', 'debito amarela'),
(626, 818, 18.90, '2025-04-29 00:00:00', 'debito amarela'),
(627, 836, 6.00, '2025-04-29 00:00:00', 'debito amarela'),
(628, 878, 7.00, '2025-04-29 00:00:00', 'debito amarela'),
(629, 912, 11.90, '2025-04-29 00:00:00', 'debito amarela'),
(630, 597, 9.00, '2025-04-30 00:00:00', 'CREDITO'),
(631, 639, 7.00, '2025-04-30 00:00:00', 'CREDITO'),
(632, 733, 13.00, '2025-04-30 00:00:00', 'CREDITO'),
(633, 788, 11.00, '2025-04-30 00:00:00', 'CREDITO'),
(634, 881, 4.00, '2025-04-30 00:00:00', 'CREDITO'),
(635, 905, 16.00, '2025-04-30 00:00:00', 'CREDITO'),
(636, 31, 50.00, '2025-05-02 00:00:00', 'dinheiro'),
(637, 928, 36.90, '2025-05-05 00:00:00', 'CREDITO AMARELA'),
(638, 942, 9.00, '2025-05-05 00:00:00', 'CREDITO AMARELA'),
(639, 958, 11.90, '2025-05-05 00:00:00', 'CREDITO AMARELA'),
(640, 959, 21.90, '2025-05-05 00:00:00', 'CREDITO AMARELA'),
(641, 880, 18.90, '2025-05-05 00:00:00', 'Erro - correcao '),
(642, 157, 24.90, '2025-05-06 00:00:00', ''),
(643, 429, 15.50, '2025-05-06 00:00:00', ''),
(644, 440, 7.00, '2025-05-06 00:00:00', ''),
(645, 454, 5.00, '2025-05-06 00:00:00', ''),
(646, 638, 10.00, '2025-05-06 00:00:00', ''),
(647, 684, 23.90, '2025-05-06 00:00:00', ''),
(648, 937, 28.00, '2025-05-06 00:00:00', ''),
(649, 390, 0.90, '2025-05-06 00:00:00', ''),
(650, 469, 27.40, '2025-05-06 00:00:00', ''),
(651, 503, 20.00, '2025-05-06 00:00:00', ''),
(652, 505, 20.00, '2025-05-06 00:00:00', ''),
(653, 504, 20.00, '2025-05-06 00:00:00', ''),
(654, 617, 25.90, '2025-05-06 00:00:00', ''),
(655, 636, 7.00, '2025-05-06 00:00:00', ''),
(656, 644, 23.40, '2025-05-06 00:00:00', ''),
(657, 681, 4.00, '2025-05-06 00:00:00', ''),
(658, 689, 23.40, '2025-05-06 00:00:00', ''),
(659, 708, 11.90, '2025-05-06 00:00:00', ''),
(660, 711, 18.90, '2025-05-06 00:00:00', ''),
(661, 752, 14.90, '2025-05-06 00:00:00', ''),
(662, 779, 4.00, '2025-05-06 00:00:00', ''),
(663, 894, 4.50, '2025-05-06 00:00:00', ''),
(664, 956, 8.00, '2025-05-06 00:00:00', ''),
(665, 962, 3.00, '2025-05-06 00:00:00', ''),
(666, 982, 3.00, '2025-05-06 00:00:00', ''),
(667, 924, 146.30, '2025-05-06 00:00:00', 'DEBITO AMARELA'),
(668, 934, 17.90, '2025-05-06 00:00:00', 'DEBITO AMARELA'),
(669, 955, 21.00, '2025-05-06 00:00:00', 'DEBITO AMARELA'),
(670, 983, 9.00, '2025-05-06 00:00:00', 'DEBITO AMARELA'),
(671, 272, 17.20, '2025-05-06 00:00:00', 'Pagou via pix'),
(672, 471, 24.90, '2025-05-06 00:00:00', 'Pagou via pix'),
(673, 491, 18.90, '2025-05-06 00:00:00', 'Pagou via pix'),
(674, 512, 9.00, '2025-05-06 00:00:00', 'Pagou via pix'),
(675, 723, 22.90, '2025-05-06 00:00:00', 'Pagou via pix'),
(676, 830, 7.10, '2025-05-06 00:00:00', 'Pagou via pix'),
(677, 646, 27.90, '2025-05-06 00:00:00', 'Pix PagBank'),
(678, 657, 7.00, '2025-05-06 00:00:00', 'Pix PagBank'),
(679, 658, 11.90, '2025-05-06 00:00:00', 'Pix PagBank'),
(680, 664, 10.00, '2025-05-06 00:00:00', 'Pix PagBank'),
(681, 743, 60.70, '2025-05-06 00:00:00', 'Pix PagBank'),
(682, 766, 76.80, '2025-05-06 00:00:00', 'Pix PagBank'),
(683, 767, 16.00, '2025-05-06 00:00:00', 'Pix PagBank'),
(684, 781, 9.00, '2025-05-06 00:00:00', 'Pix PagBank'),
(685, 826, 22.90, '2025-05-06 00:00:00', 'Pix PagBank'),
(686, 841, 36.80, '2025-05-06 00:00:00', 'Pix PagBank'),
(687, 893, 38.80, '2025-05-06 00:00:00', 'Pix PagBank'),
(688, 640, 25.80, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(689, 643, 22.90, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(690, 655, 7.00, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(691, 663, 13.00, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(692, 672, 7.00, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(693, 676, 17.00, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(694, 702, 12.00, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(695, 703, 12.00, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(696, 717, 14.90, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(697, 740, 6.00, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(698, 754, 7.00, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(699, 793, 16.90, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(700, 808, 3.50, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(701, 823, 14.90, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(702, 834, 20.90, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(703, 838, 22.90, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(704, 853, 7.00, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(705, 856, 13.90, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(706, 926, 48.80, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(707, 947, 5.00, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(708, 957, 39.80, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(709, 992, 5.99, '2025-05-07 00:00:00', 'VIA PIX - SANTANDER'),
(710, 739, 4.00, '2025-05-07 00:00:00', ''),
(711, 750, 4.00, '2025-05-07 00:00:00', ''),
(712, 902, 4.00, '2025-05-07 00:00:00', ''),
(713, 976, 4.50, '2025-05-07 00:00:00', ''),
(714, 683, 11.00, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(715, 705, 12.90, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(716, 720, 32.80, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(717, 730, 23.90, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(718, 763, 26.00, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(719, 790, 6.00, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(720, 821, 48.80, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(721, 839, 33.50, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(722, 848, 17.90, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(723, 866, 51.90, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(724, 875, 18.50, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(725, 899, 45.80, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(726, 907, 17.50, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(727, 925, 35.90, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(728, 945, 29.90, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(729, 960, 25.50, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(730, 964, 21.00, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(731, 997, 56.40, '2025-05-07 00:00:00', 'BANCO INTER  PIX'),
(732, 661, 2.00, '2025-05-07 00:00:00', 'PIX - SANTANDER'),
(733, 718, 4.00, '2025-05-07 00:00:00', 'PIX - SANTANDER'),
(734, 738, 9.00, '2025-05-07 00:00:00', 'PIX - SANTANDER'),
(735, 758, 10.50, '2025-05-07 00:00:00', 'PIX - SANTANDER'),
(736, 780, 16.90, '2025-05-07 00:00:00', 'PIX - SANTANDER'),
(737, 810, 14.50, '2025-05-07 00:00:00', 'PIX - SANTANDER'),
(738, 825, 27.40, '2025-05-07 00:00:00', 'PIX - SANTANDER'),
(739, 835, 29.40, '2025-05-07 00:00:00', 'PIX - SANTANDER'),
(740, 855, 13.50, '2025-05-07 00:00:00', 'PIX - SANTANDER'),
(741, 863, 5.50, '2025-05-07 00:00:00', 'PIX - SANTANDER'),
(742, 889, 4.00, '2025-05-07 00:00:00', 'PIX - SANTANDER'),
(743, 923, 3.00, '2025-05-07 00:00:00', 'PIX - SANTANDER'),
(744, 943, 18.00, '2025-05-07 00:00:00', 'PIX - SANTANDER'),
(745, 954, 12.00, '2025-05-07 00:00:00', 'PIX - SANTANDER'),
(746, 969, 5.50, '2025-05-07 00:00:00', 'PIX - SANTANDER'),
(747, 984, 24.49, '2025-05-07 00:00:00', 'PIX - SANTANDER'),
(748, 751, 5.50, '2025-05-07 00:00:00', 'DINHEIRO'),
(749, 784, 5.00, '2025-05-07 00:00:00', 'DINHEIRO'),
(750, 785, 5.00, '2025-05-07 00:00:00', 'DINHEIRO'),
(751, 887, 9.00, '2025-05-07 00:00:00', 'DINHEIRO'),
(752, 938, 7.00, '2025-05-07 00:00:00', 'DINHEIRO'),
(753, 948, 5.00, '2025-05-07 00:00:00', 'DINHEIRO'),
(754, 1002, 4.50, '2025-05-07 00:00:00', 'DINHEIRO'),
(755, 47, 22.00, '2025-05-07 00:00:00', ''),
(756, 153, 8.00, '2025-05-07 00:00:00', ''),
(757, 345, 4.00, '2025-05-07 00:00:00', ''),
(758, 520, 11.90, '2025-05-07 00:00:00', ''),
(759, 660, 18.00, '2025-05-07 00:00:00', ''),
(760, 775, 10.00, '2025-05-07 00:00:00', ''),
(761, 777, 7.90, '2025-05-07 00:00:00', ''),
(762, 798, 9.00, '2025-05-07 00:00:00', ''),
(763, 812, 35.80, '2025-05-07 00:00:00', ''),
(764, 854, 8.00, '2025-05-07 00:00:00', ''),
(765, 913, 450.00, '2025-05-07 00:00:00', ''),
(766, 935, 3.00, '2025-05-07 00:00:00', ''),
(767, 802, 2.00, '2025-05-07 00:00:00', ''),
(768, 803, 7.00, '2025-05-07 00:00:00', ''),
(769, 820, 16.00, '2025-05-07 00:00:00', ''),
(770, 843, 39.80, '2025-05-07 00:00:00', ''),
(771, 922, 27.90, '2025-05-07 00:00:00', ''),
(772, 1000, 11.00, '2025-05-07 00:00:00', ''),
(773, 1004, 0.00, '2025-05-07 00:00:00', ''),
(774, 22, 6.00, '2025-05-08 00:00:00', ''),
(775, 62, 18.90, '2025-05-08 00:00:00', ''),
(776, 70, 5.50, '2025-05-08 00:00:00', ''),
(777, 86, 18.50, '2025-05-08 00:00:00', ''),
(778, 105, 14.90, '2025-05-08 00:00:00', ''),
(779, 106, 19.00, '2025-05-08 00:00:00', ''),
(780, 119, 11.90, '2025-05-08 00:00:00', ''),
(781, 161, 13.50, '2025-05-08 00:00:00', ''),
(782, 181, 14.00, '2025-05-08 00:00:00', ''),
(783, 198, 8.00, '2025-05-08 00:00:00', ''),
(784, 210, 19.90, '2025-05-08 00:00:00', ''),
(785, 293, 14.00, '2025-05-08 00:00:00', ''),
(786, 307, 20.40, '2025-05-08 00:00:00', ''),
(787, 317, 7.00, '2025-05-08 00:00:00', ''),
(788, 320, 14.00, '2025-05-08 00:00:00', ''),
(789, 322, 5.50, '2025-05-08 00:00:00', ''),
(790, 339, 10.00, '2025-05-08 00:00:00', ''),
(791, 348, 4.50, '2025-05-08 00:00:00', ''),
(792, 354, 7.50, '2025-05-08 00:00:00', ''),
(793, 364, 5.50, '2025-05-08 00:00:00', ''),
(794, 403, 6.50, '2025-05-08 00:00:00', ''),
(795, 412, 14.90, '2025-05-08 00:00:00', ''),
(796, 446, 37.40, '2025-05-08 00:00:00', ''),
(797, 461, 6.50, '2025-05-08 00:00:00', ''),
(798, 480, 11.50, '2025-05-08 00:00:00', ''),
(799, 497, 7.00, '2025-05-08 00:00:00', ''),
(800, 532, 8.50, '2025-05-08 00:00:00', ''),
(801, 555, 6.00, '2025-05-08 00:00:00', ''),
(802, 566, 23.40, '2025-05-08 00:00:00', ''),
(803, 572, 11.90, '2025-05-08 00:00:00', ''),
(804, 580, 21.90, '2025-05-08 00:00:00', ''),
(805, 618, 5.00, '2025-05-08 00:00:00', ''),
(806, 653, 26.80, '2025-05-08 00:00:00', ''),
(807, 876, 9.00, '2025-05-08 00:00:00', ''),
(808, 886, 8.00, '2025-05-08 00:00:00', ''),
(809, 732, 6.00, '2025-05-08 00:00:00', 'PIX'),
(810, 759, 5.50, '2025-05-08 00:00:00', 'PIX'),
(811, 773, 10.00, '2025-05-08 00:00:00', 'PIX'),
(812, 786, 3.00, '2025-05-08 00:00:00', 'PIX'),
(813, 795, 11.00, '2025-05-08 00:00:00', 'PIX'),
(814, 811, 9.00, '2025-05-08 00:00:00', 'PIX'),
(815, 837, 22.00, '2025-05-08 00:00:00', 'PIX'),
(816, 851, 3.50, '2025-05-08 00:00:00', 'PIX'),
(817, 884, 29.90, '2025-05-08 00:00:00', 'PIX'),
(818, 898, 43.30, '2025-05-08 00:00:00', 'PIX'),
(819, 920, 6.80, '2025-05-08 00:00:00', 'PIX'),
(820, 570, 5.00, '2025-05-09 00:00:00', 'CREDITO AMARELA'),
(821, 575, 25.90, '2025-05-09 00:00:00', 'CREDITO AMARELA'),
(822, 591, 11.00, '2025-05-09 00:00:00', 'CREDITO AMARELA'),
(823, 605, 11.00, '2025-05-09 00:00:00', 'CREDITO AMARELA'),
(824, 611, 12.50, '2025-05-09 00:00:00', 'CREDITO AMARELA'),
(825, 614, 34.70, '2025-05-09 00:00:00', 'CREDITO AMARELA'),
(826, 868, 7.90, '2025-05-09 00:00:00', 'CREDITO AMARELA'),
(827, 921, 10.00, '2025-05-09 00:00:00', 'CREDITO AMARELA'),
(828, 930, 6.00, '2025-05-09 00:00:00', 'CREDITO AMARELA'),
(829, 168, 6.00, '2025-05-12 00:00:00', 'credito amarelo'),
(830, 218, 14.00, '2025-05-12 00:00:00', 'credito amarelo'),
(831, 243, 18.90, '2025-05-12 00:00:00', 'credito amarelo'),
(832, 263, 15.50, '2025-05-12 00:00:00', 'credito amarelo'),
(833, 267, 9.90, '2025-05-12 00:00:00', 'credito amarelo'),
(834, 313, 21.50, '2025-05-12 00:00:00', 'credito amarelo'),
(835, 314, 5.50, '2025-05-12 00:00:00', 'credito amarelo'),
(836, 324, 17.90, '2025-05-12 00:00:00', 'credito amarelo'),
(837, 328, 10.00, '2025-05-12 00:00:00', 'credito amarelo'),
(838, 386, 12.00, '2025-05-12 00:00:00', 'credito amarelo'),
(839, 484, 19.80, '2025-05-12 00:00:00', 'credito amarelo'),
(840, 560, 18.90, '2025-05-12 00:00:00', 'credito amarelo'),
(841, 571, 18.90, '2025-05-12 00:00:00', 'credito amarelo'),
(842, 656, 22.50, '2025-05-12 00:00:00', 'credito amarelo'),
(843, 760, 14.00, '2025-05-12 00:00:00', 'credito amarelo'),
(844, 864, 14.00, '2025-05-12 00:00:00', 'credito amarelo'),
(845, 888, 20.00, '2025-05-12 00:00:00', 'credito amarelo'),
(846, 1038, 18.90, '2025-05-12 00:00:00', 'credito amarelo'),
(847, 973, 21.90, '2025-05-12 00:00:00', 'credito amarela'),
(848, 988, 44.20, '2025-05-12 00:00:00', 'credito amarela'),
(849, 996, 8.00, '2025-05-12 00:00:00', 'credito amarela'),
(850, 1016, 48.80, '2025-05-12 00:00:00', 'credito amarela'),
(851, 1017, 25.90, '2025-05-12 00:00:00', 'credito amarela'),
(852, 463, 12.50, '2025-05-12 00:00:00', 'credito/pix amarela'),
(853, 540, 11.90, '2025-05-12 00:00:00', 'credito/pix amarela'),
(854, 578, 28.90, '2025-05-12 00:00:00', 'credito/pix amarela'),
(855, 590, 3.00, '2025-05-12 00:00:00', 'credito/pix amarela'),
(856, 616, 11.90, '2025-05-12 00:00:00', 'credito/pix amarela'),
(857, 634, 10.00, '2025-05-12 00:00:00', 'credito/pix amarela'),
(858, 667, 10.00, '2025-05-12 00:00:00', 'credito/pix amarela'),
(859, 669, 23.00, '2025-05-12 00:00:00', 'credito/pix amarela'),
(860, 690, 5.00, '2025-05-12 00:00:00', 'credito/pix amarela'),
(861, 704, 9.00, '2025-05-12 00:00:00', 'credito/pix amarela'),
(862, 744, 5.00, '2025-05-12 00:00:00', 'credito/pix amarela'),
(863, 797, 10.00, '2025-05-12 00:00:00', 'credito/pix amarela'),
(864, 806, 10.00, '2025-05-12 00:00:00', 'credito/pix amarela'),
(865, 816, 10.00, '2025-05-12 00:00:00', 'credito/pix amarela'),
(866, 817, 10.00, '2025-05-12 00:00:00', 'credito/pix amarela'),
(867, 847, 11.90, '2025-05-12 00:00:00', 'credito/pix amarela'),
(868, 865, 7.00, '2025-05-12 00:00:00', 'credito/pix amarela'),
(869, 895, 48.90, '2025-05-12 00:00:00', 'credito/pix amarela'),
(870, 911, 10.00, '2025-05-12 00:00:00', 'credito/pix amarela'),
(871, 914, 4.00, '2025-05-12 00:00:00', 'credito/pix amarela'),
(872, 917, 13.50, '2025-05-12 00:00:00', 'credito/pix amarela'),
(873, 963, 0.10, '2025-05-12 00:00:00', 'credito/pix amarela'),
(874, 485, 3.90, '2025-05-12 00:00:00', 'pix'),
(875, 528, 5.00, '2025-05-12 00:00:00', 'pix'),
(876, 536, 26.90, '2025-05-12 00:00:00', 'pix'),
(877, 551, 11.90, '2025-05-12 00:00:00', 'pix'),
(878, 553, 33.90, '2025-05-12 00:00:00', 'pix'),
(879, 565, 42.80, '2025-05-12 00:00:00', 'pix'),
(880, 574, 11.50, '2025-05-12 00:00:00', 'pix'),
(881, 587, 42.90, '2025-05-12 00:00:00', 'pix'),
(882, 592, 25.90, '2025-05-12 00:00:00', 'pix'),
(883, 593, 7.00, '2025-05-12 00:00:00', 'pix'),
(884, 603, 7.00, '2025-05-12 00:00:00', 'pix'),
(885, 619, 7.00, '2025-05-12 00:00:00', 'pix'),
(886, 651, 51.30, '2025-05-12 00:00:00', 'pix'),
(887, 666, 4.50, '2025-05-12 00:00:00', 'pix'),
(888, 668, 2.00, '2025-05-12 00:00:00', 'pix'),
(889, 677, 16.50, '2025-05-12 00:00:00', 'pix'),
(890, 716, 30.80, '2025-05-12 00:00:00', 'Pagou na maquina amarela - com as taxas ficou 450,92'),
(891, 769, 33.90, '2025-05-12 00:00:00', 'Pagou na maquina amarela - com as taxas ficou 450,92'),
(892, 771, 9.00, '2025-05-12 00:00:00', 'Pagou na maquina amarela - com as taxas ficou 450,92'),
(893, 791, 24.90, '2025-05-12 00:00:00', 'Pagou na maquina amarela - com as taxas ficou 450,92'),
(894, 809, 28.90, '2025-05-12 00:00:00', 'Pagou na maquina amarela - com as taxas ficou 450,92'),
(895, 824, 45.80, '2025-05-12 00:00:00', 'Pagou na maquina amarela - com as taxas ficou 450,92'),
(896, 861, 34.80, '2025-05-12 00:00:00', 'Pagou na maquina amarela - com as taxas ficou 450,92'),
(897, 873, 24.00, '2025-05-12 00:00:00', 'Pagou na maquina amarela - com as taxas ficou 450,92'),
(898, 885, 30.00, '2025-05-12 00:00:00', 'Pagou na maquina amarela - com as taxas ficou 450,92'),
(899, 897, 60.70, '2025-05-12 00:00:00', 'Pagou na maquina amarela - com as taxas ficou 450,92'),
(900, 903, 39.80, '2025-05-12 00:00:00', 'Pagou na maquina amarela - com as taxas ficou 450,92'),
(901, 927, 13.50, '2025-05-12 00:00:00', 'Pagou na maquina amarela - com as taxas ficou 450,92'),
(902, 972, 37.80, '2025-05-12 00:00:00', 'Pagou na maquina amarela - com as taxas ficou 450,92'),
(903, 998, 28.80, '2025-05-12 00:00:00', 'Pagou na maquina amarela - com as taxas ficou 450,92'),
(904, 1005, 18.90, '2025-05-12 00:00:00', 'Pagou na maquina amarela - com as taxas ficou 450,92'),
(905, 1022, 12.99, '2025-05-12 00:00:00', 'Pagou na maquina amarela - com as taxas ficou 450,92'),
(906, 830, 6.90, '2025-05-13 00:00:00', 'pix'),
(907, 870, 19.90, '2025-05-13 00:00:00', 'pix'),
(908, 909, 31.00, '2025-05-13 00:00:00', 'pix'),
(909, 918, 9.00, '2025-05-13 00:00:00', 'pix'),
(910, 932, 10.00, '2025-05-13 00:00:00', 'pix'),
(911, 987, 14.90, '2025-05-13 00:00:00', 'pix'),
(912, 1003, 8.30, '2025-05-13 00:00:00', 'pix'),
(913, 1012, 59.20, '2025-05-14 00:00:00', 'debito'),
(914, 1019, 5.00, '2025-05-14 00:00:00', 'debito'),
(915, 1021, 25.00, '2025-05-14 00:00:00', 'debito'),
(916, 1025, 9.00, '2025-05-14 00:00:00', 'debito'),
(917, 670, 10.00, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(918, 682, 23.00, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(919, 693, 18.90, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(920, 700, 10.00, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(921, 713, 23.90, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(922, 747, 54.00, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(923, 772, 11.00, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(924, 782, 12.50, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(925, 783, 12.50, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(926, 787, 45.00, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(927, 800, 28.90, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(928, 827, 10.00, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(929, 828, 12.00, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(930, 832, 24.90, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(931, 849, 12.50, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(932, 906, 39.00, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(933, 933, 3.50, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(934, 1010, 6.99, '2025-05-14 00:00:00', 'PAGOU NO PIX - MAQUINA AMARELA'),
(935, 31, 10.00, '2025-05-14 00:00:00', 'PIX'),
(936, 1058, 18.79, '2025-05-15 00:00:00', ''),
(937, 1058, 0.10, '2025-05-15 00:00:00', ''),
(938, 500, 9.50, '2025-05-15 00:00:00', 'Credito amarela'),
(939, 941, 13.89, '2025-05-15 00:00:00', 'Credito amarela'),
(940, 1063, 3.00, '2025-05-15 00:00:00', 'dinheiro'),
(941, 647, 0.01, '2025-05-15 00:00:00', 'pix'),
(942, 929, 10.00, '2025-05-15 00:00:00', 'pix'),
(943, 1014, 42.80, '2025-05-15 00:00:00', 'pix'),
(944, 1015, 18.90, '2025-05-15 00:00:00', 'pix'),
(945, 1048, 18.90, '2025-05-15 00:00:00', 'pix'),
(946, 1082, 9.39, '2025-05-15 00:00:00', 'pix'),
(947, 649, 29.40, '2025-05-15 00:00:00', 'DEBITO'),
(948, 659, 8.00, '2025-05-15 00:00:00', 'DEBITO'),
(949, 662, 7.00, '2025-05-15 00:00:00', 'DEBITO'),
(950, 673, 5.60, '2025-05-15 00:00:00', 'DEBITO'),
(951, 1042, 24.00, '2025-05-15 00:00:00', 'NA CONTA DO DOMARIA'),
(952, 673, 17.80, '2025-05-16 00:00:00', 'debito'),
(953, 686, 14.90, '2025-05-16 00:00:00', 'debito');
INSERT INTO `pagamentos` (`id`, `venda_id`, `valor`, `data_pagamento`, `observacao`) VALUES
(954, 691, 4.50, '2025-05-16 00:00:00', 'debito'),
(955, 729, 7.00, '2025-05-16 00:00:00', 'debito'),
(956, 749, 5.80, '2025-05-16 00:00:00', 'debito'),
(957, 880, 3.00, '2025-05-19 00:00:00', 'PIX'),
(958, 967, 18.90, '2025-05-19 00:00:00', 'PIX'),
(959, 968, 17.50, '2025-05-19 00:00:00', 'PIX'),
(960, 975, 3.00, '2025-05-19 00:00:00', 'PIX'),
(961, 980, 6.00, '2025-05-19 00:00:00', 'PIX'),
(962, 1011, 18.90, '2025-05-19 00:00:00', 'PIX'),
(963, 1059, 18.90, '2025-05-19 00:00:00', 'PIX'),
(964, 1091, 35.80, '2025-05-19 00:00:00', 'PIX'),
(965, 1114, 25.00, '2025-05-19 00:00:00', 'PIX'),
(966, 1125, 12.00, '2025-05-19 00:00:00', 'PIX'),
(967, 1102, 4.00, '2025-05-19 00:00:00', 'PIX'),
(968, 1110, 3.00, '2025-05-19 00:00:00', 'PIX'),
(969, 1126, 6.00, '2025-05-19 00:00:00', 'PIX'),
(970, 765, 30.90, '2025-05-20 00:00:00', ''),
(971, 804, 23.90, '2025-05-20 00:00:00', ''),
(972, 805, 13.00, '2025-05-20 00:00:00', ''),
(973, 850, 66.30, '2025-05-20 00:00:00', ''),
(974, 871, 16.00, '2025-05-20 00:00:00', ''),
(975, 892, 22.00, '2025-05-20 00:00:00', ''),
(976, 915, 21.90, '2025-05-20 00:00:00', ''),
(977, 936, 9.00, '2025-05-20 00:00:00', ''),
(978, 946, 10.00, '2025-05-20 00:00:00', ''),
(979, 974, 13.00, '2025-05-20 00:00:00', ''),
(980, 985, 19.00, '2025-05-20 00:00:00', ''),
(981, 1008, 42.80, '2025-05-20 00:00:00', ''),
(982, 1039, 100.70, '2025-05-20 00:00:00', ''),
(983, 749, 9.10, '2025-05-20 00:00:00', 'credito cielo'),
(984, 753, 3.00, '2025-05-20 00:00:00', 'credito cielo'),
(985, 764, 23.40, '2025-05-20 00:00:00', 'credito cielo'),
(986, 794, 14.50, '2025-05-20 00:00:00', 'credito cielo'),
(987, 1033, 5.50, '2025-05-21 00:00:00', 'dinheiro'),
(988, 31, 40.00, '2025-05-22 00:00:00', 'Pagamento pix'),
(989, 1167, 15.90, '2025-05-23 00:00:00', ''),
(990, 845, 5.00, '2025-05-27 00:00:00', 'Credito na amarela'),
(991, 900, 35.90, '2025-05-27 00:00:00', 'Credito na amarela'),
(992, 978, 26.40, '2025-05-27 00:00:00', 'Credito na amarela'),
(993, 1030, 18.90, '2025-05-27 00:00:00', 'Credito na amarela'),
(994, 1141, 18.90, '2025-05-27 00:00:00', 'Credito na amarela'),
(995, 1201, 19.90, '2025-05-27 00:00:00', 'Credito na amarela'),
(996, 31, 30.00, '2025-05-27 00:00:00', 'Pix'),
(997, 1004, 27.90, '2025-05-27 00:00:00', 'cREdito vermelha'),
(998, 1036, 36.90, '2025-05-27 00:00:00', 'cREdito vermelha'),
(999, 1049, 7.00, '2025-05-27 00:00:00', 'cREdito vermelha'),
(1000, 1208, 9.00, '2025-05-27 00:00:00', 'cREdito vermelha'),
(1001, 920, 17.10, '2025-05-27 00:00:00', 'Pix Santander'),
(1002, 944, 9.00, '2025-05-27 00:00:00', 'Pix Santander'),
(1003, 951, 14.50, '2025-05-27 00:00:00', 'Pix Santander'),
(1004, 971, 30.00, '2025-05-27 00:00:00', 'Pix Santander'),
(1005, 991, 4.50, '2025-05-27 00:00:00', 'Pix Santander'),
(1006, 1007, 11.50, '2025-05-27 00:00:00', 'Pix Santander'),
(1007, 1028, 41.90, '2025-05-27 00:00:00', 'Pix Santander'),
(1008, 1050, 10.50, '2025-05-27 00:00:00', 'Pix Santander'),
(1009, 1078, 18.90, '2025-05-27 00:00:00', 'Pix Santander'),
(1010, 1099, 6.00, '2025-05-27 00:00:00', 'Pix Santander'),
(1011, 1162, 27.40, '2025-05-27 00:00:00', 'Pix Santander'),
(1012, 1184, 11.50, '2025-05-27 00:00:00', 'Pix Santander'),
(1013, 1202, 25.90, '2025-05-27 00:00:00', 'Pix Santander'),
(1014, 1171, 78.80, '2025-05-28 00:00:00', 'pIX SANTANDER'),
(1015, 1172, 6.00, '2025-05-28 00:00:00', 'pIX SANTANDER'),
(1016, 1211, 5.00, '2025-05-28 00:00:00', 'pIX SANTANDER'),
(1017, 31, 50.00, '2025-05-29 00:00:00', ''),
(1018, 1003, 10.60, '2025-05-29 00:00:00', 'Pix Santander'),
(1019, 1054, 18.90, '2025-05-29 00:00:00', 'Pix Santander'),
(1020, 1084, 31.80, '2025-05-29 00:00:00', 'Pix Santander'),
(1021, 1145, 18.90, '2025-05-29 00:00:00', 'Pix Santander'),
(1022, 1238, 27.99, '2025-05-29 00:00:00', 'Pix Santander'),
(1023, 31, 30.00, '2025-05-30 00:00:00', ''),
(1024, 1221, 34.40, '2025-06-06 00:00:00', ''),
(1025, 1234, 9.00, '2025-06-06 00:00:00', ''),
(1026, 1261, 25.00, '2025-06-06 00:00:00', '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos_contas`
--

CREATE TABLE `pagamentos_contas` (
  `id` int NOT NULL,
  `conta_id` int NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_pagamento` date NOT NULL,
  `forma_pagamento` varchar(50) NOT NULL,
  `observacoes` text,
  `documento_comprovante` varchar(255) DEFAULT NULL,
  `banco` varchar(100) DEFAULT NULL,
  `agencia` varchar(20) DEFAULT NULL,
  `conta` varchar(30) DEFAULT NULL,
  `usuario_registro` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Acionadores `pagamentos_contas`
--
DELIMITER $$
CREATE TRIGGER `tr_pagamentos_atualizar_conta` AFTER INSERT ON `pagamentos_contas` FOR EACH ROW BEGIN
    UPDATE contas 
    SET valor_pago = (
        SELECT COALESCE(SUM(valor), 0) 
        FROM pagamentos_contas 
        WHERE conta_id = NEW.conta_id
    )
    WHERE id = NEW.conta_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedidos_fornecedores`
--

CREATE TABLE `pedidos_fornecedores` (
  `id` int NOT NULL,
  `fornecedor_id` int NOT NULL,
  `numero_pedido` varchar(50) DEFAULT NULL,
  `data_pedido` date NOT NULL,
  `data_entrega_prevista` date DEFAULT NULL,
  `data_entrega_realizada` date DEFAULT NULL,
  `valor_total` decimal(10,2) DEFAULT '0.00',
  `status` enum('pendente','confirmado','em_transito','entregue','cancelado') DEFAULT 'pendente',
  `forma_pagamento` varchar(100) DEFAULT NULL,
  `observacoes` text,
  `arquivo_pedido` varchar(255) DEFAULT NULL,
  `criado_por` int DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `pedidos_fornecedores`
--

INSERT INTO `pedidos_fornecedores` (`id`, `fornecedor_id`, `numero_pedido`, `data_pedido`, `data_entrega_prevista`, `data_entrega_realizada`, `valor_total`, `status`, `forma_pagamento`, `observacoes`, `arquivo_pedido`, `criado_por`, `data_criacao`, `data_atualizacao`) VALUES
(1, 1, '', '2025-06-07', '2025-06-14', '2025-06-07', 200.00, 'entregue', 'pix', '', NULL, 1, '2025-06-07 01:40:01', '2025-06-07 01:55:48');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ponto_registros`
--

CREATE TABLE `ponto_registros` (
  `id` int NOT NULL,
  `funcionario_id` int DEFAULT NULL,
  `data_registro` date DEFAULT NULL,
  `entrada_manha` time DEFAULT NULL,
  `saida_almoco` time DEFAULT NULL,
  `entrada_tarde` time DEFAULT NULL,
  `saida_final` time DEFAULT NULL,
  `horas_trabalhadas` time DEFAULT NULL,
  `horas_extras` time DEFAULT NULL,
  `observacoes` text,
  `ip_registro` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('incompleto','completo','falta') DEFAULT 'incompleto',
  `registrado_por` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `ponto_registros`
--

INSERT INTO `ponto_registros` (`id`, `funcionario_id`, `data_registro`, `entrada_manha`, `saida_almoco`, `entrada_tarde`, `saida_final`, `horas_trabalhadas`, `horas_extras`, `observacoes`, `ip_registro`, `created_at`, `status`, `registrado_por`, `updated_at`) VALUES
(1, 2, '2025-06-01', '23:51:47', '23:52:24', '23:52:35', '23:53:12', '00:01:14', NULL, '', '::1', '2025-06-01 23:51:47', 'completo', 1, '2025-06-01 23:53:12');

--
-- Acionadores `ponto_registros`
--
DELIMITER $$
CREATE TRIGGER `tr_calcular_horas_ponto_registros` BEFORE UPDATE ON `ponto_registros` FOR EACH ROW BEGIN
    -- Calcular horas trabalhadas quando houver entrada e saída
    IF NEW.entrada_manha IS NOT NULL AND NEW.saida_final IS NOT NULL THEN
        SET NEW.horas_trabalhadas = calcular_horas_ponto(
            NEW.entrada_manha,
            NEW.saida_almoco,
            NEW.entrada_tarde,
            NEW.saida_final
        );
        
        -- Atualizar status para completo
        SET NEW.status = 'completo';
    ELSEIF NEW.entrada_manha IS NOT NULL THEN
        SET NEW.status = 'incompleto';
    ELSE
        SET NEW.status = 'falta';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_calcular_horas_ponto_registros_insert` BEFORE INSERT ON `ponto_registros` FOR EACH ROW BEGIN
    -- Calcular horas trabalhadas quando houver entrada e saída
    IF NEW.entrada_manha IS NOT NULL AND NEW.saida_final IS NOT NULL THEN
        SET NEW.horas_trabalhadas = calcular_horas_ponto(
            NEW.entrada_manha,
            NEW.saida_almoco,
            NEW.entrada_tarde,
            NEW.saida_final
        );
        
        -- Atualizar status para completo
        SET NEW.status = 'completo';
    ELSEIF NEW.entrada_manha IS NOT NULL THEN
        SET NEW.status = 'incompleto';
    ELSE
        SET NEW.status = 'falta';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int NOT NULL,
  `nome` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `descricao` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `valor_venda` decimal(10,2) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `nome`, `descricao`, `valor_venda`, `data_cadastro`) VALUES
(1, 'Macarrão Domaria', '', 18.90, '2025-02-26 00:43:50'),
(2, 'Pão de Queijo ', 'Pequeno', 1.00, '2025-02-26 00:55:09'),
(3, 'Suco Prats', 'Suco Prats varios sabores', 9.90, '2025-02-26 13:26:02'),
(4, 'Suco Necta Nutri', 'Sabores', 3.50, '2025-02-26 13:26:02'),
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
(48, 'Pudim de Leite Condensado', '', 15.00, '2025-02-26 21:40:07'),
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
(77, 'Suco Necta Nutri Cajú', '', 3.50, '2025-02-26 21:40:07'),
(78, 'Suco Necta Nutri MARACUJÁ', '', 3.50, '2025-02-26 21:40:07'),
(79, 'Suco Necta Nutri UVA', '', 3.50, '2025-02-26 21:40:07'),
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
(144, 'Acréscimo de Presunto', '', 2.00, '2025-02-26 21:40:07'),
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
(179, 'Empadão Goiano', '', 14.90, '2025-02-26 21:40:07'),
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
(192, 'Tapioca com Nutella', '', 14.90, '2025-02-26 21:40:07'),
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
(239, 'Picole Bombomzin - Garoto', '', 16.00, '2025-03-04 20:46:33'),
(240, 'Almoço ', '', 18.90, '2025-03-12 16:09:19'),
(241, 'VIRADA PAULISTA', '', 18.90, '2025-03-13 19:38:13'),
(242, 'Cocada Morena', '', 7.00, '2025-03-27 17:41:46'),
(243, 'Pão de Mel', '', 9.00, '2025-03-28 16:52:53'),
(244, 'Coca Cola 2L', '', 14.50, '2025-03-28 17:39:50'),
(245, 'H2O ', '', 8.00, '2025-03-28 17:45:17'),
(246, 'Coca 600ml', '', 8.00, '2025-04-02 18:13:24'),
(247, 'Fanta Maracuja', '', 6.00, '2025-04-09 16:05:40'),
(248, 'Suco de Laranja', '', 9.90, '2025-04-09 16:50:36'),
(249, 'Suco Del Valle Lata', '', 7.00, '2025-04-09 18:09:39'),
(250, 'Mousse ', '', 7.00, '2025-04-10 15:31:03'),
(251, 'Mousse', '', 7.00, '2025-04-10 16:19:18'),
(252, 'Tapioca Queijo Minas', '', 10.00, '2025-04-15 18:51:45'),
(253, 'VALE', '', 1.00, '2025-04-28 22:55:14'),
(254, 'RING TRIPLO CHOCOLATE', '', 9.90, '2025-04-30 20:48:03'),
(255, 'Pão BATATA', '', 10.00, '2025-05-05 21:56:49'),
(256, 'Muffin Chocolate', '', 8.90, '2025-05-06 21:38:59'),
(257, 'Chocolate Look Mais', '', 2.50, '2025-05-08 16:25:27'),
(258, 'Power Ade', '', 8.90, '2025-05-15 17:01:38'),
(259, 'Pipoca Gourmet', '', 15.00, '2025-05-16 16:13:13'),
(260, 'Feijoada', '', 20.00, '2025-05-16 18:45:51'),
(261, 'Pipoca Doce', '', 3.00, '2025-05-22 18:30:31'),
(262, 'Fruta no pote', '', 6.00, '2025-05-22 22:00:04'),
(263, 'Fanta 310ml', '', 6.00, '2025-05-23 17:35:10'),
(264, 'Promocao - Feijoada + Suco Laranja ', '', 25.00, '2025-05-30 16:21:38');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nome` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `senha` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `nivel_acesso` enum('admin','gerente','vendedor','cliente') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'cliente',
  `cliente_id` int DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `data_criacao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_acesso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `nivel_acesso`, `cliente_id`, `ativo`, `data_criacao`, `ultimo_acesso`) VALUES
(1, 'Josuel Menezes', 'jghoste@gmail.com', '$2y$10$QMkPXZDPnm0vFA7gfKPQZ.R28QdxagA4e7pFggqqx0QKUttUsblOe', 'admin', NULL, 1, '2025-02-26 13:50:52', '2025-06-06 20:56:09'),
(2, 'Estefany Menezes', 'niely.sp@gmail.com', '$2y$10$lwFg/1cenf8plI/lAfp9JOXrIEg6cGsP6tUrWl8wRf/Ft8k6drRua', 'vendedor', NULL, 1, '2025-02-26 19:43:02', '2025-02-27 20:03:22'),
(3, 'Rodrigo Santos', 'rodrigosantos@gmail.com', '$2y$10$cnrD.lm.jbkPfyPdhhjCsO2CiDLHXXl2cTatC.3acrssVmhqVxMbG', 'gerente', NULL, 1, '2025-02-26 19:44:01', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `vendas`
--

CREATE TABLE `vendas` (
  `id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `data_venda` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('aberto','pago','cancelado') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT 'aberto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

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
(22, 9, '2025-02-28 16:09:30', 'pago'),
(23, 35, '2025-02-28 21:07:39', 'aberto'),
(24, 30, '2025-02-28 21:12:05', 'pago'),
(25, 51, '2025-02-28 21:16:22', 'pago'),
(26, 52, '2025-02-28 21:19:15', 'pago'),
(27, 53, '2025-02-28 21:23:11', 'pago'),
(28, 50, '2025-02-28 21:24:20', 'pago'),
(29, 45, '2025-02-28 21:27:26', 'aberto'),
(30, 42, '2025-02-28 21:31:55', 'pago'),
(31, 43, '2025-02-28 21:33:01', 'aberto'),
(32, 44, '2025-02-28 21:34:03', 'aberto'),
(33, 15, '2025-02-28 21:36:07', 'pago'),
(34, 20, '2025-02-28 21:36:46', 'pago'),
(35, 19, '2025-02-28 21:37:33', 'pago'),
(36, 17, '2025-02-28 21:39:00', 'pago'),
(37, 18, '2025-02-28 21:39:42', 'pago'),
(38, 22, '2025-02-28 21:42:16', 'pago'),
(39, 23, '2025-02-28 21:42:50', 'pago'),
(40, 24, '2025-02-28 21:43:30', 'pago'),
(41, 25, '2025-02-28 21:44:08', 'pago'),
(42, 26, '2025-02-28 21:45:42', 'pago'),
(43, 36, '2025-02-28 21:46:33', 'pago'),
(44, 38, '2025-02-28 21:47:20', 'pago'),
(45, 39, '2025-02-28 21:47:56', 'pago'),
(46, 41, '2025-02-28 21:48:30', 'pago'),
(47, 3, '2025-02-28 21:51:16', 'pago'),
(48, 54, '2025-02-28 21:55:12', 'aberto'),
(49, 2, '2025-02-28 23:24:22', 'aberto'),
(50, 2, '2025-02-28 23:25:35', 'aberto'),
(51, 2, '2025-02-28 23:32:58', 'aberto'),
(52, 18, '2025-03-05 13:40:11', 'pago'),
(53, 21, '2025-03-05 13:47:56', 'pago'),
(54, 21, '2025-03-05 13:49:48', 'pago'),
(55, 28, '2025-03-05 13:59:41', 'pago'),
(56, 28, '2025-03-05 14:03:15', 'pago'),
(57, 21, '2025-03-05 14:37:40', 'pago'),
(58, 32, '2025-03-05 15:06:46', 'pago'),
(59, 32, '2025-03-05 15:09:15', 'pago'),
(60, 15, '2025-03-05 15:20:37', 'pago'),
(61, 18, '2025-03-05 15:46:42', 'pago'),
(62, 9, '2025-03-05 15:57:20', 'pago'),
(63, 21, '2025-03-05 16:35:27', 'pago'),
(64, 22, '2025-03-05 16:39:45', 'pago'),
(65, 22, '2025-03-05 16:52:44', 'pago'),
(66, 43, '2025-03-05 16:56:18', 'aberto'),
(67, 42, '2025-03-05 16:57:41', 'pago'),
(68, 47, '2025-03-05 17:05:28', 'pago'),
(69, 14, '2025-03-05 17:41:56', 'pago'),
(70, 9, '2025-03-05 17:45:17', 'pago'),
(71, 54, '2025-03-05 18:09:54', 'aberto'),
(72, 28, '2025-03-06 23:38:29', 'pago'),
(73, 46, '2025-03-06 23:46:34', 'pago'),
(74, 46, '2025-03-06 23:47:27', 'pago'),
(75, 23, '2025-03-06 23:51:48', 'pago'),
(76, 24, '2025-03-06 23:53:50', 'pago'),
(77, 11, '2025-03-06 23:57:14', 'pago'),
(78, 17, '2025-03-07 00:07:57', 'pago'),
(79, 41, '2025-03-07 00:10:12', 'pago'),
(80, 18, '2025-03-07 00:13:02', 'aberto'),
(81, 36, '2025-03-07 00:15:22', 'pago'),
(82, 47, '2025-03-07 00:16:21', 'pago'),
(83, 48, '2025-03-07 00:17:22', 'pago'),
(84, 42, '2025-03-07 00:19:19', 'pago'),
(85, 11, '2025-03-07 00:20:27', 'pago'),
(86, 9, '2025-03-07 00:21:59', 'pago'),
(87, 23, '2025-03-07 00:23:06', 'pago'),
(88, 32, '2025-03-07 00:24:32', 'pago'),
(89, 16, '2025-03-07 00:25:43', 'pago'),
(90, 10, '2025-03-07 00:26:51', 'pago'),
(91, 15, '2025-03-07 00:28:29', 'pago'),
(92, 24, '2025-03-07 00:29:37', 'pago'),
(93, 21, '2025-03-07 00:30:54', 'pago'),
(94, 8, '2025-03-07 00:32:07', 'pago'),
(95, 47, '2025-03-07 00:33:38', 'pago'),
(96, 42, '2025-03-07 13:50:06', 'pago'),
(97, 28, '2025-03-07 13:52:16', 'pago'),
(98, 41, '2025-03-07 13:53:08', 'pago'),
(99, 29, '2025-03-07 13:53:50', 'pago'),
(100, 46, '2025-03-07 13:54:42', 'pago'),
(101, 21, '2025-03-07 13:56:38', 'pago'),
(102, 15, '2025-03-07 13:58:31', 'pago'),
(103, 36, '2025-03-07 13:59:37', 'pago'),
(104, 22, '2025-03-07 14:00:31', 'pago'),
(105, 9, '2025-03-07 15:41:39', 'pago'),
(106, 9, '2025-03-07 15:44:37', 'pago'),
(107, 47, '2025-03-07 15:50:02', 'pago'),
(108, 46, '2025-03-07 15:51:13', 'pago'),
(109, 41, '2025-03-07 15:55:40', 'pago'),
(110, 21, '2025-03-07 16:01:03', 'pago'),
(111, 16, '2025-03-07 17:05:52', 'pago'),
(112, 21, '2025-03-07 17:14:14', 'pago'),
(113, 41, '2025-03-07 17:25:39', 'pago'),
(114, 25, '2025-03-07 17:35:24', 'pago'),
(115, 41, '2025-03-07 17:42:15', 'pago'),
(116, 24, '2025-03-07 18:13:12', 'pago'),
(117, 21, '2025-03-07 18:26:27', 'pago'),
(118, 46, '2025-03-07 18:35:12', 'pago'),
(119, 9, '2025-03-07 19:22:36', 'pago'),
(120, 32, '2025-03-07 20:05:37', 'pago'),
(121, 55, '2025-03-07 20:39:13', 'pago'),
(122, 56, '2025-03-07 20:40:59', 'pago'),
(123, 56, '2025-03-07 20:42:53', 'pago'),
(124, 40, '2025-03-07 20:46:04', 'pago'),
(125, 54, '2025-03-07 20:47:02', 'aberto'),
(126, 47, '2025-03-07 20:48:00', 'pago'),
(127, 57, '2025-03-10 10:40:16', 'pago'),
(128, 46, '2025-03-10 11:13:56', 'pago'),
(129, 15, '2025-03-10 11:44:26', 'pago'),
(130, 11, '2025-03-10 11:47:04', 'pago'),
(131, 28, '2025-03-10 12:32:41', 'pago'),
(132, 48, '2025-03-10 13:12:38', 'pago'),
(133, 42, '2025-03-10 13:24:19', 'pago'),
(134, 32, '2025-03-10 14:10:07', 'pago'),
(135, 48, '2025-03-10 14:50:12', 'pago'),
(136, 28, '2025-03-10 14:56:21', 'pago'),
(137, 28, '2025-03-10 15:00:13', 'pago'),
(138, 29, '2025-03-10 15:01:03', 'pago'),
(139, 17, '2025-03-10 15:22:13', 'pago'),
(140, 25, '2025-03-10 15:34:26', 'pago'),
(141, 38, '2025-03-10 16:04:40', 'pago'),
(142, 42, '2025-03-10 16:44:36', 'pago'),
(143, 43, '2025-03-10 16:46:24', 'aberto'),
(144, 16, '2025-03-10 17:35:52', 'pago'),
(145, 16, '2025-03-10 17:40:45', 'pago'),
(146, 54, '2025-03-10 18:22:31', 'aberto'),
(147, 46, '2025-03-10 18:49:12', 'pago'),
(148, 28, '2025-03-10 18:54:20', 'pago'),
(149, 29, '2025-03-10 19:15:05', 'pago'),
(150, 41, '2025-03-10 19:19:46', 'pago'),
(151, 11, '2025-03-10 19:49:34', 'pago'),
(152, 22, '2025-03-10 19:58:42', 'pago'),
(153, 3, '2025-03-10 20:57:19', 'pago'),
(154, 32, '2025-03-11 10:22:22', 'pago'),
(155, 48, '2025-03-11 10:48:02', 'pago'),
(156, 24, '2025-03-11 10:49:19', 'pago'),
(157, 12, '2025-03-11 10:50:56', 'pago'),
(158, 29, '2025-03-11 11:18:08', 'pago'),
(159, 32, '2025-03-11 11:27:51', 'pago'),
(160, 15, '2025-03-11 11:34:27', 'pago'),
(161, 9, '2025-03-11 11:37:01', 'pago'),
(162, 42, '2025-03-11 11:42:43', 'pago'),
(163, 28, '2025-03-11 11:55:22', 'pago'),
(164, 48, '2025-03-11 12:45:58', 'pago'),
(165, 19, '2025-03-11 12:47:23', 'pago'),
(166, 18, '2025-03-11 13:30:41', 'pago'),
(167, 48, '2025-03-11 13:43:20', 'pago'),
(168, 25, '2025-03-11 13:43:42', 'pago'),
(169, 29, '2025-03-11 13:44:28', 'pago'),
(170, 18, '2025-03-11 14:10:06', 'pago'),
(171, 15, '2025-03-11 15:03:51', 'pago'),
(172, 15, '2025-03-11 15:09:45', 'pago'),
(173, 32, '2025-03-11 15:21:51', 'pago'),
(174, 36, '2025-03-11 15:24:57', 'pago'),
(175, 42, '2025-03-11 16:06:26', 'pago'),
(176, 48, '2025-03-11 16:15:38', 'pago'),
(177, 48, '2025-03-11 16:16:52', 'pago'),
(178, 46, '2025-03-11 16:19:31', 'pago'),
(179, 29, '2025-03-11 16:51:04', 'pago'),
(180, 17, '2025-03-11 17:03:04', 'pago'),
(181, 9, '2025-03-11 17:36:54', 'pago'),
(182, 57, '2025-03-11 18:34:57', 'pago'),
(183, 16, '2025-03-11 18:35:39', 'pago'),
(184, 46, '2025-03-11 19:20:31', 'pago'),
(185, 15, '2025-03-11 19:49:23', 'pago'),
(186, 41, '2025-03-11 20:25:18', 'pago'),
(187, 48, '2025-03-11 20:35:23', 'pago'),
(188, 32, '2025-03-12 09:55:16', 'pago'),
(189, 42, '2025-03-12 09:57:07', 'pago'),
(190, 23, '2025-03-12 09:58:33', 'pago'),
(191, 31, '2025-03-12 11:10:39', 'pago'),
(192, 15, '2025-03-12 11:17:37', 'pago'),
(193, 46, '2025-03-12 11:31:32', 'pago'),
(194, 28, '2025-03-12 11:49:36', 'pago'),
(195, 56, '2025-03-12 12:04:26', 'aberto'),
(196, 31, '2025-03-12 13:35:47', 'pago'),
(197, 11, '2025-03-12 13:56:59', 'pago'),
(198, 9, '2025-03-12 14:04:21', 'pago'),
(199, 21, '2025-03-12 14:20:47', 'pago'),
(200, 46, '2025-03-12 14:57:37', 'pago'),
(201, 28, '2025-03-12 15:13:17', 'pago'),
(202, 17, '2025-03-12 15:44:01', 'pago'),
(203, 17, '2025-03-12 15:51:41', 'pago'),
(204, 28, '2025-03-12 15:55:03', 'pago'),
(205, 48, '2025-03-12 16:09:52', 'pago'),
(206, 32, '2025-03-12 16:11:26', 'pago'),
(207, 42, '2025-03-12 16:15:53', 'pago'),
(208, 26, '2025-03-12 16:18:11', 'pago'),
(209, 8, '2025-03-12 16:23:07', 'pago'),
(210, 9, '2025-03-12 16:27:33', 'pago'),
(211, 32, '2025-03-12 16:39:08', 'pago'),
(212, 15, '2025-03-12 16:44:39', 'pago'),
(213, 29, '2025-03-12 16:49:43', 'pago'),
(214, 26, '2025-03-12 17:12:09', 'pago'),
(215, 16, '2025-03-12 17:14:14', 'pago'),
(216, 19, '2025-03-12 17:24:47', 'aberto'),
(217, 18, '2025-03-12 17:39:18', 'pago'),
(218, 25, '2025-03-12 17:42:46', 'pago'),
(219, 18, '2025-03-12 17:59:49', 'pago'),
(220, 48, '2025-03-12 18:44:28', 'pago'),
(221, 32, '2025-03-12 18:46:07', 'pago'),
(222, 21, '2025-03-12 19:15:39', 'pago'),
(223, 18, '2025-03-12 19:40:29', 'pago'),
(224, 29, '2025-03-12 20:55:58', 'pago'),
(225, 48, '2025-03-13 10:28:30', 'pago'),
(226, 10, '2025-03-13 10:29:18', 'pago'),
(227, 10, '2025-03-13 10:30:50', 'pago'),
(228, 15, '2025-03-13 10:44:13', 'pago'),
(229, 47, '2025-03-13 10:45:19', 'pago'),
(230, 47, '2025-03-13 13:37:02', 'pago'),
(231, 47, '2025-03-13 13:37:32', 'pago'),
(232, 29, '2025-03-13 14:48:13', 'pago'),
(233, 47, '2025-03-13 14:51:30', 'pago'),
(234, 10, '2025-03-13 18:19:48', 'pago'),
(235, 16, '2025-03-13 18:21:54', 'pago'),
(236, 29, '2025-03-13 18:25:01', 'pago'),
(237, 24, '2025-03-13 18:26:19', 'aberto'),
(238, 26, '2025-03-13 18:27:37', 'pago'),
(239, 17, '2025-03-13 18:29:30', 'pago'),
(240, 28, '2025-03-13 18:30:56', 'pago'),
(241, 26, '2025-03-13 19:10:35', 'pago'),
(242, 14, '2025-03-13 19:32:54', 'pago'),
(243, 25, '2025-03-13 19:38:30', 'pago'),
(244, 46, '2025-03-13 19:39:05', 'pago'),
(245, 46, '2025-03-13 19:44:00', 'pago'),
(246, 46, '2025-03-13 19:44:30', 'pago'),
(247, 42, '2025-03-13 19:50:49', 'pago'),
(248, 32, '2025-03-13 19:53:52', 'aberto'),
(249, 18, '2025-03-13 19:57:18', 'pago'),
(250, 10, '2025-03-13 19:59:43', 'pago'),
(251, 26, '2025-03-13 20:01:48', 'pago'),
(252, 17, '2025-03-13 20:15:07', 'pago'),
(253, 46, '2025-03-13 20:25:35', 'pago'),
(254, 42, '2025-03-13 20:27:46', 'pago'),
(255, 41, '2025-03-13 20:28:25', 'pago'),
(256, 15, '2025-03-13 20:29:06', 'pago'),
(257, 57, '2025-03-14 10:38:59', 'pago'),
(258, 15, '2025-03-14 12:00:00', 'pago'),
(259, 19, '2025-03-14 12:05:09', 'pago'),
(260, 19, '2025-03-14 12:17:37', 'pago'),
(261, 18, '2025-03-14 13:04:57', 'pago'),
(262, 17, '2025-03-14 13:07:56', 'pago'),
(263, 25, '2025-03-14 13:40:16', 'pago'),
(264, 42, '2025-03-14 13:43:41', 'pago'),
(265, 28, '2025-03-14 13:56:20', 'pago'),
(266, 42, '2025-03-14 14:06:19', 'pago'),
(267, 25, '2025-03-14 15:12:40', 'pago'),
(268, 36, '2025-03-14 15:28:08', 'pago'),
(269, 15, '2025-03-14 15:51:04', 'pago'),
(270, 48, '2025-03-14 16:01:55', 'aberto'),
(271, 19, '2025-03-14 16:10:59', 'pago'),
(272, 55, '2025-03-14 17:19:36', 'pago'),
(273, 36, '2025-03-14 17:55:17', 'pago'),
(274, 29, '2025-03-14 17:56:23', 'pago'),
(275, 32, '2025-03-14 17:57:46', 'aberto'),
(276, 28, '2025-03-14 17:59:37', 'pago'),
(277, 42, '2025-03-14 18:08:05', 'pago'),
(278, 46, '2025-03-14 18:09:54', 'pago'),
(279, 40, '2025-03-14 18:21:11', 'pago'),
(280, 41, '2025-03-14 18:58:18', 'pago'),
(281, 24, '2025-03-14 19:13:01', 'pago'),
(282, 11, '2025-03-14 19:21:28', 'pago'),
(283, 19, '2025-03-14 19:48:16', 'pago'),
(284, 17, '2025-03-14 19:48:44', 'pago'),
(285, 16, '2025-03-14 19:49:25', 'pago'),
(286, 15, '2025-03-14 20:06:30', 'pago'),
(287, 19, '2025-03-14 20:07:58', 'pago'),
(288, 48, '2025-03-15 11:29:08', 'pago'),
(289, 36, '2025-03-15 12:22:36', 'pago'),
(290, 57, '2025-03-17 10:33:25', 'pago'),
(291, 40, '2025-03-17 10:57:36', 'pago'),
(292, 18, '2025-03-17 11:54:00', 'pago'),
(293, 9, '2025-03-17 13:01:28', 'pago'),
(294, 19, '2025-03-17 13:28:42', 'pago'),
(295, 18, '2025-03-17 13:29:24', 'pago'),
(296, 14, '2025-03-17 15:08:27', 'pago'),
(297, 32, '2025-03-17 15:15:40', 'aberto'),
(298, 29, '2025-03-17 15:34:09', 'pago'),
(299, 29, '2025-03-17 15:35:06', 'pago'),
(300, 56, '2025-03-17 15:36:32', 'pago'),
(301, 46, '2025-03-17 15:52:04', 'pago'),
(302, 47, '2025-03-17 15:53:51', 'pago'),
(303, 36, '2025-03-17 15:58:07', 'pago'),
(304, 42, '2025-03-17 15:59:20', 'pago'),
(305, 17, '2025-03-17 16:05:47', 'pago'),
(306, 47, '2025-03-17 16:16:14', 'pago'),
(307, 9, '2025-03-17 16:23:18', 'pago'),
(308, 16, '2025-03-17 16:52:32', 'pago'),
(309, 16, '2025-03-17 16:54:47', 'pago'),
(310, 46, '2025-03-17 17:11:05', 'pago'),
(311, 18, '2025-03-17 17:12:24', 'pago'),
(312, 22, '2025-03-17 18:36:07', 'pago'),
(313, 25, '2025-03-17 18:54:22', 'pago'),
(314, 25, '2025-03-17 18:55:02', 'pago'),
(315, 47, '2025-03-17 18:55:54', 'pago'),
(316, 10, '2025-03-17 19:38:35', 'pago'),
(317, 9, '2025-03-18 09:57:16', 'pago'),
(318, 47, '2025-03-18 09:58:47', 'pago'),
(319, 11, '2025-03-18 11:14:13', 'pago'),
(320, 9, '2025-03-18 11:18:59', 'pago'),
(321, 42, '2025-03-18 11:21:15', 'pago'),
(322, 9, '2025-03-18 11:22:03', 'pago'),
(323, 17, '2025-03-18 11:53:50', 'pago'),
(324, 25, '2025-03-18 12:00:02', 'pago'),
(325, 26, '2025-03-18 13:47:16', 'pago'),
(326, 16, '2025-03-18 16:15:02', 'pago'),
(327, 24, '2025-03-18 16:32:08', 'pago'),
(328, 25, '2025-03-18 16:40:49', 'pago'),
(329, 48, '2025-03-18 16:58:54', 'pago'),
(330, 47, '2025-03-18 17:45:06', 'pago'),
(331, 18, '2025-03-18 18:01:37', 'pago'),
(332, 10, '2025-03-18 18:04:52', 'pago'),
(333, 46, '2025-03-18 18:06:18', 'pago'),
(334, 32, '2025-03-18 18:07:14', 'aberto'),
(335, 29, '2025-03-18 18:10:13', 'pago'),
(336, 11, '2025-03-18 18:12:53', 'pago'),
(337, 17, '2025-03-18 18:24:35', 'pago'),
(338, 17, '2025-03-18 18:46:39', 'pago'),
(339, 9, '2025-03-18 19:22:18', 'pago'),
(340, 18, '2025-03-18 19:37:26', 'pago'),
(341, 40, '2025-03-18 19:42:13', 'pago'),
(342, 48, '2025-03-18 19:43:57', 'pago'),
(343, 42, '2025-03-19 09:57:51', 'pago'),
(344, 21, '2025-03-19 09:59:10', 'pago'),
(345, 3, '2025-03-19 10:08:54', 'pago'),
(346, 46, '2025-03-19 11:13:29', 'pago'),
(347, 29, '2025-03-19 11:17:11', 'pago'),
(348, 9, '2025-03-19 11:31:42', 'pago'),
(349, 14, '2025-03-19 11:32:16', 'pago'),
(350, 18, '2025-03-19 11:38:11', 'pago'),
(351, 32, '2025-03-19 12:05:38', 'aberto'),
(352, 15, '2025-03-19 15:57:16', 'pago'),
(353, 18, '2025-03-19 15:59:23', 'pago'),
(354, 9, '2025-03-19 16:01:07', 'pago'),
(355, 46, '2025-03-19 16:24:31', 'pago'),
(356, 10, '2025-03-19 16:26:07', 'pago'),
(357, 15, '2025-03-19 16:27:57', 'pago'),
(358, 19, '2025-03-19 17:03:57', 'pago'),
(359, 29, '2025-03-19 17:06:01', 'pago'),
(360, 16, '2025-03-19 17:11:51', 'pago'),
(361, 48, '2025-03-19 17:27:33', 'pago'),
(362, 17, '2025-03-19 17:30:34', 'pago'),
(363, 48, '2025-03-19 17:39:52', 'pago'),
(364, 9, '2025-03-19 18:10:21', 'pago'),
(365, 28, '2025-03-19 18:11:36', 'pago'),
(366, 47, '2025-03-19 18:25:17', 'pago'),
(367, 46, '2025-03-19 19:01:04', 'pago'),
(368, 24, '2025-03-19 19:15:42', 'pago'),
(369, 23, '2025-03-19 19:24:06', 'aberto'),
(370, 15, '2025-03-19 19:41:31', 'pago'),
(371, 24, '2025-03-19 19:43:46', 'pago'),
(372, 17, '2025-03-19 19:49:29', 'pago'),
(373, 14, '2025-03-19 20:14:59', 'pago'),
(374, 15, '2025-03-19 20:20:36', 'pago'),
(375, 42, '2025-03-19 20:23:56', 'pago'),
(376, 10, '2025-03-19 20:30:50', 'pago'),
(377, 16, '2025-03-19 20:37:09', 'pago'),
(378, 48, '2025-03-20 09:53:18', 'pago'),
(379, 18, '2025-03-20 11:17:03', 'pago'),
(380, 46, '2025-03-20 11:19:20', 'pago'),
(381, 15, '2025-03-20 11:20:38', 'pago'),
(382, 31, '2025-03-20 11:35:21', 'pago'),
(383, 42, '2025-03-20 11:37:19', 'pago'),
(384, 11, '2025-03-20 12:23:14', 'pago'),
(385, 47, '2025-03-20 13:20:03', 'pago'),
(386, 25, '2025-03-20 13:28:00', 'pago'),
(387, 57, '2025-03-20 13:43:52', 'pago'),
(388, 57, '2025-03-20 13:44:12', 'pago'),
(389, 41, '2025-03-20 13:51:55', 'pago'),
(390, 46, '2025-03-20 16:59:36', 'pago'),
(391, 17, '2025-03-20 17:01:56', 'aberto'),
(392, 16, '2025-03-20 17:08:34', 'pago'),
(393, 24, '2025-03-20 17:21:17', 'pago'),
(394, 10, '2025-03-20 17:55:06', 'pago'),
(395, 18, '2025-03-20 17:57:41', 'pago'),
(396, 31, '2025-03-20 18:04:35', 'pago'),
(397, 28, '2025-03-20 18:10:42', 'pago'),
(398, 11, '2025-03-20 18:14:06', 'pago'),
(399, 36, '2025-03-20 18:49:02', 'pago'),
(400, 15, '2025-03-20 19:08:32', 'pago'),
(401, 42, '2025-03-21 12:06:20', 'pago'),
(402, 14, '2025-03-21 12:19:10', 'pago'),
(403, 9, '2025-03-21 12:30:48', 'pago'),
(404, 41, '2025-03-21 13:15:20', 'pago'),
(405, 38, '2025-03-21 13:15:49', 'pago'),
(406, 19, '2025-03-21 13:16:58', 'pago'),
(407, 32, '2025-03-21 13:18:11', 'aberto'),
(408, 21, '2025-03-21 14:15:03', 'pago'),
(409, 48, '2025-03-21 14:16:03', 'pago'),
(410, 15, '2025-03-21 14:32:35', 'pago'),
(411, 18, '2025-03-21 15:41:48', 'pago'),
(412, 9, '2025-03-21 15:46:57', 'pago'),
(413, 14, '2025-03-21 15:47:52', 'pago'),
(414, 48, '2025-03-21 17:02:13', 'pago'),
(415, 24, '2025-03-21 17:03:26', 'pago'),
(416, 16, '2025-03-21 17:04:24', 'pago'),
(417, 41, '2025-03-21 17:21:23', 'pago'),
(418, 40, '2025-03-21 19:47:15', 'pago'),
(419, 29, '2025-03-21 19:48:14', 'pago'),
(420, 15, '2025-03-21 20:21:11', 'pago'),
(421, 42, '2025-03-21 20:21:56', 'pago'),
(422, 28, '2025-03-21 20:22:50', 'pago'),
(423, 19, '2025-03-21 20:23:29', 'pago'),
(424, 15, '2025-03-24 10:49:14', 'pago'),
(425, 57, '2025-03-24 10:52:12', 'pago'),
(426, 11, '2025-03-24 11:32:29', 'pago'),
(427, 21, '2025-03-24 13:38:41', 'pago'),
(428, 32, '2025-03-24 13:39:53', 'aberto'),
(429, 12, '2025-03-24 13:43:48', 'pago'),
(430, 10, '2025-03-24 13:44:55', 'pago'),
(431, 48, '2025-03-24 14:01:05', 'pago'),
(432, 18, '2025-03-24 14:05:07', 'pago'),
(433, 28, '2025-03-24 14:13:46', 'pago'),
(434, 21, '2025-03-24 14:14:49', 'pago'),
(435, 16, '2025-03-24 17:04:53', 'pago'),
(436, 56, '2025-03-24 17:52:07', 'pago'),
(437, 18, '2025-03-24 18:11:14', 'pago'),
(438, 24, '2025-03-24 19:29:52', 'pago'),
(439, 11, '2025-03-24 19:31:48', 'pago'),
(440, 12, '2025-03-24 19:32:52', 'pago'),
(441, 48, '2025-03-24 19:34:34', 'pago'),
(442, 41, '2025-03-24 19:35:27', 'pago'),
(443, 39, '2025-03-24 19:37:47', 'pago'),
(444, 16, '2025-03-24 19:51:52', 'pago'),
(445, 41, '2025-03-24 19:53:07', 'pago'),
(446, 9, '2025-03-24 20:12:54', 'pago'),
(447, 31, '2025-03-24 20:14:36', 'pago'),
(448, 38, '2025-03-24 20:15:53', 'pago'),
(449, 15, '2025-03-24 20:17:30', 'pago'),
(450, 15, '2025-03-24 20:23:47', 'pago'),
(451, 21, '2025-03-24 20:28:50', 'pago'),
(452, 57, '2025-03-25 11:02:30', 'pago'),
(453, 10, '2025-03-25 13:47:42', 'pago'),
(454, 12, '2025-03-25 13:49:38', 'pago'),
(455, 18, '2025-03-25 13:50:52', 'pago'),
(456, 19, '2025-03-25 14:40:18', 'pago'),
(457, 15, '2025-03-25 15:45:12', 'pago'),
(458, 16, '2025-03-25 16:23:07', 'pago'),
(459, 36, '2025-03-25 17:15:27', 'pago'),
(460, 15, '2025-03-25 17:49:23', 'pago'),
(461, 9, '2025-03-25 19:52:41', 'pago'),
(462, 21, '2025-03-25 19:54:03', 'pago'),
(463, 17, '2025-03-25 19:55:18', 'pago'),
(464, 31, '2025-03-25 19:57:33', 'pago'),
(465, 42, '2025-03-25 20:00:03', 'pago'),
(466, 18, '2025-03-25 20:09:39', 'pago'),
(467, 16, '2025-03-25 20:11:21', 'pago'),
(468, 10, '2025-03-25 20:27:18', 'pago'),
(469, 46, '2025-03-25 20:31:40', 'pago'),
(470, 15, '2025-03-25 20:39:18', 'pago'),
(471, 55, '2025-03-25 20:40:34', 'pago'),
(472, 19, '2025-03-25 20:41:22', 'pago'),
(473, 57, '2025-03-26 10:18:59', 'pago'),
(474, 23, '2025-03-26 11:21:05', 'aberto'),
(475, 8, '2025-03-26 12:52:31', 'pago'),
(476, 11, '2025-03-26 13:27:15', 'pago'),
(477, 10, '2025-03-26 13:59:46', 'pago'),
(478, 28, '2025-03-26 14:45:40', 'pago'),
(479, 28, '2025-03-26 14:46:32', 'pago'),
(480, 9, '2025-03-26 15:30:19', 'pago'),
(481, 15, '2025-03-26 15:31:23', 'pago'),
(482, 38, '2025-03-26 15:32:31', 'pago'),
(483, 18, '2025-03-26 15:52:01', 'pago'),
(484, 25, '2025-03-26 15:53:04', 'pago'),
(485, 42, '2025-03-26 15:56:44', 'pago'),
(486, 15, '2025-03-26 17:19:16', 'pago'),
(487, 48, '2025-03-26 17:21:57', 'pago'),
(488, 16, '2025-03-26 17:22:53', 'pago'),
(489, 18, '2025-03-26 17:23:58', 'pago'),
(490, 41, '2025-03-26 17:33:24', 'pago'),
(491, 55, '2025-03-26 17:34:03', 'pago'),
(492, 41, '2025-03-26 17:39:40', 'pago'),
(493, 31, '2025-03-26 17:40:50', 'pago'),
(494, 18, '2025-03-26 19:08:10', 'pago'),
(495, 11, '2025-03-26 19:52:14', 'pago'),
(496, 8, '2025-03-26 19:54:38', 'pago'),
(497, 9, '2025-03-26 19:55:47', 'pago'),
(498, 14, '2025-03-27 12:15:42', 'pago'),
(499, 8, '2025-03-27 12:16:51', 'pago'),
(500, 22, '2025-03-27 12:18:04', 'pago'),
(501, 18, '2025-03-27 12:47:02', 'pago'),
(502, 16, '2025-03-27 17:07:52', 'pago'),
(503, 46, '2025-03-27 17:10:16', 'pago'),
(504, 46, '2025-03-27 17:10:17', 'pago'),
(505, 46, '2025-03-27 17:10:17', 'pago'),
(506, 31, '2025-03-27 17:12:35', 'pago'),
(507, 21, '2025-03-27 17:18:50', 'pago'),
(508, 18, '2025-03-27 17:21:12', 'pago'),
(509, 8, '2025-03-27 17:22:59', 'pago'),
(510, 19, '2025-03-27 17:25:12', 'pago'),
(511, 28, '2025-03-27 17:33:17', 'pago'),
(512, 55, '2025-03-27 17:34:18', 'pago'),
(513, 21, '2025-03-27 17:44:46', 'pago'),
(514, 18, '2025-03-27 17:45:31', 'pago'),
(515, 19, '2025-03-27 18:21:06', 'pago'),
(516, 21, '2025-03-27 18:23:51', 'pago'),
(517, 41, '2025-03-27 18:42:12', 'pago'),
(518, 48, '2025-03-27 19:17:32', 'pago'),
(519, 48, '2025-03-27 19:26:56', 'pago'),
(520, 3, '2025-03-27 21:07:10', 'pago'),
(521, 15, '2025-03-28 09:53:29', 'pago'),
(522, 21, '2025-03-28 09:54:48', 'pago'),
(523, 11, '2025-03-28 12:04:08', 'pago'),
(524, 8, '2025-03-28 12:04:51', 'pago'),
(525, 41, '2025-03-28 13:42:16', 'pago'),
(526, 18, '2025-03-28 14:15:40', 'pago'),
(527, 19, '2025-03-28 14:17:08', 'pago'),
(528, 42, '2025-03-28 14:24:38', 'pago'),
(529, 11, '2025-03-28 15:50:36', 'pago'),
(530, 8, '2025-03-28 15:51:25', 'pago'),
(531, 18, '2025-03-28 16:53:54', 'pago'),
(532, 9, '2025-03-28 17:21:35', 'pago'),
(533, 15, '2025-03-28 17:22:35', 'pago'),
(534, 31, '2025-03-28 17:24:43', 'pago'),
(535, 41, '2025-03-28 17:25:56', 'pago'),
(536, 42, '2025-03-28 17:33:31', 'pago'),
(537, 19, '2025-03-28 17:41:19', 'pago'),
(538, 24, '2025-03-28 17:43:51', 'pago'),
(539, 28, '2025-03-28 17:45:43', 'pago'),
(540, 17, '2025-03-28 18:14:19', 'pago'),
(541, 16, '2025-03-28 18:17:02', 'pago'),
(542, 41, '2025-03-28 18:55:25', 'pago'),
(543, 41, '2025-03-28 18:55:57', 'aberto'),
(544, 15, '2025-03-28 19:13:24', 'pago'),
(545, 15, '2025-03-28 19:14:21', 'pago'),
(546, 36, '2025-03-28 19:21:37', 'pago'),
(547, 40, '2025-03-28 20:09:40', 'pago'),
(548, 11, '2025-03-28 20:19:06', 'pago'),
(549, 19, '2025-03-28 20:28:58', 'pago'),
(550, 18, '2025-03-31 13:18:47', 'pago'),
(551, 42, '2025-03-31 13:21:55', 'pago'),
(552, 38, '2025-03-31 13:29:52', 'pago'),
(553, 42, '2025-03-31 13:37:47', 'pago'),
(554, 15, '2025-03-31 13:39:59', 'pago'),
(555, 9, '2025-03-31 13:55:43', 'pago'),
(556, 8, '2025-03-31 14:07:58', 'pago'),
(557, 8, '2025-03-31 14:07:58', 'pago'),
(558, 26, '2025-03-31 15:45:27', 'pago'),
(559, 14, '2025-03-31 16:43:10', 'pago'),
(560, 25, '2025-03-31 16:44:28', 'pago'),
(561, 38, '2025-03-31 16:47:05', 'pago'),
(562, 8, '2025-03-31 17:02:46', 'aberto'),
(563, 16, '2025-03-31 17:19:54', 'pago'),
(564, 18, '2025-03-31 18:06:19', 'pago'),
(565, 42, '2025-03-31 18:09:42', 'pago'),
(566, 9, '2025-03-31 18:11:44', 'pago'),
(567, 28, '2025-03-31 20:13:11', 'pago'),
(568, 24, '2025-03-31 20:15:26', 'pago'),
(569, 10, '2025-03-31 20:44:53', 'pago'),
(570, 8, '2025-04-01 11:40:12', 'pago'),
(571, 25, '2025-04-01 11:52:52', 'pago'),
(572, 9, '2025-04-01 11:57:38', 'pago'),
(573, 57, '2025-04-01 12:13:46', 'pago'),
(574, 42, '2025-04-01 12:49:17', 'pago'),
(575, 8, '2025-04-01 14:28:53', 'pago'),
(576, 19, '2025-04-01 18:54:22', 'pago'),
(577, 29, '2025-04-01 18:55:24', 'pago'),
(578, 17, '2025-04-01 18:57:10', 'pago'),
(579, 40, '2025-04-01 18:57:52', 'pago'),
(580, 9, '2025-04-01 18:59:56', 'pago'),
(581, 10, '2025-04-01 19:02:31', 'pago'),
(582, 41, '2025-04-01 19:03:18', 'pago'),
(583, 28, '2025-04-01 19:04:24', 'pago'),
(584, 11, '2025-04-01 19:11:39', 'pago'),
(585, 16, '2025-04-01 20:17:47', 'pago'),
(586, 19, '2025-04-01 20:32:34', 'pago'),
(587, 42, '2025-04-02 10:16:51', 'pago'),
(588, 31, '2025-04-02 10:44:09', 'pago'),
(589, 15, '2025-04-02 10:54:08', 'pago'),
(590, 17, '2025-04-02 12:48:28', 'pago'),
(591, 8, '2025-04-02 14:23:29', 'pago'),
(592, 42, '2025-04-02 15:36:56', 'pago'),
(593, 42, '2025-04-02 15:39:35', 'pago'),
(594, 21, '2025-04-02 18:08:36', 'pago'),
(595, 31, '2025-04-02 18:13:46', 'pago'),
(596, 15, '2025-04-02 18:16:19', 'pago'),
(597, 14, '2025-04-02 18:19:29', 'pago'),
(598, 47, '2025-04-02 18:22:14', 'pago'),
(599, 41, '2025-04-02 18:24:56', 'pago'),
(600, 38, '2025-04-02 18:25:47', 'pago'),
(601, 18, '2025-04-02 18:27:40', 'pago'),
(602, 41, '2025-04-02 18:39:25', 'pago'),
(603, 42, '2025-04-02 19:38:52', 'pago'),
(604, 15, '2025-04-02 19:40:13', 'pago'),
(605, 8, '2025-04-02 20:33:00', 'pago'),
(606, 57, '2025-04-03 11:59:35', 'pago'),
(607, 15, '2025-04-03 17:27:16', 'pago'),
(608, 31, '2025-04-03 17:28:30', 'aberto'),
(609, 18, '2025-04-03 17:30:39', 'pago'),
(610, 11, '2025-04-03 17:49:18', 'pago'),
(611, 8, '2025-04-03 17:50:36', 'pago'),
(612, 28, '2025-04-03 18:02:41', 'aberto'),
(613, 41, '2025-04-03 18:04:26', 'pago'),
(614, 8, '2025-04-03 18:05:55', 'pago'),
(615, 19, '2025-04-03 18:06:40', 'pago'),
(616, 17, '2025-04-03 18:07:30', 'pago'),
(617, 46, '2025-04-03 18:11:53', 'pago'),
(618, 9, '2025-04-04 11:48:34', 'pago'),
(619, 42, '2025-04-04 13:00:09', 'pago'),
(620, 26, '2025-04-04 15:00:21', 'pago'),
(621, 26, '2025-04-04 15:00:21', 'pago'),
(622, 26, '2025-04-04 15:00:21', 'pago'),
(623, 26, '2025-04-04 15:00:21', 'pago'),
(624, 26, '2025-04-04 15:00:21', 'pago'),
(625, 26, '2025-04-04 15:00:22', 'pago'),
(626, 26, '2025-04-04 15:00:22', 'pago'),
(627, 26, '2025-04-04 15:00:23', 'pago'),
(628, 26, '2025-04-04 15:00:23', 'pago'),
(629, 26, '2025-04-04 15:00:23', 'pago'),
(630, 26, '2025-04-04 15:00:23', 'pago'),
(631, 26, '2025-04-04 15:00:24', 'pago'),
(632, 15, '2025-04-04 16:00:13', 'pago'),
(633, 15, '2025-04-04 16:38:10', 'pago'),
(634, 17, '2025-04-04 16:39:21', 'pago'),
(635, 38, '2025-04-04 16:59:15', 'pago'),
(636, 46, '2025-04-04 17:00:07', 'pago'),
(637, 19, '2025-04-04 17:01:47', 'pago'),
(638, 12, '2025-04-04 17:08:24', 'pago'),
(639, 14, '2025-04-04 17:14:38', 'pago'),
(640, 28, '2025-04-04 17:16:17', 'pago'),
(641, 18, '2025-04-04 17:19:39', 'pago'),
(642, 47, '2025-04-04 17:20:48', 'pago'),
(643, 28, '2025-04-04 17:24:39', 'pago'),
(644, 46, '2025-04-04 17:28:38', 'pago'),
(645, 41, '2025-04-04 17:33:48', 'pago'),
(646, 29, '2025-04-04 17:35:59', 'pago'),
(647, 39, '2025-04-04 17:37:23', 'pago'),
(648, 19, '2025-04-04 17:38:21', 'pago'),
(649, 31, '2025-04-04 17:39:56', 'pago'),
(650, 10, '2025-04-04 17:42:33', 'pago'),
(651, 42, '2025-04-04 17:43:20', 'pago'),
(652, 24, '2025-04-04 17:44:09', 'aberto'),
(653, 9, '2025-04-04 17:51:06', 'pago'),
(654, 40, '2025-04-04 17:52:46', 'pago'),
(655, 28, '2025-04-04 18:15:52', 'pago'),
(656, 25, '2025-04-04 18:33:36', 'pago'),
(657, 29, '2025-04-05 11:26:59', 'pago'),
(658, 29, '2025-04-05 11:37:05', 'pago'),
(659, 31, '2025-04-05 11:38:03', 'pago'),
(660, 3, '2025-04-05 15:32:40', 'pago'),
(661, 15, '2025-04-07 10:42:23', 'pago'),
(662, 31, '2025-04-07 10:43:46', 'pago'),
(663, 28, '2025-04-07 11:00:36', 'pago'),
(664, 29, '2025-04-07 11:16:26', 'pago'),
(665, 32, '2025-04-07 12:42:14', 'aberto'),
(666, 42, '2025-04-07 12:43:08', 'pago'),
(667, 17, '2025-04-07 12:43:53', 'pago'),
(668, 42, '2025-04-07 12:58:25', 'pago'),
(669, 17, '2025-04-07 16:17:54', 'pago'),
(670, 16, '2025-04-07 16:27:10', 'pago'),
(671, 36, '2025-04-07 16:29:51', 'pago'),
(672, 28, '2025-04-07 16:30:54', 'pago'),
(673, 31, '2025-04-07 16:31:48', 'pago'),
(674, 41, '2025-04-07 16:33:04', 'pago'),
(675, 23, '2025-04-07 16:33:38', 'aberto'),
(676, 28, '2025-04-07 16:51:48', 'pago'),
(677, 42, '2025-04-07 17:06:48', 'aberto'),
(678, 24, '2025-04-07 18:37:19', 'aberto'),
(679, 32, '2025-04-07 18:52:54', 'aberto'),
(680, 41, '2025-04-07 19:46:26', 'pago'),
(681, 46, '2025-04-07 20:00:52', 'pago'),
(682, 16, '2025-04-07 20:28:20', 'pago'),
(683, 18, '2025-04-07 20:33:56', 'pago'),
(684, 12, '2025-04-07 20:46:39', 'pago'),
(685, 10, '2025-04-07 20:47:36', 'pago'),
(686, 31, '2025-04-08 11:03:07', 'pago'),
(687, 41, '2025-04-08 14:20:22', 'pago'),
(688, 21, '2025-04-08 19:09:52', 'pago'),
(689, 46, '2025-04-08 19:12:17', 'pago'),
(690, 17, '2025-04-08 19:14:01', 'pago'),
(691, 31, '2025-04-08 19:16:00', 'pago'),
(692, 42, '2025-04-08 19:31:02', 'aberto'),
(693, 16, '2025-04-08 19:32:39', 'pago'),
(694, 21, '2025-04-08 19:34:25', 'pago'),
(695, 21, '2025-04-08 19:34:30', 'pago'),
(696, 21, '2025-04-08 19:34:33', 'pago'),
(697, 21, '2025-04-08 19:34:35', 'pago'),
(698, 21, '2025-04-08 19:34:35', 'aberto'),
(699, 41, '2025-04-08 19:35:35', 'pago'),
(700, 16, '2025-04-08 19:56:00', 'pago'),
(701, 32, '2025-04-08 20:05:18', 'aberto'),
(702, 28, '2025-04-08 20:07:23', 'pago'),
(703, 28, '2025-04-08 20:07:23', 'pago'),
(704, 17, '2025-04-08 20:09:00', 'pago'),
(705, 18, '2025-04-08 20:09:59', 'pago'),
(706, 48, '2025-04-09 10:19:53', 'pago'),
(707, 38, '2025-04-09 12:12:22', 'pago'),
(708, 46, '2025-04-09 12:26:59', 'pago'),
(709, 41, '2025-04-09 16:07:26', 'pago'),
(710, 23, '2025-04-09 16:08:05', 'aberto'),
(711, 46, '2025-04-09 16:27:21', 'pago'),
(712, 26, '2025-04-09 16:50:58', 'pago'),
(713, 16, '2025-04-09 17:26:12', 'pago'),
(714, 32, '2025-04-09 17:27:47', 'aberto'),
(715, 10, '2025-04-09 17:29:47', 'pago'),
(716, 19, '2025-04-09 17:31:26', 'pago'),
(717, 28, '2025-04-09 17:32:16', 'pago'),
(718, 15, '2025-04-09 17:33:01', 'pago'),
(719, 48, '2025-04-09 17:35:00', 'pago'),
(720, 18, '2025-04-09 17:37:54', 'pago'),
(721, 42, '2025-04-09 17:38:53', 'aberto'),
(722, 41, '2025-04-09 18:09:15', 'pago'),
(723, 55, '2025-04-09 18:19:22', 'pago'),
(724, 56, '2025-04-09 18:20:24', 'pago'),
(725, 24, '2025-04-09 18:46:12', 'aberto'),
(726, 24, '2025-04-09 18:46:57', 'aberto'),
(727, 32, '2025-04-09 19:35:59', 'aberto'),
(728, 2, '2025-04-10 01:18:01', 'aberto'),
(729, 31, '2025-04-10 11:03:14', 'pago'),
(730, 18, '2025-04-10 16:00:41', 'pago'),
(731, 26, '2025-04-10 16:14:02', 'pago'),
(732, 36, '2025-04-10 16:15:40', 'pago'),
(733, 14, '2025-04-10 16:16:29', 'pago'),
(734, 41, '2025-04-10 16:17:44', 'pago'),
(735, 42, '2025-04-10 16:25:27', 'aberto'),
(736, 23, '2025-04-10 16:28:32', 'aberto'),
(737, 38, '2025-04-10 16:32:38', 'pago'),
(738, 15, '2025-04-10 16:36:06', 'pago'),
(739, 57, '2025-04-10 16:39:59', 'pago'),
(740, 28, '2025-04-10 16:42:00', 'pago'),
(741, 47, '2025-04-10 18:04:46', 'pago'),
(742, 32, '2025-04-10 18:06:45', 'aberto'),
(743, 29, '2025-04-10 18:09:24', 'pago'),
(744, 17, '2025-04-10 18:10:18', 'pago'),
(745, 41, '2025-04-10 18:11:53', 'pago'),
(746, 42, '2025-04-10 18:14:07', 'aberto'),
(747, 16, '2025-04-10 18:16:11', 'pago'),
(748, 26, '2025-04-10 18:16:57', 'pago'),
(749, 31, '2025-04-10 18:19:37', 'pago'),
(750, 57, '2025-04-10 18:22:16', 'pago'),
(751, 56, '2025-04-10 19:42:31', 'pago'),
(752, 46, '2025-04-10 20:28:25', 'pago'),
(753, 31, '2025-04-11 10:54:30', 'pago'),
(754, 28, '2025-04-11 17:28:09', 'pago'),
(755, 42, '2025-04-11 17:29:32', 'aberto'),
(756, 40, '2025-04-11 17:30:29', 'pago'),
(757, 41, '2025-04-11 17:31:15', 'pago'),
(758, 15, '2025-04-11 17:33:44', 'pago'),
(759, 36, '2025-04-11 17:34:36', 'pago'),
(760, 25, '2025-04-11 17:37:17', 'pago'),
(761, 48, '2025-04-11 17:40:55', 'pago'),
(762, 48, '2025-04-11 17:41:18', 'aberto'),
(763, 18, '2025-04-11 17:44:15', 'pago'),
(764, 31, '2025-04-11 17:46:08', 'pago'),
(765, 10, '2025-04-11 17:48:12', 'pago'),
(766, 29, '2025-04-11 17:52:28', 'pago'),
(767, 29, '2025-04-11 17:54:12', 'pago'),
(768, 21, '2025-04-11 17:59:37', 'aberto'),
(769, 19, '2025-04-11 18:01:35', 'pago'),
(770, 42, '2025-04-11 18:59:05', 'aberto'),
(771, 19, '2025-04-11 19:13:25', 'pago'),
(772, 16, '2025-04-11 19:14:49', 'pago'),
(773, 36, '2025-04-11 19:35:32', 'pago'),
(774, 47, '2025-04-11 19:45:10', 'pago'),
(775, 3, '2025-04-11 20:05:24', 'pago'),
(776, 47, '2025-04-11 21:32:03', 'pago'),
(777, 3, '2025-04-11 21:32:37', 'pago'),
(778, 32, '2025-04-14 09:52:06', 'aberto'),
(779, 46, '2025-04-14 11:13:26', 'pago'),
(780, 15, '2025-04-14 11:14:26', 'pago'),
(781, 29, '2025-04-14 11:44:53', 'pago'),
(782, 16, '2025-04-14 17:17:30', 'pago'),
(783, 16, '2025-04-14 17:17:33', 'pago'),
(784, 56, '2025-04-14 17:18:34', 'pago'),
(785, 56, '2025-04-14 17:18:37', 'pago'),
(786, 36, '2025-04-14 19:35:52', 'pago'),
(787, 16, '2025-04-14 19:38:45', 'pago'),
(788, 14, '2025-04-14 19:39:39', 'pago'),
(789, 42, '2025-04-14 19:41:19', 'aberto'),
(790, 18, '2025-04-14 19:42:20', 'pago'),
(791, 19, '2025-04-14 19:44:19', 'pago'),
(792, 32, '2025-04-14 19:45:08', 'aberto'),
(793, 28, '2025-04-14 19:46:07', 'pago'),
(794, 31, '2025-04-14 19:49:54', 'aberto'),
(795, 36, '2025-04-14 19:51:39', 'pago'),
(796, 41, '2025-04-14 19:52:16', 'pago'),
(797, 17, '2025-04-14 19:52:56', 'pago'),
(798, 3, '2025-04-14 20:43:58', 'pago'),
(799, 41, '2025-04-15 16:25:41', 'pago'),
(800, 16, '2025-04-15 16:30:48', 'pago'),
(801, 41, '2025-04-15 16:51:56', 'pago'),
(802, 48, '2025-04-15 17:15:51', 'pago'),
(803, 48, '2025-04-15 18:41:52', 'pago'),
(804, 10, '2025-04-15 18:43:09', 'pago'),
(805, 10, '2025-04-15 18:48:11', 'pago'),
(806, 17, '2025-04-15 18:53:18', 'pago'),
(807, 42, '2025-04-15 18:56:51', 'aberto'),
(808, 28, '2025-04-15 19:04:35', 'pago'),
(809, 19, '2025-04-15 19:06:29', 'pago'),
(810, 15, '2025-04-15 19:08:27', 'pago'),
(811, 36, '2025-04-15 19:14:01', 'pago'),
(812, 3, '2025-04-15 19:30:28', 'pago'),
(813, 24, '2025-04-15 19:55:25', 'aberto'),
(814, 38, '2025-04-16 13:27:40', 'pago'),
(815, 45, '2025-04-16 18:07:13', 'aberto'),
(816, 17, '2025-04-16 18:45:21', 'pago'),
(817, 17, '2025-04-16 18:45:23', 'pago'),
(818, 26, '2025-04-16 18:46:21', 'pago'),
(819, 41, '2025-04-16 18:54:24', 'pago'),
(820, 48, '2025-04-16 18:55:43', 'pago'),
(821, 18, '2025-04-16 18:57:24', 'pago'),
(822, 38, '2025-04-16 19:29:38', 'pago'),
(823, 28, '2025-04-16 19:36:50', 'pago'),
(824, 19, '2025-04-16 19:38:18', 'pago'),
(825, 15, '2025-04-16 19:39:35', 'pago'),
(826, 29, '2025-04-16 19:41:03', 'pago'),
(827, 16, '2025-04-16 19:42:22', 'pago'),
(828, 16, '2025-04-16 19:51:03', 'pago'),
(829, 41, '2025-04-16 20:05:05', 'pago'),
(830, 55, '2025-04-17 10:29:15', 'pago'),
(831, 21, '2025-04-17 10:30:32', 'aberto'),
(832, 16, '2025-04-17 17:04:00', 'pago'),
(833, 24, '2025-04-17 17:46:54', 'aberto'),
(834, 28, '2025-04-17 17:53:52', 'pago'),
(835, 15, '2025-04-17 19:05:38', 'pago'),
(836, 26, '2025-04-17 19:06:38', 'pago'),
(837, 36, '2025-04-17 19:08:51', 'pago'),
(838, 28, '2025-04-17 19:13:57', 'pago'),
(839, 18, '2025-04-17 19:15:54', 'pago'),
(840, 41, '2025-04-17 19:20:38', 'pago'),
(841, 29, '2025-04-17 20:00:51', 'pago'),
(842, 38, '2025-04-22 15:33:06', 'pago'),
(843, 48, '2025-04-22 15:59:22', 'pago'),
(844, 40, '2025-04-22 16:33:11', 'pago'),
(845, 40, '2025-04-22 19:18:16', 'pago'),
(846, 24, '2025-04-22 19:22:37', 'aberto'),
(847, 17, '2025-04-22 19:27:03', 'pago'),
(848, 18, '2025-04-22 19:29:05', 'pago'),
(849, 16, '2025-04-22 19:30:34', 'pago'),
(850, 10, '2025-04-22 19:32:34', 'pago'),
(851, 36, '2025-04-22 19:33:59', 'pago'),
(852, 32, '2025-04-22 19:36:47', 'aberto'),
(853, 28, '2025-04-22 19:39:56', 'pago'),
(854, 3, '2025-04-22 20:49:49', 'pago'),
(855, 15, '2025-04-23 09:50:48', 'pago'),
(856, 28, '2025-04-23 17:12:05', 'pago'),
(857, 32, '2025-04-23 17:13:39', 'aberto'),
(858, 32, '2025-04-23 17:14:30', 'aberto'),
(859, 21, '2025-04-23 17:18:59', 'aberto'),
(860, 21, '2025-04-23 17:23:00', 'aberto'),
(861, 19, '2025-04-23 17:24:57', 'pago'),
(862, 41, '2025-04-23 19:03:09', 'pago'),
(863, 15, '2025-04-24 09:54:44', 'pago'),
(864, 25, '2025-04-24 09:56:29', 'pago'),
(865, 17, '2025-04-24 10:12:17', 'pago'),
(866, 18, '2025-04-24 10:15:40', 'pago'),
(867, 41, '2025-04-24 10:32:29', 'pago'),
(868, 8, '2025-04-24 10:36:08', 'pago'),
(869, 42, '2025-04-24 10:37:42', 'aberto'),
(870, 55, '2025-04-24 10:38:55', 'pago'),
(871, 10, '2025-04-24 10:47:53', 'pago'),
(872, 21, '2025-04-24 10:52:10', 'aberto'),
(873, 19, '2025-04-24 10:52:56', 'pago'),
(874, 41, '2025-04-24 14:09:30', 'pago'),
(875, 18, '2025-04-24 17:55:38', 'pago'),
(876, 9, '2025-04-24 17:58:34', 'pago'),
(877, 32, '2025-04-24 18:09:06', 'aberto'),
(878, 26, '2025-04-24 18:13:46', 'pago'),
(879, 41, '2025-04-24 18:17:26', 'pago'),
(880, 38, '2025-04-24 18:58:49', 'pago'),
(881, 14, '2025-04-24 19:49:01', 'pago'),
(882, 24, '2025-04-24 19:50:57', 'aberto'),
(883, 42, '2025-04-24 20:14:13', 'aberto'),
(884, 36, '2025-04-24 20:15:26', 'pago'),
(885, 19, '2025-04-24 20:17:17', 'pago'),
(886, 9, '2025-04-24 20:18:14', 'pago'),
(887, 56, '2025-04-25 19:31:53', 'pago'),
(888, 25, '2025-04-25 19:33:30', 'pago'),
(889, 15, '2025-04-25 19:36:34', 'pago'),
(890, 42, '2025-04-25 19:37:42', 'aberto'),
(891, 41, '2025-04-25 19:38:57', 'aberto'),
(892, 10, '2025-04-25 19:39:52', 'pago'),
(893, 29, '2025-04-25 19:42:26', 'pago'),
(894, 46, '2025-04-25 19:46:28', 'pago'),
(895, 17, '2025-04-25 19:50:55', 'pago'),
(896, 47, '2025-04-25 19:52:15', 'pago'),
(897, 19, '2025-04-25 19:54:58', 'pago'),
(898, 36, '2025-04-25 19:57:46', 'pago'),
(899, 18, '2025-04-25 19:59:02', 'pago'),
(900, 40, '2025-04-25 20:00:52', 'pago'),
(901, 41, '2025-04-28 18:41:31', 'aberto'),
(902, 57, '2025-04-28 18:42:19', 'pago'),
(903, 19, '2025-04-28 22:02:51', 'pago'),
(904, 42, '2025-04-28 22:05:25', 'aberto'),
(905, 14, '2025-04-28 22:06:54', 'pago'),
(906, 16, '2025-04-28 22:08:47', 'pago'),
(907, 18, '2025-04-28 22:10:13', 'pago'),
(908, 43, '2025-04-28 22:12:54', 'aberto'),
(909, 55, '2025-04-28 22:16:59', 'pago'),
(910, 21, '2025-04-28 22:18:12', 'aberto'),
(911, 17, '2025-04-28 22:19:23', 'pago'),
(912, 26, '2025-04-28 22:20:43', 'pago'),
(913, 3, '2025-04-28 22:55:56', 'pago'),
(914, 17, '2025-04-29 14:15:42', 'pago'),
(915, 10, '2025-04-29 20:05:04', 'pago'),
(916, 21, '2025-04-29 20:10:50', 'aberto'),
(917, 17, '2025-04-29 20:13:12', 'pago'),
(918, 55, '2025-04-29 20:14:26', 'pago'),
(919, 41, '2025-04-29 20:15:55', 'aberto'),
(920, 36, '2025-04-29 20:17:20', 'pago'),
(921, 8, '2025-04-29 20:18:30', 'pago'),
(922, 48, '2025-04-29 20:25:01', 'pago'),
(923, 15, '2025-04-29 20:50:30', 'pago'),
(924, 59, '2025-04-29 20:58:48', 'pago'),
(925, 18, '2025-04-29 21:01:42', 'pago'),
(926, 28, '2025-04-29 21:04:12', 'pago'),
(927, 19, '2025-04-29 21:06:04', 'pago'),
(928, 60, '2025-04-29 21:08:57', 'pago'),
(929, 39, '2025-04-29 21:11:12', 'pago'),
(930, 8, '2025-04-30 20:40:48', 'aberto'),
(931, 21, '2025-04-30 20:42:33', 'aberto'),
(932, 55, '2025-04-30 20:43:36', 'pago'),
(933, 16, '2025-04-30 20:44:34', 'pago'),
(934, 59, '2025-04-30 20:49:40', 'pago'),
(935, 3, '2025-04-30 20:50:16', 'pago'),
(936, 10, '2025-04-30 20:51:53', 'pago'),
(937, 12, '2025-04-30 20:52:48', 'pago'),
(938, 56, '2025-04-30 20:53:36', 'pago'),
(939, 43, '2025-04-30 20:54:14', 'aberto'),
(940, 42, '2025-04-30 20:55:26', 'aberto'),
(941, 22, '2025-04-30 20:58:03', 'aberto'),
(942, 60, '2025-04-30 20:59:10', 'pago'),
(943, 15, '2025-04-30 21:00:16', 'pago'),
(944, 36, '2025-04-30 21:01:37', 'pago'),
(945, 18, '2025-04-30 21:03:04', 'pago'),
(946, 10, '2025-04-30 21:04:48', 'pago'),
(947, 28, '2025-04-30 21:05:41', 'pago'),
(948, 56, '2025-05-02 15:55:34', 'pago'),
(949, 43, '2025-05-02 16:48:06', 'aberto'),
(950, 41, '2025-05-05 14:01:56', 'aberto'),
(951, 36, '2025-05-05 14:04:05', 'pago'),
(952, 41, '2025-05-05 14:05:13', 'aberto'),
(953, 8, '2025-05-05 14:07:11', 'aberto'),
(954, 15, '2025-05-05 14:10:28', 'pago'),
(955, 59, '2025-05-05 14:14:29', 'pago'),
(956, 46, '2025-05-05 14:16:35', 'pago'),
(957, 28, '2025-05-05 14:18:10', 'pago'),
(958, 60, '2025-05-05 14:38:53', 'pago'),
(959, 60, '2025-05-05 15:03:13', 'pago'),
(960, 18, '2025-05-05 15:52:43', 'pago'),
(961, 23, '2025-05-05 15:54:08', 'aberto'),
(962, 46, '2025-05-05 21:43:58', 'pago'),
(963, 17, '2025-05-05 21:44:48', 'aberto'),
(964, 18, '2025-05-05 21:46:19', 'pago'),
(965, 42, '2025-05-05 21:47:35', 'aberto'),
(966, 43, '2025-05-05 21:48:47', 'aberto'),
(967, 38, '2025-05-05 21:49:37', 'pago'),
(968, 38, '2025-05-05 21:51:48', 'pago'),
(969, 15, '2025-05-05 21:52:53', 'pago'),
(970, 8, '2025-05-05 21:53:51', 'aberto'),
(971, 36, '2025-05-05 21:54:55', 'pago'),
(972, 19, '2025-05-05 21:57:41', 'pago'),
(973, 60, '2025-05-05 21:59:02', 'pago'),
(974, 10, '2025-05-05 21:59:58', 'pago'),
(975, 38, '2025-05-05 22:00:49', 'pago'),
(976, 57, '2025-05-05 22:01:33', 'pago'),
(977, 24, '2025-05-05 22:02:27', 'aberto'),
(978, 40, '2025-05-05 22:03:55', 'pago'),
(979, 21, '2025-05-05 22:09:10', 'aberto'),
(980, 38, '2025-05-06 14:35:17', 'pago'),
(981, 8, '2025-05-06 14:52:31', 'aberto'),
(982, 46, '2025-05-06 18:55:10', 'aberto'),
(983, 59, '2025-05-06 19:06:54', 'aberto'),
(984, 15, '2025-05-06 21:29:48', 'aberto'),
(985, 10, '2025-05-06 21:32:02', 'pago'),
(986, 12, '2025-05-06 21:32:58', 'aberto'),
(987, 55, '2025-05-06 21:34:11', 'pago'),
(988, 60, '2025-05-06 21:39:43', 'pago'),
(989, 42, '2025-05-06 21:42:53', 'aberto'),
(990, 32, '2025-05-06 21:44:13', 'aberto'),
(991, 36, '2025-05-06 21:45:08', 'pago'),
(992, 28, '2025-05-06 21:46:00', 'aberto'),
(993, 26, '2025-05-06 21:56:34', 'aberto'),
(994, 17, '2025-05-06 21:58:11', 'aberto'),
(995, 41, '2025-05-06 21:59:48', 'aberto'),
(996, 60, '2025-05-06 22:00:25', 'pago'),
(997, 18, '2025-05-06 22:02:26', 'pago'),
(998, 19, '2025-05-06 22:03:40', 'pago'),
(999, 21, '2025-05-06 22:05:06', 'aberto'),
(1000, 48, '2025-05-07 12:18:56', 'pago'),
(1001, 8, '2025-05-07 13:48:59', 'aberto'),
(1002, 56, '2025-05-07 18:15:16', 'pago'),
(1003, 55, '2025-05-07 18:34:17', 'pago'),
(1004, 48, '2025-05-07 18:36:15', 'pago'),
(1005, 19, '2025-05-07 18:37:29', 'pago'),
(1006, 31, '2025-05-07 18:40:57', 'aberto'),
(1007, 36, '2025-05-07 18:48:45', 'pago'),
(1008, 10, '2025-05-07 18:54:34', 'pago'),
(1009, 8, '2025-05-07 18:57:34', 'aberto'),
(1010, 16, '2025-05-07 18:58:13', 'aberto'),
(1011, 38, '2025-05-07 18:59:29', 'pago'),
(1012, 59, '2025-05-07 19:01:47', 'pago'),
(1013, 41, '2025-05-07 19:04:06', 'aberto'),
(1014, 39, '2025-05-07 19:05:52', 'pago'),
(1015, 39, '2025-05-08 17:29:02', 'pago'),
(1016, 60, '2025-05-08 19:19:15', 'pago'),
(1017, 60, '2025-05-08 19:21:40', 'pago'),
(1018, 15, '2025-05-08 19:46:13', 'aberto'),
(1019, 59, '2025-05-08 19:49:42', 'pago'),
(1020, 15, '2025-05-09 01:41:13', 'aberto'),
(1021, 59, '2025-05-09 01:45:52', 'pago'),
(1022, 19, '2025-05-09 01:47:01', 'aberto'),
(1023, 41, '2025-05-09 01:49:41', 'aberto'),
(1024, 8, '2025-05-09 11:54:54', 'aberto'),
(1025, 59, '2025-05-09 14:44:48', 'pago'),
(1026, 21, '2025-05-09 16:59:01', 'aberto'),
(1027, 41, '2025-05-09 17:02:49', 'aberto'),
(1028, 36, '2025-05-09 17:11:48', 'pago'),
(1029, 18, '2025-05-09 17:13:51', 'aberto'),
(1030, 40, '2025-05-09 17:20:34', 'pago'),
(1031, 26, '2025-05-09 17:23:17', 'aberto'),
(1032, 28, '2025-05-09 17:38:40', 'aberto'),
(1033, 56, '2025-05-09 17:39:41', 'aberto'),
(1034, 28, '2025-05-09 17:41:03', 'aberto'),
(1035, 41, '2025-05-09 17:42:07', 'aberto'),
(1036, 48, '2025-05-09 17:43:20', 'pago'),
(1037, 17, '2025-05-09 17:44:39', 'aberto'),
(1038, 25, '2025-05-12 12:18:48', 'aberto'),
(1039, 10, '2025-05-12 12:49:56', 'pago'),
(1040, 15, '2025-05-12 12:59:02', 'aberto'),
(1041, 29, '2025-05-12 13:00:35', 'aberto'),
(1042, 3, '2025-05-12 17:41:02', 'pago'),
(1043, 18, '2025-05-13 14:13:53', 'aberto'),
(1044, 21, '2025-05-13 14:40:55', 'aberto'),
(1045, 3, '2025-05-13 16:06:17', 'aberto'),
(1046, 59, '2025-05-13 16:20:54', 'aberto'),
(1047, 24, '2025-05-13 16:21:51', 'aberto'),
(1048, 39, '2025-05-13 16:25:05', 'pago'),
(1049, 48, '2025-05-13 16:26:54', 'pago'),
(1050, 36, '2025-05-13 16:28:01', 'pago'),
(1051, 3, '2025-05-13 16:29:21', 'aberto'),
(1052, 28, '2025-05-13 16:31:29', 'aberto'),
(1053, 41, '2025-05-13 16:45:32', 'aberto'),
(1054, 55, '2025-05-13 17:25:10', 'pago'),
(1055, 21, '2025-05-13 17:27:33', 'aberto'),
(1056, 26, '2025-05-13 17:28:58', 'aberto'),
(1057, 8, '2025-05-13 17:30:21', 'aberto'),
(1058, 25, '2025-05-13 17:31:31', 'aberto'),
(1059, 38, '2025-05-13 17:33:04', 'pago'),
(1060, 29, '2025-05-13 17:34:16', 'aberto'),
(1061, 17, '2025-05-13 17:36:06', 'aberto'),
(1062, 15, '2025-05-13 17:38:03', 'aberto'),
(1063, 57, '2025-05-13 18:01:06', 'pago'),
(1064, 59, '2025-05-13 18:43:02', 'aberto'),
(1065, 18, '2025-05-13 19:54:54', 'aberto'),
(1066, 21, '2025-05-14 11:02:59', 'aberto'),
(1067, 59, '2025-05-14 11:45:25', 'aberto'),
(1068, 8, '2025-05-14 17:36:48', 'aberto'),
(1069, 47, '2025-05-14 17:38:06', 'aberto'),
(1070, 18, '2025-05-14 17:39:59', 'aberto'),
(1071, 42, '2025-05-14 17:41:04', 'aberto'),
(1072, 21, '2025-05-14 17:42:03', 'aberto'),
(1073, 43, '2025-05-14 17:43:36', 'aberto'),
(1074, 41, '2025-05-14 17:45:19', 'aberto'),
(1075, 42, '2025-05-14 20:12:03', 'aberto'),
(1076, 19, '2025-05-14 21:30:18', 'aberto'),
(1077, 59, '2025-05-14 21:31:09', 'aberto'),
(1078, 36, '2025-05-14 21:31:48', 'pago'),
(1079, 29, '2025-05-14 21:32:42', 'aberto'),
(1080, 17, '2025-05-14 21:33:41', 'aberto'),
(1081, 41, '2025-05-14 21:34:30', 'aberto'),
(1082, 39, '2025-05-14 21:35:19', 'aberto'),
(1083, 18, '2025-05-14 21:36:14', 'aberto'),
(1084, 55, '2025-05-14 21:37:18', 'pago'),
(1085, 16, '2025-05-14 21:38:25', 'aberto'),
(1086, 28, '2025-05-14 21:39:38', 'aberto'),
(1087, 28, '2025-05-14 21:42:24', 'aberto'),
(1088, 39, '2025-05-15 14:13:13', 'aberto'),
(1089, 41, '2025-05-15 14:46:05', 'aberto'),
(1090, 59, '2025-05-15 16:51:37', 'aberto'),
(1091, 38, '2025-05-15 16:52:34', 'pago'),
(1092, 28, '2025-05-15 17:02:05', 'aberto'),
(1093, 16, '2025-05-15 17:03:02', 'aberto'),
(1094, 15, '2025-05-15 17:03:53', 'aberto'),
(1095, 17, '2025-05-15 17:05:03', 'aberto'),
(1096, 42, '2025-05-15 17:11:19', 'aberto'),
(1097, 18, '2025-05-15 17:12:38', 'aberto'),
(1098, 19, '2025-05-15 17:15:54', 'aberto'),
(1099, 36, '2025-05-15 17:16:55', 'pago'),
(1100, 29, '2025-05-15 17:19:21', 'aberto'),
(1101, 42, '2025-05-15 18:24:28', 'aberto'),
(1102, 57, '2025-05-15 18:26:37', 'pago'),
(1103, 41, '2025-05-15 18:48:48', 'aberto'),
(1104, 39, '2025-05-15 20:01:01', 'aberto'),
(1105, 41, '2025-05-15 20:01:50', 'aberto'),
(1106, 17, '2025-05-15 20:02:49', 'aberto'),
(1107, 59, '2025-05-16 16:13:44', 'aberto'),
(1108, 56, '2025-05-16 18:31:27', 'aberto'),
(1109, 56, '2025-05-16 18:41:50', 'aberto'),
(1110, 57, '2025-05-16 18:42:34', 'pago'),
(1111, 17, '2025-05-16 18:46:26', 'aberto'),
(1112, 41, '2025-05-16 18:49:41', 'aberto'),
(1113, 39, '2025-05-16 18:50:50', 'aberto'),
(1114, 38, '2025-05-16 18:52:23', 'pago'),
(1115, 15, '2025-05-16 19:15:52', 'aberto'),
(1116, 16, '2025-05-16 19:17:14', 'aberto'),
(1117, 8, '2025-05-16 19:18:39', 'aberto'),
(1118, 21, '2025-05-16 19:27:59', 'aberto'),
(1119, 18, '2025-05-16 19:30:07', 'aberto'),
(1120, 29, '2025-05-16 19:31:22', 'aberto'),
(1121, 19, '2025-05-16 19:35:47', 'aberto'),
(1122, 21, '2025-05-16 19:55:55', 'aberto'),
(1123, 8, '2025-05-19 13:58:34', 'aberto'),
(1124, 56, '2025-05-19 17:59:20', 'aberto'),
(1125, 38, '2025-05-19 18:20:16', 'aberto'),
(1126, 57, '2025-05-19 19:05:00', 'pago'),
(1127, 8, '2025-05-19 20:34:16', 'aberto'),
(1128, 17, '2025-05-19 20:40:28', 'aberto'),
(1129, 29, '2025-05-19 20:41:44', 'aberto'),
(1130, 28, '2025-05-19 20:43:13', 'aberto'),
(1131, 19, '2025-05-19 20:45:54', 'aberto'),
(1132, 59, '2025-05-19 20:47:25', 'aberto'),
(1133, 41, '2025-05-19 20:48:28', 'aberto'),
(1134, 16, '2025-05-19 20:49:49', 'aberto'),
(1135, 18, '2025-05-19 20:50:36', 'aberto'),
(1136, 39, '2025-05-19 20:52:01', 'aberto'),
(1137, 3, '2025-05-19 21:12:25', 'aberto'),
(1138, 8, '2025-05-20 12:05:09', 'aberto'),
(1139, 8, '2025-05-20 16:46:22', 'aberto'),
(1140, 3, '2025-05-20 17:02:26', 'aberto'),
(1141, 40, '2025-05-21 16:03:43', 'pago'),
(1142, 28, '2025-05-21 16:04:27', 'aberto'),
(1143, 8, '2025-05-21 16:09:44', 'aberto'),
(1144, 11, '2025-05-21 16:10:46', 'aberto'),
(1145, 55, '2025-05-21 16:24:31', 'pago'),
(1146, 38, '2025-05-21 16:40:36', 'aberto'),
(1147, 29, '2025-05-21 16:47:37', 'aberto'),
(1148, 17, '2025-05-21 16:49:05', 'aberto'),
(1149, 41, '2025-05-21 16:50:01', 'aberto'),
(1150, 10, '2025-05-21 16:51:27', 'aberto'),
(1151, 39, '2025-05-21 16:53:10', 'aberto'),
(1152, 18, '2025-05-21 18:28:05', 'aberto'),
(1153, 16, '2025-05-21 18:41:11', 'aberto'),
(1154, 21, '2025-05-21 18:43:37', 'aberto'),
(1155, 19, '2025-05-21 18:48:24', 'aberto'),
(1156, 43, '2025-05-21 18:50:13', 'aberto'),
(1157, 16, '2025-05-22 09:42:35', 'aberto'),
(1158, 41, '2025-05-22 18:00:30', 'aberto'),
(1159, 8, '2025-05-22 18:40:08', 'aberto'),
(1160, 29, '2025-05-22 18:41:10', 'aberto'),
(1161, 16, '2025-05-22 18:43:16', 'aberto'),
(1162, 36, '2025-05-22 18:45:05', 'pago'),
(1163, 41, '2025-05-22 21:42:39', 'aberto'),
(1164, 39, '2025-05-22 21:43:28', 'aberto'),
(1165, 56, '2025-05-22 21:44:47', 'aberto'),
(1166, 38, '2025-05-22 21:45:40', 'aberto'),
(1167, 60, '2025-05-22 21:47:01', 'pago'),
(1168, 18, '2025-05-22 21:48:00', 'aberto'),
(1169, 19, '2025-05-22 21:50:05', 'aberto'),
(1170, 17, '2025-05-22 21:53:18', 'aberto'),
(1171, 61, '2025-05-22 21:59:41', 'pago'),
(1172, 61, '2025-05-22 22:00:22', 'pago'),
(1173, 59, '2025-05-22 22:02:42', 'aberto'),
(1174, 59, '2025-05-22 22:03:26', 'aberto'),
(1175, 59, '2025-05-23 14:26:39', 'aberto'),
(1176, 11, '2025-05-23 17:04:43', 'aberto'),
(1177, 59, '2025-05-23 17:06:04', 'aberto'),
(1178, 41, '2025-05-23 17:07:03', 'aberto'),
(1179, 16, '2025-05-23 17:39:21', 'aberto'),
(1180, 16, '2025-05-23 17:42:17', 'aberto'),
(1181, 29, '2025-05-23 17:43:03', 'aberto'),
(1182, 8, '2025-05-23 17:44:21', 'aberto'),
(1183, 17, '2025-05-23 17:45:40', 'aberto'),
(1184, 36, '2025-05-23 17:47:09', 'pago'),
(1185, 18, '2025-05-23 17:48:45', 'aberto'),
(1186, 19, '2025-05-23 17:50:05', 'aberto'),
(1187, 57, '2025-05-23 17:50:46', 'aberto'),
(1188, 12, '2025-05-23 17:51:32', 'aberto'),
(1189, 39, '2025-05-26 10:13:21', 'aberto'),
(1190, 11, '2025-05-26 10:17:36', 'aberto'),
(1191, 57, '2025-05-26 13:59:58', 'aberto'),
(1192, 43, '2025-05-26 17:16:39', 'aberto'),
(1193, 59, '2025-05-27 00:05:07', 'aberto'),
(1194, 17, '2025-05-27 00:06:50', 'aberto'),
(1195, 8, '2025-05-27 00:07:53', 'aberto'),
(1196, 39, '2025-05-27 00:08:39', 'aberto'),
(1197, 29, '2025-05-27 00:09:14', 'aberto'),
(1198, 16, '2025-05-27 00:10:17', 'aberto'),
(1199, 19, '2025-05-27 00:11:15', 'aberto'),
(1200, 56, '2025-05-27 00:12:10', 'aberto'),
(1201, 40, '2025-05-27 14:25:15', 'pago'),
(1202, 36, '2025-05-27 17:12:25', 'aberto'),
(1203, 41, '2025-05-27 17:14:30', 'aberto'),
(1204, 29, '2025-05-27 17:17:39', 'aberto'),
(1205, 19, '2025-05-27 17:19:52', 'aberto'),
(1206, 11, '2025-05-27 17:25:39', 'aberto'),
(1207, 15, '2025-05-27 17:31:09', 'aberto'),
(1208, 48, '2025-05-27 18:22:19', 'pago'),
(1209, 18, '2025-05-27 18:25:27', 'aberto'),
(1210, 40, '2025-05-27 18:27:31', 'aberto'),
(1211, 61, '2025-05-27 18:43:54', 'pago'),
(1212, 29, '2025-05-27 19:03:07', 'aberto'),
(1213, 56, '2025-05-28 17:07:02', 'aberto'),
(1214, 41, '2025-05-28 17:13:00', 'aberto'),
(1215, 16, '2025-05-28 17:35:17', 'aberto'),
(1216, 29, '2025-05-28 17:36:50', 'aberto'),
(1217, 48, '2025-05-28 17:37:44', 'aberto'),
(1218, 16, '2025-05-28 17:38:32', 'aberto'),
(1219, 15, '2025-05-28 17:40:59', 'aberto'),
(1220, 46, '2025-05-28 17:42:41', 'aberto'),
(1221, 36, '2025-05-28 17:44:46', 'pago'),
(1222, 18, '2025-05-28 17:48:03', 'aberto'),
(1223, 11, '2025-05-28 17:49:43', 'aberto'),
(1224, 8, '2025-05-28 17:50:29', 'aberto'),
(1225, 17, '2025-05-28 17:52:12', 'aberto'),
(1226, 61, '2025-05-28 17:54:54', 'aberto'),
(1227, 61, '2025-05-28 17:55:22', 'aberto'),
(1228, 39, '2025-05-28 17:56:13', 'aberto'),
(1229, 39, '2025-05-28 17:56:58', 'aberto'),
(1230, 59, '2025-05-28 17:58:03', 'aberto'),
(1231, 19, '2025-05-28 17:59:12', 'aberto'),
(1232, 8, '2025-05-28 18:07:42', 'aberto'),
(1233, 41, '2025-05-28 18:30:17', 'aberto'),
(1234, 36, '2025-05-28 18:37:58', 'pago');
INSERT INTO `vendas` (`id`, `cliente_id`, `data_venda`, `status`) VALUES
(1235, 10, '2025-05-28 18:40:20', 'aberto'),
(1236, 43, '2025-05-29 15:49:53', 'aberto'),
(1237, 29, '2025-05-29 21:05:43', 'aberto'),
(1238, 55, '2025-05-29 21:10:23', 'aberto'),
(1239, 43, '2025-05-29 21:15:51', 'aberto'),
(1240, 16, '2025-05-29 21:17:52', 'aberto'),
(1241, 48, '2025-05-29 21:18:56', 'aberto'),
(1242, 39, '2025-05-29 21:19:42', 'aberto'),
(1243, 8, '2025-05-29 21:20:30', 'aberto'),
(1244, 61, '2025-05-29 21:21:41', 'aberto'),
(1245, 15, '2025-05-29 21:23:04', 'aberto'),
(1246, 41, '2025-05-29 21:24:07', 'aberto'),
(1247, 18, '2025-05-29 21:25:31', 'aberto'),
(1248, 17, '2025-05-29 21:26:17', 'aberto'),
(1249, 59, '2025-05-29 21:27:03', 'aberto'),
(1250, 41, '2025-05-30 16:21:58', 'aberto'),
(1251, 15, '2025-05-30 16:32:03', 'aberto'),
(1252, 8, '2025-05-30 16:33:41', 'aberto'),
(1253, 48, '2025-05-30 16:34:59', 'aberto'),
(1254, 59, '2025-05-30 16:35:43', 'aberto'),
(1255, 18, '2025-05-30 16:37:22', 'aberto'),
(1256, 17, '2025-05-30 16:38:37', 'aberto'),
(1257, 46, '2025-05-30 16:39:28', 'aberto'),
(1258, 17, '2025-05-30 16:40:22', 'aberto'),
(1259, 19, '2025-05-30 16:41:02', 'aberto'),
(1260, 39, '2025-05-30 16:41:52', 'aberto'),
(1261, 36, '2025-05-30 16:42:26', 'pago'),
(1262, 40, '2025-05-30 16:43:43', 'aberto'),
(1263, 61, '2025-05-30 16:44:28', 'aberto'),
(1264, 29, '2025-05-30 16:47:04', 'aberto'),
(1265, 38, '2025-05-30 16:47:49', 'aberto'),
(1266, 43, '2025-05-30 16:50:07', 'aberto'),
(1267, 62, '2025-05-30 16:55:26', 'aberto'),
(1268, 16, '2025-05-30 16:57:27', 'aberto'),
(1269, 15, '2025-05-30 19:08:25', 'aberto'),
(1270, 18, '2025-05-30 20:38:13', 'aberto'),
(1272, 2, '2025-06-01 15:38:24', 'aberto'),
(1273, 2, '2025-06-01 15:42:24', 'aberto');

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `view_contas_vencidas`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `view_contas_vencidas` (
`id` int
,`tipo` enum('pagar','receber')
,`categoria_id` int
,`descricao` varchar(255)
,`valor_original` decimal(10,2)
,`valor_pago` decimal(10,2)
,`valor_pendente` decimal(10,2)
,`data_vencimento` date
,`data_competencia` date
,`data_cadastro` date
,`status` enum('pendente','pago_parcial','pago','vencido','cancelado')
,`prioridade` enum('baixa','media','alta','urgente')
,`cliente_id` int
,`fornecedor_id` int
,`venda_id` int
,`observacoes` text
,`documento` varchar(100)
,`forma_pagamento` varchar(50)
,`recorrente` tinyint(1)
,`periodicidade` enum('mensal','bimestral','trimestral','semestral','anual')
,`dia_vencimento` int
,`usuario_cadastro` int
,`created_at` timestamp
,`updated_at` timestamp
,`categoria_nome` varchar(100)
,`categoria_cor` varchar(7)
,`dias_vencido` int
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `view_estatisticas_fornecedores`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `view_estatisticas_fornecedores` (
`id` int
,`nome` varchar(255)
,`empresa` varchar(255)
,`telefone` varchar(20)
,`status` enum('ativo','inativo')
,`avaliacao` decimal(2,1)
,`total_pedidos` bigint
,`valor_total_comprado` decimal(32,2)
,`valor_pedidos_abertos` decimal(32,2)
,`ultimo_pedido` date
,`media_atraso_dias` decimal(12,4)
,`entregas_no_prazo` bigint
,`total_entregas` bigint
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `view_fluxo_caixa`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `view_fluxo_caixa` (
`mes_ano` varchar(7)
,`receitas_previstas` decimal(32,2)
,`despesas_previstas` decimal(32,2)
,`saldo_previsto` decimal(33,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `view_ponto_completo`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `view_ponto_completo` (
`id` int
,`funcionario_id` int
,`data_registro` date
,`entrada_manha` time
,`saida_almoco` time
,`entrada_tarde` time
,`saida_final` time
,`horas_trabalhadas` time
,`horas_extras` time
,`observacoes` text
,`ip_registro` varchar(45)
,`created_at` timestamp
,`status` enum('incompleto','completo','falta')
,`registrado_por` int
,`updated_at` timestamp
,`funcionario_nome` varchar(100)
,`funcionario_codigo` varchar(10)
,`cargo` varchar(50)
,`departamento` varchar(50)
,`hora_entrada_esperada` time
,`hora_saida_esperada` time
,`almoco_saida_esperada` time
,`almoco_volta_esperada` time
,`status_detalhado` varchar(11)
,`atraso_entrada` time
,`saida_antecipada` time
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `view_resumo_contas`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `view_resumo_contas` (
`tipo` enum('pagar','receber')
,`status` enum('pendente','pago_parcial','pago','vencido','cancelado')
,`quantidade` bigint
,`valor_total` decimal(32,2)
,`valor_pago_total` decimal(32,2)
,`valor_pendente_total` decimal(32,2)
,`valor_medio` decimal(14,6)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_estatisticas_listas`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_estatisticas_listas` (
`total_listas` bigint
,`rascunhos` bigint
,`enviadas` bigint
,`em_cotacao` bigint
,`finalizadas` bigint
,`canceladas` bigint
,`valor_total_estimado` decimal(32,2)
,`valor_total_final` decimal(32,2)
,`hoje` bigint
,`ultima_semana` bigint
,`ultimo_mes` bigint
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_itens_completos`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_itens_completos` (
`id` int
,`lista_id` int
,`produto_descricao` varchar(300)
,`categoria` varchar(100)
,`quantidade` decimal(10,2)
,`unidade` varchar(20)
,`preco_estimado` decimal(10,2)
,`preco_final` decimal(10,2)
,`fornecedor_sugerido_id` int
,`fornecedor_escolhido_id` int
,`observacoes` text
,`status_item` enum('pendente','cotado','aprovado','comprado')
,`ordem` int
,`data_criacao` timestamp
,`lista_nome` varchar(200)
,`lista_status` enum('rascunho','enviada','em_cotacao','finalizada','cancelada')
,`fornecedor_sugerido_nome` varchar(255)
,`fornecedor_escolhido_nome` varchar(255)
,`total_cotacoes` bigint
,`menor_preco_cotado` decimal(10,2)
,`maior_preco_cotado` decimal(10,2)
,`preco_medio_cotado` decimal(14,6)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_listas_resumo`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_listas_resumo` (
`id` int
,`nome` varchar(200)
,`descricao` text
,`status` enum('rascunho','enviada','em_cotacao','finalizada','cancelada')
,`prioridade` enum('baixa','media','alta','urgente')
,`data_criacao` timestamp
,`data_envio` timestamp
,`data_prazo` date
,`criado_por` int
,`valor_estimado` decimal(10,2)
,`valor_final` decimal(10,2)
,`observacoes` text
,`data_ultima_atualizacao` timestamp
,`total_itens` bigint
,`itens_pendentes` bigint
,`itens_cotados` bigint
,`itens_aprovados` bigint
,`itens_comprados` bigint
,`fornecedores_contatados` bigint
,`cotacoes_recebidas` bigint
);

-- --------------------------------------------------------

--
-- Estrutura para view `view_contas_vencidas`
--
DROP TABLE IF EXISTS `view_contas_vencidas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_contas_vencidas`  AS SELECT `c`.`id` AS `id`, `c`.`tipo` AS `tipo`, `c`.`categoria_id` AS `categoria_id`, `c`.`descricao` AS `descricao`, `c`.`valor_original` AS `valor_original`, `c`.`valor_pago` AS `valor_pago`, `c`.`valor_pendente` AS `valor_pendente`, `c`.`data_vencimento` AS `data_vencimento`, `c`.`data_competencia` AS `data_competencia`, `c`.`data_cadastro` AS `data_cadastro`, `c`.`status` AS `status`, `c`.`prioridade` AS `prioridade`, `c`.`cliente_id` AS `cliente_id`, `c`.`fornecedor_id` AS `fornecedor_id`, `c`.`venda_id` AS `venda_id`, `c`.`observacoes` AS `observacoes`, `c`.`documento` AS `documento`, `c`.`forma_pagamento` AS `forma_pagamento`, `c`.`recorrente` AS `recorrente`, `c`.`periodicidade` AS `periodicidade`, `c`.`dia_vencimento` AS `dia_vencimento`, `c`.`usuario_cadastro` AS `usuario_cadastro`, `c`.`created_at` AS `created_at`, `c`.`updated_at` AS `updated_at`, `cat`.`nome` AS `categoria_nome`, `cat`.`cor` AS `categoria_cor`, (to_days(curdate()) - to_days(`c`.`data_vencimento`)) AS `dias_vencido` FROM (`contas` `c` left join `categorias_contas` `cat` on((`c`.`categoria_id` = `cat`.`id`))) WHERE ((`c`.`data_vencimento` < curdate()) AND (`c`.`status` in ('pendente','pago_parcial'))) ORDER BY `c`.`data_vencimento` ASC ;

-- --------------------------------------------------------

--
-- Estrutura para view `view_estatisticas_fornecedores`
--
DROP TABLE IF EXISTS `view_estatisticas_fornecedores`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_estatisticas_fornecedores`  AS SELECT `f`.`id` AS `id`, `f`.`nome` AS `nome`, `f`.`empresa` AS `empresa`, `f`.`telefone` AS `telefone`, `f`.`status` AS `status`, `f`.`avaliacao` AS `avaliacao`, count(distinct `pf`.`id`) AS `total_pedidos`, coalesce(sum((case when (`pf`.`status` = 'entregue') then `pf`.`valor_total` end)),0) AS `valor_total_comprado`, coalesce(sum((case when (`pf`.`status` in ('pendente','confirmado','em_transito')) then `pf`.`valor_total` end)),0) AS `valor_pedidos_abertos`, max(`pf`.`data_pedido`) AS `ultimo_pedido`, avg((case when ((`pf`.`data_entrega_realizada` is not null) and (`pf`.`data_entrega_prevista` is not null)) then (to_days(`pf`.`data_entrega_realizada`) - to_days(`pf`.`data_entrega_prevista`)) end)) AS `media_atraso_dias`, count((case when ((`pf`.`status` = 'entregue') and (`pf`.`data_entrega_realizada` <= `pf`.`data_entrega_prevista`)) then 1 end)) AS `entregas_no_prazo`, count((case when (`pf`.`status` = 'entregue') then 1 end)) AS `total_entregas` FROM (`fornecedores` `f` left join `pedidos_fornecedores` `pf` on((`f`.`id` = `pf`.`fornecedor_id`))) GROUP BY `f`.`id`, `f`.`nome`, `f`.`empresa`, `f`.`telefone`, `f`.`status`, `f`.`avaliacao` ;

-- --------------------------------------------------------

--
-- Estrutura para view `view_fluxo_caixa`
--
DROP TABLE IF EXISTS `view_fluxo_caixa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_fluxo_caixa`  AS SELECT date_format(`contas`.`data_vencimento`,'%Y-%m') AS `mes_ano`, sum((case when (`contas`.`tipo` = 'receber') then `contas`.`valor_pendente` else 0 end)) AS `receitas_previstas`, sum((case when (`contas`.`tipo` = 'pagar') then `contas`.`valor_pendente` else 0 end)) AS `despesas_previstas`, (sum((case when (`contas`.`tipo` = 'receber') then `contas`.`valor_pendente` else 0 end)) - sum((case when (`contas`.`tipo` = 'pagar') then `contas`.`valor_pendente` else 0 end))) AS `saldo_previsto` FROM `contas` WHERE (`contas`.`status` in ('pendente','pago_parcial')) GROUP BY date_format(`contas`.`data_vencimento`,'%Y-%m') ORDER BY `mes_ano` ASC ;

-- --------------------------------------------------------

--
-- Estrutura para view `view_ponto_completo`
--
DROP TABLE IF EXISTS `view_ponto_completo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_ponto_completo`  AS SELECT `pr`.`id` AS `id`, `pr`.`funcionario_id` AS `funcionario_id`, `pr`.`data_registro` AS `data_registro`, `pr`.`entrada_manha` AS `entrada_manha`, `pr`.`saida_almoco` AS `saida_almoco`, `pr`.`entrada_tarde` AS `entrada_tarde`, `pr`.`saida_final` AS `saida_final`, `pr`.`horas_trabalhadas` AS `horas_trabalhadas`, `pr`.`horas_extras` AS `horas_extras`, `pr`.`observacoes` AS `observacoes`, `pr`.`ip_registro` AS `ip_registro`, `pr`.`created_at` AS `created_at`, `pr`.`status` AS `status`, `pr`.`registrado_por` AS `registrado_por`, `pr`.`updated_at` AS `updated_at`, `f`.`nome` AS `funcionario_nome`, `f`.`codigo` AS `funcionario_codigo`, `f`.`cargo` AS `cargo`, `f`.`departamento` AS `departamento`, `ht`.`hora_entrada` AS `hora_entrada_esperada`, `ht`.`hora_saida` AS `hora_saida_esperada`, `ht`.`hora_almoco_saida` AS `almoco_saida_esperada`, `ht`.`hora_almoco_volta` AS `almoco_volta_esperada`, (case when (`pr`.`entrada_manha` is null) then 'Falta' when (`pr`.`saida_final` is null) then 'Em trabalho' when (`pr`.`status` = 'completo') then 'Completo' else 'Incompleto' end) AS `status_detalhado`, (case when (`pr`.`entrada_manha` > `ht`.`hora_entrada`) then timediff(`pr`.`entrada_manha`,`ht`.`hora_entrada`) else NULL end) AS `atraso_entrada`, (case when ((`pr`.`saida_final` < `ht`.`hora_saida`) and (`pr`.`saida_final` is not null)) then timediff(`ht`.`hora_saida`,`pr`.`saida_final`) else NULL end) AS `saida_antecipada` FROM ((`ponto_registros` `pr` join `funcionarios` `f` on((`pr`.`funcionario_id` = `f`.`id`))) left join `horarios_trabalho` `ht` on(((`f`.`id` = `ht`.`funcionario_id`) and (`ht`.`dia_semana` = dayofweek(`pr`.`data_registro`)) and (`ht`.`ativo` = true)))) ;

-- --------------------------------------------------------

--
-- Estrutura para view `view_resumo_contas`
--
DROP TABLE IF EXISTS `view_resumo_contas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_resumo_contas`  AS SELECT `contas`.`tipo` AS `tipo`, `contas`.`status` AS `status`, count(0) AS `quantidade`, sum(`contas`.`valor_original`) AS `valor_total`, sum(`contas`.`valor_pago`) AS `valor_pago_total`, sum(`contas`.`valor_pendente`) AS `valor_pendente_total`, avg(`contas`.`valor_original`) AS `valor_medio` FROM `contas` WHERE (`contas`.`status` <> 'cancelado') GROUP BY `contas`.`tipo`, `contas`.`status` ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_estatisticas_listas`
--
DROP TABLE IF EXISTS `v_estatisticas_listas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_estatisticas_listas`  AS SELECT count(0) AS `total_listas`, count((case when (`listas_compras`.`status` = 'rascunho') then 1 end)) AS `rascunhos`, count((case when (`listas_compras`.`status` = 'enviada') then 1 end)) AS `enviadas`, count((case when (`listas_compras`.`status` = 'em_cotacao') then 1 end)) AS `em_cotacao`, count((case when (`listas_compras`.`status` = 'finalizada') then 1 end)) AS `finalizadas`, count((case when (`listas_compras`.`status` = 'cancelada') then 1 end)) AS `canceladas`, coalesce(sum(`listas_compras`.`valor_estimado`),0) AS `valor_total_estimado`, coalesce(sum(`listas_compras`.`valor_final`),0) AS `valor_total_final`, count((case when (cast(`listas_compras`.`data_criacao` as date) = curdate()) then 1 end)) AS `hoje`, count((case when (`listas_compras`.`data_criacao` >= (curdate() - interval 7 day)) then 1 end)) AS `ultima_semana`, count((case when (`listas_compras`.`data_criacao` >= (curdate() - interval 30 day)) then 1 end)) AS `ultimo_mes` FROM `listas_compras` ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_itens_completos`
--
DROP TABLE IF EXISTS `v_itens_completos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_itens_completos`  AS SELECT `i`.`id` AS `id`, `i`.`lista_id` AS `lista_id`, `i`.`produto_descricao` AS `produto_descricao`, `i`.`categoria` AS `categoria`, `i`.`quantidade` AS `quantidade`, `i`.`unidade` AS `unidade`, `i`.`preco_estimado` AS `preco_estimado`, `i`.`preco_final` AS `preco_final`, `i`.`fornecedor_sugerido_id` AS `fornecedor_sugerido_id`, `i`.`fornecedor_escolhido_id` AS `fornecedor_escolhido_id`, `i`.`observacoes` AS `observacoes`, `i`.`status_item` AS `status_item`, `i`.`ordem` AS `ordem`, `i`.`data_criacao` AS `data_criacao`, `l`.`nome` AS `lista_nome`, `l`.`status` AS `lista_status`, `fs`.`nome` AS `fornecedor_sugerido_nome`, `fe`.`nome` AS `fornecedor_escolhido_nome`, count(`c`.`id`) AS `total_cotacoes`, min(`c`.`preco_unitario`) AS `menor_preco_cotado`, max(`c`.`preco_unitario`) AS `maior_preco_cotado`, avg(`c`.`preco_unitario`) AS `preco_medio_cotado` FROM ((((`itens_lista_compras` `i` join `listas_compras` `l` on((`i`.`lista_id` = `l`.`id`))) left join `fornecedores` `fs` on((`i`.`fornecedor_sugerido_id` = `fs`.`id`))) left join `fornecedores` `fe` on((`i`.`fornecedor_escolhido_id` = `fe`.`id`))) left join `cotacoes_fornecedores` `c` on((`i`.`id` = `c`.`item_lista_id`))) GROUP BY `i`.`id` ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_listas_resumo`
--
DROP TABLE IF EXISTS `v_listas_resumo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_listas_resumo`  AS SELECT `l`.`id` AS `id`, `l`.`nome` AS `nome`, `l`.`descricao` AS `descricao`, `l`.`status` AS `status`, `l`.`prioridade` AS `prioridade`, `l`.`data_criacao` AS `data_criacao`, `l`.`data_envio` AS `data_envio`, `l`.`data_prazo` AS `data_prazo`, `l`.`criado_por` AS `criado_por`, `l`.`valor_estimado` AS `valor_estimado`, `l`.`valor_final` AS `valor_final`, `l`.`observacoes` AS `observacoes`, `l`.`data_ultima_atualizacao` AS `data_ultima_atualizacao`, count(`i`.`id`) AS `total_itens`, count((case when (`i`.`status_item` = 'pendente') then 1 end)) AS `itens_pendentes`, count((case when (`i`.`status_item` = 'cotado') then 1 end)) AS `itens_cotados`, count((case when (`i`.`status_item` = 'aprovado') then 1 end)) AS `itens_aprovados`, count((case when (`i`.`status_item` = 'comprado') then 1 end)) AS `itens_comprados`, count(distinct `e`.`fornecedor_id`) AS `fornecedores_contatados`, count((case when (`e`.`status_resposta` = 'cotacao_recebida') then 1 end)) AS `cotacoes_recebidas` FROM ((`listas_compras` `l` left join `itens_lista_compras` `i` on((`l`.`id` = `i`.`lista_id`))) left join `envios_lista_fornecedores` `e` on((`l`.`id` = `e`.`lista_id`))) GROUP BY `l`.`id` ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `anexos_contas`
--
ALTER TABLE `anexos_contas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conta` (`conta_id`),
  ADD KEY `usuario_upload` (`usuario_upload`);

--
-- Índices de tabela `categorias_contas`
--
ALTER TABLE `categorias_contas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `categorias_fornecedores`
--
ALTER TABLE `categorias_fornecedores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `comunicacoes_fornecedores`
--
ALTER TABLE `comunicacoes_fornecedores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_fornecedor` (`fornecedor_id`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_data` (`data_comunicacao`),
  ADD KEY `idx_comunicacoes_data` (`data_comunicacao` DESC);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`);

--
-- Índices de tabela `configuracoes_ponto`
--
ALTER TABLE `configuracoes_ponto`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `contas`
--
ALTER TABLE `contas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_vencimento` (`data_vencimento`),
  ADD KEY `idx_categoria` (`categoria_id`),
  ADD KEY `idx_cliente` (`cliente_id`),
  ADD KEY `idx_fornecedor` (`fornecedor_id`),
  ADD KEY `usuario_cadastro` (`usuario_cadastro`),
  ADD KEY `idx_contas_competencia` (`data_competencia`),
  ADD KEY `idx_contas_tipo_status` (`tipo`,`status`),
  ADD KEY `idx_contas_recorrente` (`recorrente`);

--
-- Índices de tabela `contatos_fornecedores`
--
ALTER TABLE `contatos_fornecedores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fornecedor` (`fornecedor_id`),
  ADD KEY `idx_principal` (`eh_principal`);

--
-- Índices de tabela `cotacoes_fornecedores`
--
ALTER TABLE `cotacoes_fornecedores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_envio_id` (`envio_id`),
  ADD KEY `idx_item_lista_id` (`item_lista_id`),
  ADD KEY `idx_data_cotacao` (`data_cotacao`),
  ADD KEY `idx_cotacoes_preco` (`preco_unitario`);

--
-- Índices de tabela `envios_lista_fornecedores`
--
ALTER TABLE `envios_lista_fornecedores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_lista_fornecedor` (`lista_id`,`fornecedor_id`),
  ADD KEY `fornecedor_id` (`fornecedor_id`),
  ADD KEY `idx_lista_fornecedor` (`lista_id`,`fornecedor_id`),
  ADD KEY `idx_status_resposta` (`status_resposta`),
  ADD KEY `idx_data_envio` (`data_envio`),
  ADD KEY `idx_envios_data_resposta` (`data_resposta`);

--
-- Índices de tabela `extras_tipos`
--
ALTER TABLE `extras_tipos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_extras_tipos_ativo` (`ativo`);

--
-- Índices de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cadastrado_por` (`cadastrado_por`),
  ADD KEY `idx_nome` (`nome`),
  ADD KEY `idx_empresa` (`empresa`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_tipo` (`tipo_fornecedor`),
  ADD KEY `idx_fornecedores_busca` (`nome`,`empresa`,`cnpj`);

--
-- Índices de tabela `fornecedor_categorias`
--
ALTER TABLE `fornecedor_categorias`
  ADD PRIMARY KEY (`fornecedor_id`,`categoria_id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Índices de tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- Índices de tabela `funcionarios_extras`
--
ALTER TABLE `funcionarios_extras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_funcionario_extra_mes` (`funcionario_id`,`extra_tipo_id`,`mes_referencia`),
  ADD KEY `extra_tipo_id` (`extra_tipo_id`),
  ADD KEY `concedido_por` (`concedido_por`),
  ADD KEY `idx_funcionarios_extras_mes` (`mes_referencia`),
  ADD KEY `idx_funcionarios_extras_funcionario` (`funcionario_id`);

--
-- Índices de tabela `historico_contas`
--
ALTER TABLE `historico_contas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conta` (`conta_id`),
  ADD KEY `idx_acao` (`acao`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `historico_listas_compras`
--
ALTER TABLE `historico_listas_compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lista_id` (`lista_id`),
  ADD KEY `idx_data_acao` (`data_acao`);

--
-- Índices de tabela `horarios_trabalho`
--
ALTER TABLE `horarios_trabalho`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_funcionario_dia` (`funcionario_id`,`dia_semana`),
  ADD KEY `idx_funcionario` (`funcionario_id`),
  ADD KEY `idx_dia_semana` (`dia_semana`);

--
-- Índices de tabela `itens_lista_compras`
--
ALTER TABLE `itens_lista_compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fornecedor_escolhido_id` (`fornecedor_escolhido_id`),
  ADD KEY `idx_lista_id` (`lista_id`),
  ADD KEY `idx_categoria` (`categoria`),
  ADD KEY `idx_status_item` (`status_item`),
  ADD KEY `idx_itens_fornecedor_sugerido` (`fornecedor_sugerido_id`),
  ADD KEY `idx_itens_categoria` (`categoria`);

--
-- Índices de tabela `itens_pedido_fornecedor`
--
ALTER TABLE `itens_pedido_fornecedor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pedido` (`pedido_id`),
  ADD KEY `idx_produto` (`produto_id`);

--
-- Índices de tabela `itens_venda`
--
ALTER TABLE `itens_venda`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venda_id` (`venda_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `listas_compras`
--
ALTER TABLE `listas_compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_data_criacao` (`data_criacao`),
  ADD KEY `idx_criado_por` (`criado_por`),
  ADD KEY `idx_listas_data_prazo` (`data_prazo`),
  ADD KEY `idx_listas_prioridade` (`prioridade`);

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
-- Índices de tabela `pagamentos_contas`
--
ALTER TABLE `pagamentos_contas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conta` (`conta_id`),
  ADD KEY `idx_data` (`data_pagamento`),
  ADD KEY `usuario_registro` (`usuario_registro`),
  ADD KEY `idx_pagamentos_data_forma` (`data_pagamento`,`forma_pagamento`);

--
-- Índices de tabela `pedidos_fornecedores`
--
ALTER TABLE `pedidos_fornecedores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_fornecedor` (`fornecedor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_data_pedido` (`data_pedido`),
  ADD KEY `idx_numero_pedido` (`numero_pedido`),
  ADD KEY `idx_pedidos_status_data` (`status`,`data_pedido`);

--
-- Índices de tabela `ponto_registros`
--
ALTER TABLE `ponto_registros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_funcionario_data` (`funcionario_id`,`data_registro`),
  ADD KEY `idx_data_registro` (`data_registro`),
  ADD KEY `idx_created_at` (`created_at`);

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
-- AUTO_INCREMENT de tabela `anexos_contas`
--
ALTER TABLE `anexos_contas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categorias_contas`
--
ALTER TABLE `categorias_contas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `categorias_fornecedores`
--
ALTER TABLE `categorias_fornecedores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT de tabela `comunicacoes_fornecedores`
--
ALTER TABLE `comunicacoes_fornecedores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `configuracoes_ponto`
--
ALTER TABLE `configuracoes_ponto`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `contas`
--
ALTER TABLE `contas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `contatos_fornecedores`
--
ALTER TABLE `contatos_fornecedores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cotacoes_fornecedores`
--
ALTER TABLE `cotacoes_fornecedores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `envios_lista_fornecedores`
--
ALTER TABLE `envios_lista_fornecedores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `extras_tipos`
--
ALTER TABLE `extras_tipos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `funcionarios_extras`
--
ALTER TABLE `funcionarios_extras`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `historico_contas`
--
ALTER TABLE `historico_contas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `historico_listas_compras`
--
ALTER TABLE `historico_listas_compras`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `horarios_trabalho`
--
ALTER TABLE `horarios_trabalho`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `itens_lista_compras`
--
ALTER TABLE `itens_lista_compras`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `itens_pedido_fornecedor`
--
ALTER TABLE `itens_pedido_fornecedor`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `itens_venda`
--
ALTER TABLE `itens_venda`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2566;

--
-- AUTO_INCREMENT de tabela `listas_compras`
--
ALTER TABLE `listas_compras`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `logs_sistema`
--
ALTER TABLE `logs_sistema`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1027;

--
-- AUTO_INCREMENT de tabela `pagamentos_contas`
--
ALTER TABLE `pagamentos_contas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pedidos_fornecedores`
--
ALTER TABLE `pedidos_fornecedores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `ponto_registros`
--
ALTER TABLE `ponto_registros`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=265;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `vendas`
--
ALTER TABLE `vendas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1274;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `anexos_contas`
--
ALTER TABLE `anexos_contas`
  ADD CONSTRAINT `anexos_contas_ibfk_1` FOREIGN KEY (`conta_id`) REFERENCES `contas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `anexos_contas_ibfk_2` FOREIGN KEY (`usuario_upload`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `comunicacoes_fornecedores`
--
ALTER TABLE `comunicacoes_fornecedores`
  ADD CONSTRAINT `comunicacoes_fornecedores_ibfk_1` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comunicacoes_fornecedores_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `contas`
--
ALTER TABLE `contas`
  ADD CONSTRAINT `contas_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_contas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contas_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contas_ibfk_3` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contas_ibfk_4` FOREIGN KEY (`usuario_cadastro`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `contatos_fornecedores`
--
ALTER TABLE `contatos_fornecedores`
  ADD CONSTRAINT `contatos_fornecedores_ibfk_1` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `cotacoes_fornecedores`
--
ALTER TABLE `cotacoes_fornecedores`
  ADD CONSTRAINT `cotacoes_fornecedores_ibfk_1` FOREIGN KEY (`envio_id`) REFERENCES `envios_lista_fornecedores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cotacoes_fornecedores_ibfk_2` FOREIGN KEY (`item_lista_id`) REFERENCES `itens_lista_compras` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `envios_lista_fornecedores`
--
ALTER TABLE `envios_lista_fornecedores`
  ADD CONSTRAINT `envios_lista_fornecedores_ibfk_1` FOREIGN KEY (`lista_id`) REFERENCES `listas_compras` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `envios_lista_fornecedores_ibfk_2` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD CONSTRAINT `fornecedores_ibfk_1` FOREIGN KEY (`cadastrado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `fornecedor_categorias`
--
ALTER TABLE `fornecedor_categorias`
  ADD CONSTRAINT `fornecedor_categorias_ibfk_1` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fornecedor_categorias_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_fornecedores` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `funcionarios_extras`
--
ALTER TABLE `funcionarios_extras`
  ADD CONSTRAINT `funcionarios_extras_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `funcionarios_extras_ibfk_2` FOREIGN KEY (`extra_tipo_id`) REFERENCES `extras_tipos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `funcionarios_extras_ibfk_3` FOREIGN KEY (`concedido_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `historico_contas`
--
ALTER TABLE `historico_contas`
  ADD CONSTRAINT `historico_contas_ibfk_1` FOREIGN KEY (`conta_id`) REFERENCES `contas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historico_contas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `historico_listas_compras`
--
ALTER TABLE `historico_listas_compras`
  ADD CONSTRAINT `historico_listas_compras_ibfk_1` FOREIGN KEY (`lista_id`) REFERENCES `listas_compras` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `horarios_trabalho`
--
ALTER TABLE `horarios_trabalho`
  ADD CONSTRAINT `horarios_trabalho_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `itens_lista_compras`
--
ALTER TABLE `itens_lista_compras`
  ADD CONSTRAINT `itens_lista_compras_ibfk_1` FOREIGN KEY (`lista_id`) REFERENCES `listas_compras` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `itens_lista_compras_ibfk_2` FOREIGN KEY (`fornecedor_sugerido_id`) REFERENCES `fornecedores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `itens_lista_compras_ibfk_3` FOREIGN KEY (`fornecedor_escolhido_id`) REFERENCES `fornecedores` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `itens_pedido_fornecedor`
--
ALTER TABLE `itens_pedido_fornecedor`
  ADD CONSTRAINT `itens_pedido_fornecedor_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos_fornecedores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `itens_pedido_fornecedor_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE SET NULL;

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
-- Restrições para tabelas `pagamentos_contas`
--
ALTER TABLE `pagamentos_contas`
  ADD CONSTRAINT `pagamentos_contas_ibfk_1` FOREIGN KEY (`conta_id`) REFERENCES `contas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pagamentos_contas_ibfk_2` FOREIGN KEY (`usuario_registro`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `pedidos_fornecedores`
--
ALTER TABLE `pedidos_fornecedores`
  ADD CONSTRAINT `pedidos_fornecedores_ibfk_1` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedidos_fornecedores_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `ponto_registros`
--
ALTER TABLE `ponto_registros`
  ADD CONSTRAINT `ponto_registros_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`);

--
-- Restrições para tabelas `vendas`
--
ALTER TABLE `vendas`
  ADD CONSTRAINT `vendas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
