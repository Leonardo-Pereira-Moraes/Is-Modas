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

/** Confere tipo e tamanho da imagem escolhida; limpa o campo se for inválida. */
function imagemValida(input) {
    const arquivo = input.files[0];
    if (!arquivo) return null;

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(arquivo.type)) {
        alert('Formato inválido. Envie JPG, PNG ou WEBP.');
        input.value = '';
        return null;
    }
    if (arquivo.size > 5 * 1024 * 1024) {
        alert('A imagem é grande demais (máximo 5 MB).');
        input.value = '';
        return null;
    }
    return arquivo;
}

// Pré-visualização da foto principal no formulário de produto
const inputImagem = document.getElementById('imagem');
if (inputImagem) {
    const preview = document.getElementById('previewImagem');
    const dica = document.getElementById('uploadDica');

    inputImagem.addEventListener('change', () => {
        const arquivo = imagemValida(inputImagem);
        if (!arquivo) return;
        preview.src = URL.createObjectURL(arquivo);
        preview.classList.remove('d-none');
        dica.classList.add('d-none');
    });
}

// Tipo de tamanho: em cada cor, mostra só os tamanhos do tipo escolhido e desmarca os outros
const seletorTipo = document.getElementById('tipo_tamanho');
const listaCores = document.getElementById('listaCores');

function aplicarTipo(escopo) {
    if (!seletorTipo) return;
    const tipo = seletorTipo.value;
    escopo.querySelectorAll('.tamanho-opcao').forEach((opcao) => {
        const visivel = opcao.dataset.tipo === tipo;
        const caixa = opcao.querySelector('input');
        opcao.hidden = !visivel;
        if (!visivel) caixa.checked = false;
        if (visivel && tipo === 'unico') caixa.checked = true; // "Único" é sempre o único tamanho
    });
}

if (seletorTipo && listaCores) {
    seletorTipo.addEventListener('change', () => aplicarTipo(listaCores));
    aplicarTipo(listaCores);
}

// Cores: adicionar/remover linhas e pré-visualizar a foto de cada cor
if (listaCores) {
    const modelo = document.getElementById('modeloCor');
    // Próxima chave "n<numero>" livre (evita repetir chaves que já estão no formulário)
    let proxima = Math.max(0, ...Array.from(listaCores.querySelectorAll('.cor-linha'), (l) =>
        l.dataset.chave.startsWith('n') ? Number(l.dataset.chave.slice(1)) + 1 : 0));

    document.getElementById('adicionarCor').addEventListener('click', () => {
        const html = modelo.innerHTML.replaceAll('__CHAVE__', 'n' + proxima++);
        listaCores.insertAdjacentHTML('beforeend', html);
        const linha = listaCores.lastElementChild;
        aplicarTipo(linha); // o modelo foi gerado com o tipo do carregamento da página
        linha.querySelector('input[type="text"]').focus();
    });

    listaCores.addEventListener('click', (ev) => {
        const remover = ev.target.closest('.cor-remover');
        if (remover) remover.closest('.cor-linha').remove();
    });

    listaCores.addEventListener('change', (ev) => {
        if (!ev.target.classList.contains('cor-arquivo')) return;
        const arquivo = imagemValida(ev.target);
        if (!arquivo) return;
        const foto = ev.target.closest('.cor-foto');
        const img = foto.querySelector('img');
        img.src = URL.createObjectURL(arquivo);
        img.classList.remove('d-none');
        foto.querySelector('i').classList.add('d-none');
        // Foto nova substitui a antiga: desmarca "usar a foto principal"
        const usarPrincipal = ev.target.closest('.cor-linha').querySelector('input[name$="[remover_foto]"]');
        if (usarPrincipal) usarPrincipal.checked = false;
    });
}
