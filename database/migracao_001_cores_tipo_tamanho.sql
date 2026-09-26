-- =====================================================================
-- Migração 001: cores por produto + tipo de tamanho por produto
-- Rode UMA vez em bancos criados antes desta versão (o schema.sql já
-- inclui estas mudanças para instalações novas).
-- =====================================================================

-- Tipo de cada tamanho
ALTER TABLE tamanhos
    ADD COLUMN tipo ENUM('letra','numero','unico') NOT NULL DEFAULT 'letra' AFTER nome;
UPDATE tamanhos SET tipo = 'numero' WHERE nome REGEXP '^[0-9]+$';
UPDATE tamanhos SET tipo = 'unico'  WHERE nome = 'Único';
ALTER TABLE tamanhos ALTER COLUMN tipo DROP DEFAULT;

-- Tipo de tamanho escolhido por produto
ALTER TABLE produtos
    ADD COLUMN tipo_tamanho ENUM('letra','numero','unico') NOT NULL DEFAULT 'letra' AFTER imagem;

-- Deduz o tipo dos produtos existentes pelos tamanhos já marcados
UPDATE produtos p
JOIN (
    SELECT pt.produto_id,
           CASE
               WHEN SUM(t.tipo = 'numero') > 0 THEN 'numero'
               WHEN SUM(t.tipo = 'letra')  > 0 THEN 'letra'
               ELSE 'unico'
           END AS tipo
    FROM produto_tamanhos pt
    JOIN tamanhos t ON t.id = pt.tamanho_id
    GROUP BY pt.produto_id
) x ON x.produto_id = p.id
SET p.tipo_tamanho = x.tipo;

-- Remove tamanhos que não são do tipo do produto (caso houvesse mistura)
DELETE pt FROM produto_tamanhos pt
JOIN produtos p ON p.id = pt.produto_id
JOIN tamanhos t ON t.id = pt.tamanho_id
WHERE t.tipo <> p.tipo_tamanho;

-- Cores
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
