-- =====================================================================
-- Is Modas - Estrutura do banco de dados
-- Importe pelo phpMyAdmin (aba "Importar") ou:
--   mysql -u root < database/schema.sql
-- Em produção (InfinityFree) o banco já vem criado: remova as duas
-- linhas CREATE DATABASE / USE e importe direto no banco do vPanel.
-- =====================================================================

-- ---------------------------------------------------------------------
-- Usuários do painel admin
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(100) NOT NULL,
    usuario       VARCHAR(50)  NOT NULL UNIQUE,
    senha_hash    VARCHAR(255) NOT NULL,
    ultimo_login  DATETIME NULL,
    criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Categorias (feminino, masculino, acessórios...)
-- slug é usado pelo filtro JavaScript do catálogo (data-categoria)
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome   VARCHAR(50) NOT NULL,
    slug   VARCHAR(50) NOT NULL UNIQUE,
    ordem  TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tamanhos disponíveis (P, M, G, 38, 40, Único...)
-- tipo agrupa os tamanhos: letra (PP a XG), numero (34 a 46) ou unico
-- ---------------------------------------------------------------------
CREATE TABLE tamanhos (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome   VARCHAR(10) NOT NULL UNIQUE,
    tipo   ENUM('letra','numero','unico') NOT NULL,
    ordem  TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Produtos
-- imagem guarda só o nome do arquivo (assets/uploads/produtos/<imagem>)
-- destaque = aparece na página inicial; ativo = visível no site
-- tipo_tamanho = qual grupo de tamanhos o produto usa (escolhido por
-- produto, pois a mesma categoria mistura letra e numeração)
-- ---------------------------------------------------------------------
CREATE TABLE produtos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id  INT UNSIGNED NOT NULL,
    nome          VARCHAR(120) NOT NULL,
    descricao     TEXT NULL,
    preco         DECIMAL(10,2) NOT NULL,
    imagem        VARCHAR(255) NULL,
    tipo_tamanho  ENUM('letra','numero','unico') NOT NULL DEFAULT 'letra',
    destaque      TINYINT(1) NOT NULL DEFAULT 0,
    ativo         TINYINT(1) NOT NULL DEFAULT 1,
    criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_produto_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_produtos_vitrine (ativo, destaque)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Cores de cada produto (lista livre, editável no painel)
-- imagem NULL = usa a foto principal do produto
-- ---------------------------------------------------------------------
CREATE TABLE produto_cores (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    produto_id  INT UNSIGNED NOT NULL,
    nome        VARCHAR(40) NOT NULL,
    imagem      VARCHAR(255) NULL,
    ordem       TINYINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_cor_produto FOREIGN KEY (produto_id)
        REFERENCES produtos(id) ON DELETE CASCADE,
    INDEX idx_cores_produto (produto_id, ordem)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Variações: quais tamanhos existem para cada cor
-- (ex.: calça bege só em M e G, calça preta só em PP e P).
-- Os tamanhos do produto são a união dos tamanhos de suas cores.
-- produto_id repete o dono da cor para facilitar as consultas por produto.
-- ---------------------------------------------------------------------
CREATE TABLE variacoes (
    produto_id  INT UNSIGNED NOT NULL,
    cor_id      INT UNSIGNED NOT NULL,
    tamanho_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (cor_id, tamanho_id),
    CONSTRAINT fk_var_produto FOREIGN KEY (produto_id)
        REFERENCES produtos(id) ON DELETE CASCADE,
    CONSTRAINT fk_var_cor FOREIGN KEY (cor_id)
        REFERENCES produto_cores(id) ON DELETE CASCADE,
    CONSTRAINT fk_var_tamanho FOREIGN KEY (tamanho_id)
        REFERENCES tamanhos(id) ON DELETE CASCADE,
    INDEX idx_var_produto (produto_id)
) ENGINE=InnoDB;

-- =====================================================================
-- Dados iniciais
-- =====================================================================

-- Usuário: admin / Senha: admin123  (TROQUE no painel em "Alterar senha")
INSERT INTO usuarios (nome, usuario, senha_hash) VALUES
('Administrador', 'admin', '$2y$10$crY0nmFduDaFa5i4FVVIOOwUDlX7zKFCm6l0auKFRQAn6wo9MvYYC');

INSERT INTO categorias (nome, slug, ordem) VALUES
('Feminino',           'feminino',           1),
('Masculino',          'masculino',          2),
('Sapatos Masculinos', 'sapatos-masculinos', 3);

INSERT INTO tamanhos (nome, tipo, ordem) VALUES
('PP', 'letra', 1), ('P', 'letra', 2), ('M', 'letra', 3), ('G', 'letra', 4), ('GG', 'letra', 5), ('XG', 'letra', 6),
('34', 'numero', 10), ('36', 'numero', 12), ('37', 'numero', 13), ('38', 'numero', 14),
('39', 'numero', 15), ('40', 'numero', 16), ('41', 'numero', 17), ('42', 'numero', 18),
('43', 'numero', 19), ('44', 'numero', 20), ('46', 'numero', 22),
('Único', 'unico', 30);
