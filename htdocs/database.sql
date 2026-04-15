-- Banco de Dados do StockControl ERP (TCC)
CREATE DATABASE IF NOT EXISTS easystock_db;
USE easystock_db;

-- Tabela de Usuários (Acesso ao Sistema)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    perfil ENUM('admin', 'caixa') DEFAULT 'caixa'
);

-- Tabela de Clientes
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    telefone VARCHAR(20),
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Fornecedores
CREATE TABLE IF NOT EXISTS fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cnpj VARCHAR(20),
    telefone VARCHAR(20),
    email VARCHAR(255),
    status ENUM('ativo', 'inativo') DEFAULT 'ativo'
);

-- Tabela de Produtos (Estoque Principal)
CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    categoria VARCHAR(100),
    quantidade INT DEFAULT 0,
    estoque_minimo INT DEFAULT 5,
    validade DATE NULL,
    preco_venda DECIMAL(10,2) NOT NULL,
    preco_compra DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    fornecedor_id INT,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL
);

-- Tabela Central de Movimentações (Entradas, Saídas, Perdas, Devoluções)
CREATE TABLE IF NOT EXISTS movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    tipo ENUM('entrada_compra', 'saida_venda', 'devolucao_cliente', 'devolucao_fornecedor', 'perda') NOT NULL,
    quantidade INT NOT NULL,
    data_movimento DATETIME DEFAULT CURRENT_TIMESTAMP,
    observacao VARCHAR(255),
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);

-- Tabela de Vendas
CREATE TABLE IF NOT EXISTS vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT,
    data_venda DATETIME DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL,
    forma_pagamento VARCHAR(50) NOT NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL
);

-- Tabela de Itens da Venda
CREATE TABLE IF NOT EXISTS vendas_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venda_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

-- Tabela Fiscal Emulada (NFC-e)
CREATE TABLE IF NOT EXISTS cupons_fiscais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venda_id INT NOT NULL,
    chave_acesso VARCHAR(44) NOT NULL UNIQUE,
    data_emissao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE
);

-- ==========================================
-- DADOS DE TESTE (MOCK DATA) PARA O TCC
-- ==========================================

INSERT INTO usuarios (username, password, perfil) VALUES 
('admin', '1234', 'admin'),
('caixa', '1234', 'caixa');

INSERT INTO clientes (nome, email, telefone, status) VALUES 
('João Silva', 'joao@email.com', '(11) 98765-4321', 'ativo'),
('Maria Oliveira', 'maria@email.com', '(11) 91234-5678', 'ativo'),
('Carlos Souza', 'carlos@email.com', '(11) 99999-8888', 'ativo');

INSERT INTO fornecedores (nome, cnpj, telefone, email, status) VALUES 
('Tilibra S.A', '44.990.901/0001-43', '(14) 3235-4000', 'vendas@tilibra.com.br', 'ativo'),
('Bic Brasil', '04.148.243/0001-16', '(11) 2118-8000', 'comercial@bic.com', 'ativo'),
('Acrilex', '50.334.808/0001-38', '(11) 4344-8800', 'contato@acrilex.com', 'ativo');

INSERT INTO produtos (nome, categoria, quantidade, estoque_minimo, validade, preco_venda, preco_compra, fornecedor_id) VALUES 
('Caderno Espiral 10 Matérias', 'Papelaria', 120, 20, NULL, 25.90, 10.50, 1),
('Caneta Esferográfica Azul (Caixa)', 'Escritório', 5, 10, '2028-05-20', 45.00, 20.00, 2),
('Tinta Guache Kit 6 Cores', 'Artes', 18, 15, '2024-04-05', 12.50, 5.20, 3),
('Papel Sulfite A4 (Resma)', 'Papelaria', 80, 50, NULL, 29.90, 18.00, NULL);

INSERT INTO movimentacoes (produto_id, tipo, quantidade, observacao) VALUES
(1, 'entrada_compra', 120, 'Estoque inicial - Compra NFe 1001'),
(2, 'entrada_compra', 10, 'Compra NFe 1002'),
(3, 'entrada_compra', 20, 'Compra NFe 1003'),
(4, 'entrada_compra', 80, 'Compra NFe 1004'),
(2, 'saida_venda', 5, 'Vendas na loja'),
(3, 'saida_venda', 2, 'Venda online');

INSERT INTO vendas (cliente_id, total, forma_pagamento) VALUES 
(1, 135.00, 'PIX'),
(2, 25.00, 'Cartão de Crédito');

INSERT INTO vendas_itens (venda_id, produto_id, quantidade, preco_unitario) VALUES
(1, 2, 3, 45.00),
(2, 3, 2, 12.50);

INSERT INTO cupons_fiscais (venda_id, chave_acesso) VALUES
(1, '35260304148243000116650010000000011000000015'),
(2, '35260304148243000116650010000000021000000021');
