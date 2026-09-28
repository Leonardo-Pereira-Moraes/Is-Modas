<footer class="site-footer">
    <div class="container">
        <p class="rodape-marca"><?= e(LOJA_NOME) ?></p>
        <p class="rodape-texto">Elegância para todos os momentos.</p>
        <a href="<?= e(linkWhatsApp()) ?>" class="btn btn-outline-ouro btn-sm" target="_blank" rel="noopener">
            <i class="bi bi-whatsapp"></i> Fale conosco
        </a>
        <p class="rodape-copy">&copy; <?= date('Y') ?> <?= e(LOJA_NOME) ?>. Todos os direitos reservados.</p>
    </div>
</footer>

<a href="<?= e(linkWhatsApp()) ?>" class="whatsapp-flutuante" target="_blank" rel="noopener" aria-label="Conversar no WhatsApp">
    <i class="bi bi-whatsapp"></i>
</a>

<!-- Escolha do atendente do WhatsApp (aberto pelo main.js) -->
<div class="whats-modal" id="whatsModal" hidden>
    <div class="whats-modal-fundo" data-fechar></div>
    <div class="whats-modal-caixa" role="dialog" aria-modal="true" aria-labelledby="whatsModalTitulo">
        <button type="button" class="whats-modal-fechar" data-fechar aria-label="Fechar"><i class="bi bi-x-lg"></i></button>
        <h2 id="whatsModalTitulo">Com quem você quer falar?</h2>
        <?php foreach (WHATSAPP_CONTATOS as $rotulo => $numero): ?>
            <a href="https://wa.me/<?= e($numero) ?>" class="btn-comprar whats-modal-opcao" data-numero="<?= e($numero) ?>"
               target="_blank" rel="noopener">
                <i class="bi bi-whatsapp"></i> Falar com <?= e($rotulo) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
