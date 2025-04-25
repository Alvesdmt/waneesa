-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 25/04/2025 às 16:23
-- Versão do servidor: 10.4.28-MariaDB
-- Versão do PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `waneesa_db`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `caixa`
--

CREATE TABLE `caixa` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `valor_inicial` decimal(10,2) NOT NULL,
  `data_abertura` datetime NOT NULL,
  `data_fechamento` datetime DEFAULT NULL,
  `status` enum('aberto','fechado') NOT NULL,
  `valor_final` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `caixa`
--

INSERT INTO `caixa` (`id`, `usuario_id`, `valor_inicial`, `data_abertura`, `data_fechamento`, `status`, `valor_final`) VALUES
(1, 2, 39.00, '2025-04-23 08:17:12', '2025-04-23 15:00:08', 'fechado', 39.00),
(2, 2, 109.00, '2025-04-23 15:01:18', '2025-04-23 15:27:48', 'fechado', 109.00),
(3, 2, 0.00, '2025-04-23 15:27:53', '2025-04-23 15:29:55', 'fechado', 0.00),
(4, 2, 89.00, '2025-04-23 15:30:13', '2025-04-23 15:39:11', 'fechado', 89.00),
(5, 2, 0.00, '2025-04-23 15:39:23', NULL, 'aberto', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `carnes`
--

CREATE TABLE `carnes` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `num_parcelas` int(11) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_inicio` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `carnes`
--

INSERT INTO `carnes` (`id`, `cliente_id`, `valor_total`, `num_parcelas`, `data_cadastro`, `data_inicio`, `observacoes`) VALUES
(1, 6, 332.00, 3, '2025-04-23 13:28:50', '2025-05-23', ''),
(2, 7, 765.00, 6, '2025-04-23 16:35:54', '2025-05-23', ''),
(3, 8, 76.00, 3, '2025-04-23 16:38:44', '2025-06-23', ''),
(4, 9, 212.00, 2, '2025-04-23 16:49:46', '2025-05-23', '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `telefone` varchar(15) NOT NULL,
  `endereco` varchar(200) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `estado` char(2) NOT NULL,
  `cep` varchar(9) DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome`, `cpf`, `telefone`, `endereco`, `cidade`, `estado`, `cep`, `data_cadastro`) VALUES
(6, 'HINGHEL ALVES SANTOS', '704.428.836-76', '(38) 99220-0217', 'Quadra QNB 13', 'Brasília', 'DF', '72115-130', '2025-04-23 13:28:50'),
(7, 'HINGHEL ALVES SANTOS', '704.428.836-76', '(38) 99220-0217', 'Quadra QNB 13', 'Brasília', 'DF', '72115-130', '2025-04-23 16:35:54'),
(8, 'HINGHEL ALVES SANTOS', '704.428.836-76', '(38) 99220-0217', 'Quadra QNB 13', 'Brasília', 'DF', '72115-130', '2025-04-23 16:38:44'),
(9, 'Cleia marcia', '925.418.601-57', '(38) 99103-0722', 'Quadra QNB 13', 'Brasília', 'DF', '72115-130', '2025-04-23 16:49:46');

-- --------------------------------------------------------

--
-- Estrutura para tabela `compras`
--

CREATE TABLE `compras` (
  `id` int(11) NOT NULL,
  `fornecedor` varchar(100) NOT NULL,
  `data_compra` date NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `status` enum('Pendente','Concluída','Cancelada') NOT NULL DEFAULT 'Pendente',
  `tipo_custo` enum('fixo','variavel','operacional') DEFAULT 'operacional',
  `categoria` varchar(50) DEFAULT 'outros',
  `descricao` text DEFAULT NULL,
  `forma_pagamento` varchar(20) DEFAULT 'dinheiro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `compras`
--

INSERT INTO `compras` (`id`, `fornecedor`, `data_compra`, `valor_total`, `status`, `tipo_custo`, `categoria`, `descricao`, `forma_pagamento`) VALUES
(1, 'Compras mercadoria', '2025-04-23', 1000.00, 'Concluída', 'operacional', 'outros', NULL, 'dinheiro'),
(3, 'mesas', '2025-04-23', 200.00, 'Concluída', 'operacional', 'outros', NULL, 'dinheiro'),
(4, 'aluguel', '2025-04-23', 1200.00, 'Concluída', 'operacional', 'outros', NULL, 'dinheiro'),
(5, 'mesas', '2025-04-23', 234.00, 'Concluída', 'fixo', 'outros', NULL, 'dinheiro'),
(6, 'Compras mercadoria', '2025-04-23', 364.00, 'Concluída', 'operacional', 'outros', NULL, 'dinheiro'),
(7, 'aluguel', '2025-04-23', 432.00, 'Concluída', 'variavel', 'outros', NULL, 'dinheiro'),
(8, 'cartao', '2025-04-23', 398.00, 'Concluída', 'variavel', 'outros', NULL, 'dinheiro'),
(9, 'agua', '2025-04-23', 23.00, 'Concluída', 'fixo', 'outros', NULL, 'dinheiro'),
(10, 'asdasd', '2025-04-23', 231.00, 'Concluída', 'variavel', 'outros', NULL, 'dinheiro'),
(11, 'asdasd', '2025-04-23', 123.00, 'Concluída', 'fixo', 'outros', NULL, 'dinheiro'),
(12, 'jjnj', '2025-04-23', 99.00, 'Concluída', 'fixo', 'outros', NULL, 'dinheiro'),
(13, ',mj', '2025-04-23', 39.00, 'Concluída', 'variavel', 'outros', NULL, 'dinheiro'),
(14, 'nmhjjh', '2025-04-23', 57.00, 'Concluída', 'operacional', 'outros', NULL, 'dinheiro');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `itens_venda`
--

INSERT INTO `itens_venda` (`id`, `venda_id`, `produto_id`, `quantidade`, `valor_unitario`) VALUES
(2, 2, 1, 1, 39.00),
(3, 3, 2, 1, 25.00),
(4, 4, 2, 1, 25.00),
(5, 5, 2, 2, 25.00),
(6, 6, 1, 1, 39.00),
(7, 7, 2, 1, 25.00),
(8, 8, 2, 1, 25.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes_caixa`
--

CREATE TABLE `movimentacoes_caixa` (
  `id` int(11) NOT NULL,
  `caixa_id` int(11) NOT NULL,
  `venda_id` int(11) DEFAULT NULL,
  `tipo` enum('entrada','saida') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `data_movimentacao` datetime DEFAULT current_timestamp(),
  `forma_pagamento` enum('dinheiro','cartao_credito','cartao_debito','pix') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `parcelas`
--

CREATE TABLE `parcelas` (
  `id` int(11) NOT NULL,
  `carne_id` int(11) NOT NULL,
  `numero_parcela` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pendente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `parcelas`
--

INSERT INTO `parcelas` (`id`, `carne_id`, `numero_parcela`, `valor`, `data_vencimento`, `data_pagamento`, `status`) VALUES
(1, 1, 1, 110.67, '2025-06-23', '2025-04-23', 'Pago'),
(2, 1, 2, 110.67, '2025-07-23', '2025-04-23', 'Pago'),
(3, 1, 3, 110.67, '2025-03-07', NULL, 'Pendente'),
(4, 2, 1, 127.50, '2025-06-23', '2025-04-23', 'Pago'),
(5, 2, 2, 127.50, '2025-07-23', NULL, 'Pendente'),
(6, 2, 3, 127.50, '2025-08-23', NULL, 'Pendente'),
(7, 2, 4, 127.50, '2025-09-23', NULL, 'Pendente'),
(8, 2, 5, 127.50, '2025-10-23', NULL, 'Pendente'),
(9, 2, 6, 127.50, '2025-11-23', NULL, 'Pendente'),
(10, 3, 1, 25.33, '2025-07-23', NULL, 'Pendente'),
(11, 3, 2, 25.33, '2025-08-23', NULL, 'Pendente'),
(12, 3, 3, 25.33, '2025-09-23', NULL, 'Pendente'),
(13, 4, 1, 106.00, '2025-06-23', '2025-04-23', 'Pago'),
(14, 4, 2, 106.00, '2025-07-23', '2025-04-23', 'Pago');

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `codigo_barras` varchar(13) DEFAULT NULL,
  `preco_custo` decimal(10,2) NOT NULL,
  `preco_venda` decimal(10,2) NOT NULL,
  `estoque` int(11) DEFAULT 0,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `nome`, `descricao`, `codigo_barras`, `preco_custo`, `preco_venda`, `estoque`, `foto`, `status`) VALUES
(1, 'Camisa masculina', '', '681838', 29.00, 39.00, 3, NULL, 'ativo'),
(2, 'Bola de basquete', '', '046194', 12.00, 25.00, -1, NULL, 'ativo'),
(3, 'kkk', 'kkk', '2284586546435', 78.00, 999.00, 2, NULL, 'ativo'),
(4, 'A10', '', '108246', 2.00, 22.00, 2, '680a34fe93d6a.jpeg', 'ativo'),
(5, 'asdasd', '', '544335', 54.00, 23.00, 3, NULL, 'ativo'),
(6, 'REDMI 13C', '', '3124124', 12.00, 23.00, 3, NULL, 'ativo');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` enum('admin','funcionario') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `tipo`, `created_at`) VALUES
(2, 'hinghel Alves santos', 'dev@gmail.com', '$2y$10$CI6JrSjEEFRy/GOB6JAZpupXUU3rin.0rb/UuYW34bpLRAfcVWL8y', 'admin', '2025-04-23 02:11:22'),
(5, 'HINGHEL ALVES SANTOS', 'devv@gmail.com', '$2y$10$1PTMrj6ooCZ5IT/Tloa6POEMbQpnUdHFZ/vYnWx60fDKHFhh1YrFi', 'funcionario', '2025-04-24 14:28:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `vendas`
--

CREATE TABLE `vendas` (
  `id` int(11) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `forma_pagamento` varchar(20) NOT NULL,
  `data_venda` datetime NOT NULL,
  `status` varchar(20) DEFAULT 'concluida',
  `caixa_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `vendas`
--

INSERT INTO `vendas` (`id`, `valor_total`, `forma_pagamento`, `data_venda`, `status`, `caixa_id`) VALUES
(2, 39.00, 'dinheiro', '2025-04-23 19:58:35', 'concluida', 1),
(3, 25.00, 'dinheiro', '2025-04-23 20:01:26', 'concluida', 2),
(4, 25.00, 'dinheiro', '2025-04-23 20:13:31', 'concluida', 2),
(5, 50.00, 'dinheiro', '2025-04-23 15:15:34', 'concluida', 2),
(6, 39.00, 'cartao', '2025-04-23 15:30:26', 'concluida', 4),
(7, 25.00, 'dinheiro', '2025-04-23 15:32:19', 'concluida', 4),
(8, 25.00, 'pix', '2025-04-23 15:32:28', 'concluida', 4);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `caixa`
--
ALTER TABLE `caixa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `carnes`
--
ALTER TABLE `carnes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `itens_venda`
--
ALTER TABLE `itens_venda`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venda_id` (`venda_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `movimentacoes_caixa`
--
ALTER TABLE `movimentacoes_caixa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `caixa_id` (`caixa_id`),
  ADD KEY `venda_id` (`venda_id`);

--
-- Índices de tabela `parcelas`
--
ALTER TABLE `parcelas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `carne_id` (`carne_id`);

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
  ADD KEY `caixa_id` (`caixa_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `caixa`
--
ALTER TABLE `caixa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `carnes`
--
ALTER TABLE `carnes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `compras`
--
ALTER TABLE `compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `itens_venda`
--
ALTER TABLE `itens_venda`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `movimentacoes_caixa`
--
ALTER TABLE `movimentacoes_caixa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `parcelas`
--
ALTER TABLE `parcelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `vendas`
--
ALTER TABLE `vendas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `caixa`
--
ALTER TABLE `caixa`
  ADD CONSTRAINT `caixa_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `itens_venda`
--
ALTER TABLE `itens_venda`
  ADD CONSTRAINT `itens_venda_ibfk_1` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`),
  ADD CONSTRAINT `itens_venda_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);

--
-- Restrições para tabelas `movimentacoes_caixa`
--
ALTER TABLE `movimentacoes_caixa`
  ADD CONSTRAINT `movimentacoes_caixa_ibfk_1` FOREIGN KEY (`caixa_id`) REFERENCES `caixa` (`id`),
  ADD CONSTRAINT `movimentacoes_caixa_ibfk_2` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`);

--
-- Restrições para tabelas `parcelas`
--
ALTER TABLE `parcelas`
  ADD CONSTRAINT `parcelas_ibfk_1` FOREIGN KEY (`carne_id`) REFERENCES `carnes` (`id`);

--
-- Restrições para tabelas `vendas`
--
ALTER TABLE `vendas`
  ADD CONSTRAINT `vendas_ibfk_1` FOREIGN KEY (`caixa_id`) REFERENCES `caixa` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
