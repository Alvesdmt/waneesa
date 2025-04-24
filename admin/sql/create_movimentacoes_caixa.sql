-- Tabela de movimentações do caixa
CREATE TABLE IF NOT EXISTS movimentacoes_caixa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caixa_id INT NOT NULL,
    venda_id INT,
    tipo ENUM('entrada', 'saida') NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    descricao VARCHAR(255),
    data_movimentacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    forma_pagamento ENUM('dinheiro', 'cartao_credito', 'cartao_debito', 'pix') NOT NULL,
    FOREIGN KEY (caixa_id) REFERENCES caixa(id),
    FOREIGN KEY (venda_id) REFERENCES vendas(id)
); 