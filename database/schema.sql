-- =====================================================================
-- Is Modas - Estrutura do banco de dados
-- Importe pelo phpMyAdmin (aba "Importar") ou:
--   mysql -u root < database/schema.sql
-- Em produção (InfinityFree) o banco já vem criado: remova as duas
-- linhas CREATE DATABASE / USE e importe direto no banco do vPanel.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS is_modas
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE is_modas;

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
-- ---------------------------------------------------------------------
CREATE TABLE tamanhos (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome   VARCHAR(10) NOT NULL UNIQUE,
    ordem  TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Produtos
-- imagem guarda só o nome do arquivo (assets/uploads/produtos/<imagem>)
-- destaque = aparece na página inicial; ativo = visível no site
-- ---------------------------------------------------------------------
CREATE TABLE produtos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id  INT UNSIGNED NOT NULL,
    nome          VARCHAR(120) NOT NULL,
    descricao     TEXT NULL,
    preco         DECIMAL(10,2) NOT NULL,
    imagem        VARCHAR(255) NULL,
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
-- Relação N:N produto x tamanho
-- ---------------------------------------------------------------------
CREATE TABLE produto_tamanhos (
    produto_id  INT UNSIGNED NOT NULL,
    tamanho_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (produto_id, tamanho_id),
    CONSTRAINT fk_pt_produto FOREIGN KEY (produto_id)
        REFERENCES produtos(id) ON DELETE CASCADE,
    CONSTRAINT fk_pt_tamanho FOREIGN KEY (tamanho_id)
        REFERENCES tamanhos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- Dados iniciais
-- =====================================================================

-- Usuário: admin / Senha: admin123  (TROQUE no painel em "Alterar senha")
INSERT INTO usuarios (nome, usuario, senha_hash) VALUES
('Administrador', 'admin', '$2y$10$crY0nmFduDaFa5i4FVVIOOwUDlX7zKFCm6l0auKFRQAn6wo9MvYYC');

INSERT INTO categorias (nome, slug, ordem) VALUES
('Feminino',   'feminino',   1),
('Masculino',  'masculino',  2),
('Acessórios', 'acessorios', 3);

INSERT INTO tamanhos (nome, ordem) VALUES
('PP', 1), ('P', 2), ('M', 3), ('G', 4), ('GG', 5), ('XG', 6),
('34', 10), ('36', 11), ('38', 12), ('40', 13), ('42', 14), ('44', 15), ('46', 16),
('Único', 30);
