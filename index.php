<?php
require_once __DIR__ . '/includes/loja.php';

$destaques = listarProdutos($pdo, true, 8);
if (!$destaques) {
    // Sem destaques marcados no painel: mostra as novidades
    $destaques = listarProdutos($pdo, false, 8);
}

$paginaAtiva = 'inicio';
require __DIR__ . '/includes/cabecalho.php';
?>
<main>
    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <span class="sobretitulo">Nova coleção</span>
            <h1>Elegância que <em>veste</em> você</h1>
            <p>Peças selecionadas com sofisticação para todos os seus momentos. Escolha, toque em comprar e finalize pelo WhatsApp.</p>
            <a href="catalogo.php" class="btn-ouro-grande">Ver catálogo <i class="bi bi-arrow-right"></i></a>
        </div>
    </section>

    <!-- Destaques -->
    <section class="secao">
        <div class="container">
            <header class="secao-titulo">
                <span class="sobretitulo">Seleção especial</span>
                <h2>Destaques</h2>
            </header>

            <?php if ($destaques): ?>
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
                    <?php foreach ($destaques as $p) { require __DIR__ . '/includes/card_produto.php'; } ?>
                </div>
                <div class="text-center mt-5">
                    <a href="catalogo.php" class="btn btn-outline-ouro">Ver todos os produtos</a>
                </div>
            <?php else: ?>
                <p class="catalogo-vazio">Em breve, novidades por aqui.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Categorias -->
    <section class="secao secao-escura">
        <div class="container">
            <header class="secao-titulo">
                <span class="sobretitulo">Navegue por</span>
                <h2>Categorias</h2>
            </header>
            <div class="row row-cols-1 row-cols-md-3 g-3">
                <?php foreach ($categoriasMenu as $c): ?>
                    <div class="col">
                        <a href="catalogo.php?categoria=<?= e($c['slug']) ?>" class="categoria-card">
                            <span><?= e($c['nome']) ?></span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Como comprar -->
    <section class="secao">
        <div class="container">
            <header class="secao-titulo">
                <span class="sobretitulo">Simples e rápido</span>
                <h2>Como comprar</h2>
            </header>
            <div class="row row-cols-1 row-cols-md-3 g-4 text-center passos">
                <div class="col"><i class="bi bi-search"></i><h3>Escolha</h3><p>Navegue pelo catálogo e encontre sua peça.</p></div>
                <div class="col"><i class="bi bi-rulers"></i><h3>Selecione o tamanho</h3><p>Toque no tamanho desejado no produto.</p></div>
                <div class="col"><i class="bi bi-whatsapp"></i><h3>Finalize no WhatsApp</h3><p>Toque em "Comprar" e fale direto com a loja.</p></div>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/rodape.php'; ?>
