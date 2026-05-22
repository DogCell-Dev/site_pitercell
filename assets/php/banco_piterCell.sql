-- ══════════════════════════════════════════════════════════════
--  PiterCell — Banco de Dados Normalizado (1FN → 4FN)
--  Gerado para integração cliente + admin
-- ══════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS pitercell CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pitercell;

-- ─────────────────────────────────────────────────────────────
-- 1. ADMINS
--    Separa credencial de admin da tabela de clientes (2FN/3FN)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(80)  NOT NULL,
    email      VARCHAR(120) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    criado_em  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Admin padrão — senha: admin123  (troque em produção)
INSERT INTO admins (nome, email, senha_hash)
VALUES ('Pitercell Admin', 'admin@pitercell.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ─────────────────────────────────────────────────────────────
-- 2. CLIENTES
--    Dados cadastrais (sem senha — 3FN: senha fica em usuarios)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS clientes (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nome      VARCHAR(120) NOT NULL,
    telefone  VARCHAR(20)  NOT NULL,
    email     VARCHAR(120),
    cpf       VARCHAR(14),
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ─────────────────────────────────────────────────────────────
-- 3. ENDEREÇOS DOS CLIENTES
--    Separado de clientes (4FN: um cliente pode ter N endereços)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cliente_enderecos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id  INT         NOT NULL,
    logradouro  VARCHAR(200) NOT NULL,
    numero      VARCHAR(20),
    complemento VARCHAR(100),
    bairro      VARCHAR(80)  NOT NULL,
    cidade      VARCHAR(80)  NOT NULL DEFAULT 'São Paulo',
    uf          CHAR(2)      NOT NULL DEFAULT 'SP',
    cep         VARCHAR(10),
    principal   TINYINT(1)   DEFAULT 0,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- 4. USUARIOS (login de clientes)
--    Separado de clientes (3FN: credencial ≠ dado pessoal)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id  INT          NOT NULL UNIQUE,
    email       VARCHAR(120) NOT NULL UNIQUE,
    senha_hash  VARCHAR(255) NOT NULL,
    ativo       TINYINT(1)   DEFAULT 1,
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- 5. ZONAS DE DELIVERY
--    Tabela de domínio independente (4FN: bairro → taxa é fato único)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS delivery_zonas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    bairro      VARCHAR(80)   NOT NULL UNIQUE,
    distancia_km DECIMAL(5,1) NOT NULL,
    taxa        DECIMAL(8,2)  GENERATED ALWAYS AS (distancia_km * 1.50) STORED,
    ativo       TINYINT(1)    DEFAULT 1
);

INSERT INTO delivery_zonas (bairro, distancia_km) VALUES
('Centro',         2.0),
('Jardins',        4.5),
('Santana',        6.0),
('Tatuapé',        7.5),
('Mooca',          5.0),
('Pinheiros',      8.0),
('Vila Madalena',  9.0),
('Lapa',           10.0),
('Santo André',    15.0),
('São Bernardo',   18.0),
('Guarulhos',      20.0);

-- ─────────────────────────────────────────────────────────────
-- 6. DISPOSITIVOS (tabela de domínio — 2FN)
--    Evita repetição de strings nas ordens
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS dispositivos_tipos (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(60) NOT NULL UNIQUE
);

INSERT INTO dispositivos_tipos (nome) VALUES
('iPhone'),('Samsung'),('Motorola'),('Xiaomi'),('LG'),
('Huawei'),('Sony'),('Nokia'),('Outro');

-- ─────────────────────────────────────────────────────────────
-- 7. ORDENS DE SERVIÇO
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ordens (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id       INT          NOT NULL,
    numero           VARCHAR(20)  NOT NULL UNIQUE,
    dispositivo_id   INT,                          -- FK para dispositivos_tipos
    modelo           VARCHAR(120),
    sistema          ENUM('android','ios','outro') DEFAULT 'android',
    problema         TEXT         NOT NULL,
    servico          VARCHAR(120),
    valor_orcamento  DECIMAL(10,2),
    valor_final      DECIMAL(10,2),
    status           ENUM('aguardando','em_analise','em_reparo','pronto','entregue','cancelado') DEFAULT 'aguardando',
    observacoes      TEXT,
    origem           ENUM('admin','cliente') DEFAULT 'admin',  -- quem criou a OS
    criado_em        DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id)     REFERENCES clientes(id)           ON DELETE CASCADE,
    FOREIGN KEY (dispositivo_id) REFERENCES dispositivos_tipos(id) ON DELETE SET NULL
);

-- ─────────────────────────────────────────────────────────────
-- 8. DELIVERY PEDIDOS
--    Separado de ordens (4FN: nem toda ordem tem delivery)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS delivery_pedidos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    ordem_id        INT         NOT NULL UNIQUE,
    tipo            ENUM('retirada','entrega') NOT NULL,
    endereco_id     INT,                            -- FK endereço do cliente
    zona_id         INT,                            -- FK zona/taxa
    taxa_cobrada    DECIMAL(8,2),                   -- valor snapshot no momento
    data_agendada   DATE,
    hora_agendada   TIME,
    status          ENUM('aguardando','confirmado','a_caminho','concluido','cancelado') DEFAULT 'aguardando',
    obs_cliente     TEXT,
    obs_admin       TEXT,
    criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ordem_id)    REFERENCES ordens(id)             ON DELETE CASCADE,
    FOREIGN KEY (endereco_id) REFERENCES cliente_enderecos(id)  ON DELETE SET NULL,
    FOREIGN KEY (zona_id)     REFERENCES delivery_zonas(id)     ON DELETE SET NULL
);

-- ─────────────────────────────────────────────────────────────
-- 9. MENSAGENS (chat cliente ↔ admin por OS)
--    Separado de ordens (4FN: 0-N mensagens por OS)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS mensagens (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    ordem_id   INT      NOT NULL,
    remetente  ENUM('cliente','admin') NOT NULL,
    texto      TEXT     NOT NULL,
    lida       TINYINT(1) DEFAULT 0,
    criado_em  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ordem_id) REFERENCES ordens(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- 10. HISTORICO DE STATUS
--     Separado de ordens (4FN: 0-N eventos por OS)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS historico (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ordem_id    INT NOT NULL,
    status_ant  VARCHAR(30),
    status_novo VARCHAR(30),
    nota        TEXT,
    feito_por   ENUM('admin','cliente','sistema') DEFAULT 'admin',
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ordem_id) REFERENCES ordens(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- 11. PAGAMENTOS
--     Separado de ordens (3FN: dados financeiros independentes)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pagamentos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    ordem_id     INT           NOT NULL,
    valor        DECIMAL(10,2) NOT NULL,
    forma        ENUM('dinheiro','pix','cartao_debito','cartao_credito','outro') NOT NULL,
    status       ENUM('pendente','pago','estornado') DEFAULT 'pendente',
    obs          VARCHAR(200),
    pago_em      DATETIME,
    criado_em    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ordem_id) REFERENCES ordens(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- DADOS DE EXEMPLO (compatíveis com a nova estrutura)
-- ─────────────────────────────────────────────────────────────
INSERT INTO clientes (nome, telefone, email) VALUES
('João Silva',      '11999990001', 'joao@email.com'),
('Maria Santos',    '11999990002', 'maria@email.com'),
('Carlos Oliveira', '11999990003', 'carlos@email.com');

-- Senha padrão para todos: "123456"
INSERT INTO usuarios (cliente_id, email, senha_hash) VALUES
(1, 'joao@email.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(2, 'maria@email.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(3, 'carlos@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO cliente_enderecos (cliente_id, logradouro, numero, bairro, cidade, uf, cep, principal) VALUES
(1, 'Rua das Flores', '123', 'Centro',  'São Paulo', 'SP', '01310-000', 1),
(2, 'Av. Paulista',   '456', 'Jardins', 'São Paulo', 'SP', '01310-100', 1);

INSERT INTO ordens (cliente_id, numero, dispositivo_id, modelo, sistema, problema, servico, valor_orcamento, status, origem) VALUES
(1, 'OS-0001', 1, 'iPhone 13',     'ios',     'Tela quebrada',  'Troca de Tela',    299.00, 'em_reparo', 'admin'),
(2, 'OS-0002', 2, 'Galaxy A54',    'android', 'Bateria viciada','Troca de Bateria', 149.00, 'aguardando','admin'),
(3, 'OS-0003', 3, 'Moto G84',      'android', 'Não carrega',    'Conector de Carga', 99.00, 'pronto',    'admin');
