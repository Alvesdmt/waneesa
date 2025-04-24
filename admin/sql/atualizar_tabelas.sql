-- Atualiza a tabela carnes
ALTER TABLE carnes
ADD COLUMN IF NOT EXISTS num_parcelas INT NOT NULL AFTER valor_total,
ADD COLUMN IF NOT EXISTS data_inicio DATE NOT NULL AFTER num_parcelas,
ADD COLUMN IF NOT EXISTS observacoes TEXT AFTER data_inicio,
ADD COLUMN IF NOT EXISTS data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Cria a tabela clientes se não existir
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    telefone VARCHAR(15) NOT NULL,
    endereco VARCHAR(200) NOT NULL,
    cidade VARCHAR(100) NOT NULL,
    estado CHAR(2) NOT NULL,
    cep VARCHAR(9),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cria a tabela parcelas se não existir
CREATE TABLE IF NOT EXISTS parcelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carne_id INT NOT NULL,
    numero_parcela INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE DEFAULT NULL,
    status ENUM('Pendente', 'Paga', 'Atrasada', 'Cancelada') DEFAULT 'Pendente',
    FOREIGN KEY (carne_id) REFERENCES carnes(id)
); 