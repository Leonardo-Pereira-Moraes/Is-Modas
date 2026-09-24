// Painel admin Is Modas

// Confirmação antes de excluir produto
document.querySelectorAll('.form-excluir').forEach((form) => {
    form.addEventListener('submit', (ev) => {
        const nome = form.dataset.nome || 'este produto';
        if (!confirm(`Excluir "${nome}"? Essa ação não pode ser desfeita.`)) {
            ev.preventDefault();
        }
    });
});

// Pré-visualização da foto escolhida no formulário de produto
const inputImagem = document.getElementById('imagem');
if (inputImagem) {
    const preview = document.getElementById('previewImagem');
    const dica = document.getElementById('uploadDica');
    const LIMITE = 5 * 1024 * 1024;

    inputImagem.addEventListener('change', () => {
        const arquivo = inputImagem.files[0];
        if (!arquivo) return;

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(arquivo.type)) {
            alert('Formato inválido. Envie JPG, PNG ou WEBP.');
            inputImagem.value = '';
            return;
        }
        if (arquivo.size > LIMITE) {
            alert('A imagem é grande demais (máximo 5 MB).');
            inputImagem.value = '';
            return;
        }

        preview.src = URL.createObjectURL(arquivo);
        preview.classList.remove('d-none');
        dica.classList.add('d-none');
    });
}
