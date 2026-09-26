<?php
// Card de um produto. Espera $p (item de listarProdutos()).
// data-* são lidos pelo main.js para o filtro, a troca de foto por cor,
// os tamanhos de cada cor (data-tamanhos) e a mensagem do WhatsApp.
// Com uma única opção (de cor ou de tamanho), ela já vem selecionada.
/** @var array $p */
$corUnica = count($p['cores']) === 1 ? $p['cores'][0]['nome'] : null;
// Tamanhos que aparecem de início: os da cor já selecionada, ou todos
$tamanhosVisiveis = $corUnica ? $p['cores'][0]['tamanhos'] : $p['tamanhos'];
$tamanhoUnico = count($tamanhosVisiveis) === 1 ? $tamanhosVisiveis[0] : null;
$fotoPrincipal = $p['imagem'] ? UPLOAD_URL . $p['imagem'] : '';
$fotoInicial   = count($p['cores']) === 1 && $p['cores'][0]['imagem'] ? UPLOAD_URL . $p['cores'][0]['imagem'] : $fotoPrincipal;
?>
<div class="col produto-item" data-categoria="<?= e($p['categoria_slug']) ?>">
    <article class="produto-card" data-nome="<?= e($p['nome']) ?>" data-preco="<?= e(formatarPreco($p['preco'])) ?>">
        <div class="produto-foto">
            <?php if ($fotoInicial): ?>
                <img src="<?= e($fotoInicial) ?>" alt="<?= e($p['nome']) ?>" loading="lazy"
                     data-principal="<?= e($fotoPrincipal) ?>">
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

            <?php if ($p['cores']): ?>
                <span class="opcoes-rotulo">Cor</span>
                <div class="produto-opcoes produto-cores" role="group" aria-label="Escolha a cor">
                    <?php foreach ($p['cores'] as $cor): ?>
                        <button type="button" class="opcao cor<?= $corUnica ? ' selecionado' : '' ?>"
                                aria-pressed="<?= $corUnica ? 'true' : 'false' ?>"
                                data-imagem="<?= $cor['imagem'] ? e(UPLOAD_URL . $cor['imagem']) : '' ?>"
                                data-tamanhos="<?= e(json_encode($cor['tamanhos'], JSON_UNESCAPED_UNICODE)) ?>"><?= e($cor['nome']) ?></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($p['tamanhos']): ?>
                <span class="opcoes-rotulo">Tamanho</span>
                <div class="produto-opcoes produto-tamanhos" role="group" aria-label="Escolha o tamanho">
                    <?php foreach ($p['tamanhos'] as $tam):
                        $visivel = in_array($tam, $tamanhosVisiveis, true);
                        $selecionado = $visivel && $tam === $tamanhoUnico; ?>
                        <button type="button" class="opcao tamanho<?= $selecionado ? ' selecionado' : '' ?>"
                                data-tamanho="<?= e($tam) ?>" aria-pressed="<?= $selecionado ? 'true' : 'false' ?>"
                                <?= $visivel ? '' : 'hidden' ?>><?= e($tam) ?></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <a href="<?= e(linkWhatsApp($p, $tamanhoUnico, $corUnica)) ?>" class="btn-comprar" target="_blank" rel="noopener">
                <i class="bi bi-whatsapp"></i> Comprar
            </a>
        </div>
    </article>
</div>
