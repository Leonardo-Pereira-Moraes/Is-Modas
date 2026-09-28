-- =====================================================================
-- Migração 003: categoria "Acessórios" vira "Sapatos Masculinos"
-- + numeração ímpar (37, 39, 41, 43) para calçados
-- Rode UMA vez, depois da migração 002, em bancos criados antes desta
-- versão (o schema.sql já inclui estas mudanças para instalações novas).
--
-- Os produtos apontam para a categoria pelo id, então continuam nela.
-- Links antigos com ?categoria=acessorios abrem o catálogo em "Todos".
-- =====================================================================

UPDATE categorias
SET nome = 'Sapatos Masculinos', slug = 'sapatos-masculinos'
WHERE slug = 'acessorios';

-- Numeração: ordem 10 + (número - 34), para os ímpares caberem entre os pares
UPDATE tamanhos SET ordem = 10 + (CAST(nome AS UNSIGNED) - 34) WHERE tipo = 'numero';

INSERT INTO tamanhos (nome, tipo, ordem) VALUES
('37', 'numero', 13), ('39', 'numero', 15), ('41', 'numero', 17), ('43', 'numero', 19);
