<?php
// Card de um produto. Espera $p (item de listarProdutos()).
// data-* são lidos pelo main.js para o filtro e para montar a mensagem do WhatsApp.
$unicoTamanho = count($p['tamanhos']) === 1;
?>
<div class="col produto-item" data-categoria="<?= e($p['categoria_slug']) ?>">
    <article class="produto-card" data-nome="<?= e($p['nome']) ?>" data-preco="<?= e(formatarPreco($p['preco'])) ?>">
        <div class="produto-foto">
            <?php if ($p['imagem']): ?>
                <img src="<?= UPLOAD_URL . e($p['imagem']) ?>" alt="<?= e($p['nome']) ?>" loading="lazy">
            <?php else: ?>
                <div class="produto-sem-foto"><i class="bi bi-image"></i></div>
            <?php endif; ?>
            <span class="produto-categoria"><?= e($p['categoria']) ?></span>
        </div>
        <div class="produto-info">
            <h3 class="produto-nome"><?= e($p['nome']) ?></h3>
            <?php if ($p['descricao']): ?>
                <p class="produto-descricao"><?= e($p['descricao']) ?></p>
            <?php endif; ?>
            <p class="produto-preco"><?= formatarPreco($p['preco']) ?></p>

            <?php if ($p['tamanhos']): ?>
                <div class="produto-tamanhos" role="group" aria-label="Escolha o tamanho">
                    <?php foreach ($p['tamanhos'] as $tam): ?>
                        <button type="button" class="tamanho<?= $unicoTamanho ? ' selecionado' : '' ?>"
                                aria-pressed="<?= $unicoTamanho ? 'true' : 'false' ?>"><?= e($tam) ?></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <a href="<?= e(linkWhatsApp($p)) ?>" class="btn-comprar" target="_blank" rel="noopener">
                <i class="bi bi-whatsapp"></i> Comprar
            </a>
        </div>
    </article>
</div>
