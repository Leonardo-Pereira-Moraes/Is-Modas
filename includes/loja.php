<?php
// Funções do site público.
require_once __DIR__ . '/../config/config.php';

function listarCategorias(PDO $pdo): array
{
    return $pdo->query('SELECT nome, slug FROM categorias ORDER BY ordem, nome')->fetchAll();
}

/**
 * Produtos visíveis no site, cada um com 'tamanhos' como array.
 * $limite > 0 limita a quantidade; $somenteDestaque filtra os destaques.
 */
function listarProdutos(PDO $pdo, bool $somenteDestaque = false, int $limite = 0): array
{
    $sql = "SELECT p.id, p.nome, p.descricao, p.preco, p.imagem,
                   c.nome AS categoria, c.slug AS categoria_slug,
                   GROUP_CONCAT(t.nome ORDER BY t.ordem SEPARATOR '|') AS tamanhos
            FROM produtos p
            JOIN categorias c ON c.id = p.categoria_id
            LEFT JOIN produto_tamanhos pt ON pt.produto_id = p.id
            LEFT JOIN tamanhos t ON t.id = pt.tamanho_id
            WHERE p.ativo = 1" . ($somenteDestaque ? ' AND p.destaque = 1' : '') . "
            GROUP BY p.id
            ORDER BY p.criado_em DESC" . ($limite > 0 ? ' LIMIT ' . $limite : '');

    $produtos = $pdo->query($sql)->fetchAll();
    foreach ($produtos as &$p) {
        $p['tamanhos'] = $p['tamanhos'] ? explode('|', $p['tamanhos']) : [];
    }
    return $produtos;
}

/** Link do WhatsApp com a mensagem padrão (sem tamanho escolhido). */
function linkWhatsApp(?array $produto = null): string
{
    if ($produto) {
        $msg = "Olá, " . LOJA_NOME . "! Tenho interesse neste produto:\n\n"
             . "*{$produto['nome']}*\n"
             . "Preço: " . formatarPreco($produto['preco']) . "\n\n"
             . "Ainda está disponível?";
    } else {
        $msg = "Olá, " . LOJA_NOME . "! Vim pelo site e gostaria de mais informações.";
    }
    return 'https://wa.me/' . WHATSAPP_NUMERO . '?text=' . rawurlencode($msg);
}
