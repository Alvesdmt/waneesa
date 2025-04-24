-- Tabela de Clientes
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    endereco TEXT NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Vendas a Prazo
CREATE TABLE IF NOT EXISTS vendas_prazo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    parcelas INT NOT NULL,
    data_vencimento DATE NOT NULL,
    juros DECIMAL(5,2) NOT NULL,
    status ENUM('pendente', 'pago', 'atrasado') DEFAULT 'pendente',
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

-- Tabela de Parcelas
CREATE TABLE IF NOT EXISTS parcelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venda_id INT NOT NULL,
    numero_parcela INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE,
    valor_pago DECIMAL(10,2),
    juros DECIMAL(10,2) DEFAULT 0,
    status ENUM('pendente', 'pago', 'atrasado') DEFAULT 'pendente',
    FOREIGN KEY (venda_id) REFERENCES vendas_prazo(id)
); 