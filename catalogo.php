<?php
require_once __DIR__ . '/includes/loja.php';

$produtos = listarProdutos($pdo);

$paginaAtiva = 'catalogo';
$tituloPagina = 'Catálogo';
require __DIR__ . '/includes/cabecalho.php';
?>
<main class="secao">
    <div class="container">
        <header class="secao-titulo">
            <span class="sobretitulo">Nossa coleção</span>
            <h1>Catálogo</h1>
        </header>

        <!-- Filtro por categoria (assets/js/main.js, sem recarregar a página) -->
        <div class="filtros" role="group" aria-label="Filtrar por categoria">
            <button type="button" class="filtro ativo" data-filtro="todos" aria-pressed="true">Todos</button>
            <?php foreach ($categoriasMenu as $c): ?>
                <button type="button" class="filtro" data-filtro="<?= e($c['slug']) ?>" aria-pressed="false"><?= e($c['nome']) ?></button>
            <?php endforeach; ?>
        </div>
        <p class="contador-produtos" id="contadorProdutos" aria-live="polite"></p>

        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4" id="gradeProdutos">
            <?php foreach ($produtos as $p) { require __DIR__ . '/includes/card_produto.php'; } ?>
        </div>

        <p class="catalogo-vazio" id="catalogoVazio" <?= $produtos ? 'hidden' : '' ?>>
            Nenhum produto nesta categoria no momento.
        </p>
    </div>
</main>
<?php require __DIR__ . '/includes/rodape.php'; ?>
