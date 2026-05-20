-- ── Pitercell — Banco de Dados ──
CREATE DATABASE IF NOT EXISTS pitercell CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pitercell;

CREATE TABLE IF NOT EXISTS clientes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(120) NOT NULL,
    telefone    VARCHAR(20)  NOT NULL,
    email       VARCHAR(120),
    cpf         VARCHAR(20),
    endereco    TEXT,
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ordens (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id      INT NOT NULL,
    numero          VARCHAR(20) NOT NULL UNIQUE,
    dispositivo     VARCHAR(120) NOT NULL,
    modelo          VARCHAR(120),
    sistema         ENUM('android','ios','outro') DEFAULT 'android',
    problema        TEXT NOT NULL,
    servico         VARCHAR(120),
    valor_orcamento DECIMAL(10,2),
    valor_final     DECIMAL(10,2),
    status          ENUM('aguardando','em_analise','em_reparo','pronto','entregue','cancelado') DEFAULT 'aguardando',
    observacoes     TEXT,
    criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS historico (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ordem_id    INT NOT NULL,
    status_ant  VARCHAR(30),
    status_novo VARCHAR(30),
    nota        TEXT,
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ordem_id) REFERENCES ordens(id) ON DELETE CASCADE
);

-- Dados de exemplo
INSERT INTO clientes (nome, telefone, email) VALUES
('João Silva', '11999990001', 'joao@email.com'),
('Maria Santos', '11999990002', 'maria@email.com'),
('Carlos Oliveira', '11999990003', '');

INSERT INTO ordens (cliente_id, numero, dispositivo, modelo, sistema, problema, servico, valor_orcamento, status) VALUES
(1, 'OS-0001', 'iPhone', 'iPhone 13', 'ios', 'Tela quebrada', 'Troca de Tela', 299.00, 'em_reparo'),
(2, 'OS-0002', 'Samsung', 'Galaxy A54', 'android', 'Bateria viciada', 'Troca de Bateria', 149.00, 'aguardando'),
(3, 'OS-0003', 'Motorola', 'Moto G84', 'android', 'Não carrega', 'Conector de Carga', 99.00, 'pronto');
