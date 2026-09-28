<?php
// Funções do site público.
require_once __DIR__ . '/../config/config.php';

function listarCategorias(PDO $pdo): array
{
    return $pdo->query('SELECT nome, slug FROM categorias ORDER BY ordem, nome')->fetchAll();
}

/**
 * Produtos visíveis no site. Cada um vem com:
 *  - 'cores':    array de ['nome', 'imagem', 'tamanhos' => nomes dos tamanhos daquela cor]
 *  - 'tamanhos': união dos tamanhos de todas as cores, na ordem da tabela tamanhos
 * $limite > 0 limita a quantidade; $somenteDestaque filtra os destaques.
 */
function listarProdutos(PDO $pdo, bool $somenteDestaque = false, int $limite = 0): array
{
    $sql = "SELECT p.id, p.nome, p.descricao, p.preco, p.imagem,
                   c.nome AS categoria, c.slug AS categoria_slug
            FROM produtos p
            JOIN categorias c ON c.id = p.categoria_id
            WHERE p.ativo = 1" . ($somenteDestaque ? ' AND p.destaque = 1' : '') . "
            ORDER BY p.criado_em DESC" . ($limite > 0 ? ' LIMIT ' . $limite : '');

    $produtos = $pdo->query($sql)->fetchAll();
    if (!$produtos) {
        return [];
    }
    $ids = array_column($produtos, 'id');
    $marcadores = implode(',', array_fill(0, count($ids), '?'));

    // Cores de todos os produtos listados numa consulta só
    $stmt = $pdo->prepare("SELECT id, produto_id, nome, imagem FROM produto_cores
                           WHERE produto_id IN ($marcadores) ORDER BY ordem, id");
    $stmt->execute($ids);
    $cores = [];
    foreach ($stmt->fetchAll() as $cor) {
        $cores[$cor['id']] = ['produto_id' => $cor['produto_id'], 'nome' => $cor['nome'],
                              'imagem' => $cor['imagem'], 'tamanhos' => []];
    }

    // Variações (cor x tamanho), já na ordem de exibição dos tamanhos
    $stmt = $pdo->prepare("SELECT v.produto_id, v.cor_id, t.nome
                           FROM variacoes v JOIN tamanhos t ON t.id = v.tamanho_id
                           WHERE v.produto_id IN ($marcadores) ORDER BY t.ordem");
    $stmt->execute($ids);
    $tamanhosPorProduto = [];
    foreach ($stmt->fetchAll() as $v) {
        if (isset($cores[$v['cor_id']])) {
            $cores[$v['cor_id']]['tamanhos'][] = $v['nome'];
        }
        $lista = &$tamanhosPorProduto[$v['produto_id']];
        if (!in_array($v['nome'], $lista ?? [], true)) {
            $lista[] = $v['nome'];
        }
        unset($lista);
    }

    $coresPorProduto = [];
    foreach ($cores as $cor) {
        $coresPorProduto[$cor['produto_id']][] = $cor;
    }

    foreach ($produtos as &$p) {
        $p['cores'] = $coresPorProduto[$p['id']] ?? [];
        $p['tamanhos'] = $tamanhosPorProduto[$p['id']] ?? [];
    }
    unset($p);
    return $produtos;
}

/**
 * Link do WhatsApp com mensagem pré-preenchida.
 * Sem produto: mensagem geral. Com produto: nome, preço e, se informados,
 * tamanho e cor escolhidos (o main.js refaz o link quando o cliente escolhe).
 */
function linkWhatsApp(?array $produto = null, ?string $tamanho = null, ?string $cor = null): string
{
    if ($produto) {
        $msg = "Olá, " . LOJA_NOME . "! Tenho interesse neste produto:\n\n"
             . "*{$produto['nome']}*\n"
             . "Preço: " . formatarPreco($produto['preco']) . "\n"
             . ($tamanho ? "Tamanho: $tamanho\n" : '')
             . ($cor ? "Cor: $cor\n" : '')
             . "\nAinda está disponível?";
    } else {
        $msg = "Olá, " . LOJA_NOME . "! Vim pelo site e gostaria de mais informações.";
    }
    // O main.js intercepta o clique e deixa o cliente escolher o número (WHATSAPP_CONTATOS)
    return 'https://wa.me/' . reset(WHATSAPP_CONTATOS) . '?text=' . rawurlencode($msg);
}
