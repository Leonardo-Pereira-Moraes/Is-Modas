-- =====================================================================
-- Migração 002: tamanhos por cor (tabela variacoes)
-- Rode UMA vez, depois da migração 001, em bancos criados antes desta
-- versão (o schema.sql já inclui estas mudanças para instalações novas).
--
-- Até aqui os tamanhos valiam para o produto inteiro, então cada cor
-- recebe todos os tamanhos que o produto tinha. Depois é só ajustar
-- por cor no painel.
-- =====================================================================

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

-- Produtos com tamanhos mas sem nenhuma cor ganham a cor "Única",
-- para não perderem os tamanhos (renomeie no painel se quiser)
INSERT INTO produto_cores (produto_id, nome, ordem)
SELECT DISTINCT pt.produto_id, 'Única', 0
FROM produto_tamanhos pt
WHERE NOT EXISTS (SELECT 1 FROM produto_cores pc WHERE pc.produto_id = pt.produto_id);

-- Cada cor recebe os tamanhos que o produto tinha
INSERT INTO variacoes (produto_id, cor_id, tamanho_id)
SELECT pc.produto_id, pc.id, pt.tamanho_id
FROM produto_cores pc
JOIN produto_tamanhos pt ON pt.produto_id = pc.produto_id;

DROP TABLE produto_tamanhos;
