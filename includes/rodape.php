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

<script src="assets/js/main.js"></script>
</body>
</html>
