// Site público Is Modas — JavaScript puro

const LOJA_NOME = document.body.dataset.loja || 'Is Modas';

/* ------------------------------------------------------------------
   Escolha de tamanho + link do WhatsApp com mensagem pré-preenchida
   ------------------------------------------------------------------ */
function montarMensagem(card, tamanho) {
    const linhas = [
        `Olá, ${LOJA_NOME}! Tenho interesse neste produto:`,
        '',
        `*${card.dataset.nome}*`,
        `Preço: ${card.dataset.preco}`,
    ];
    if (tamanho) linhas.push(`Tamanho: ${tamanho}`);
    linhas.push('', 'Ainda está disponível?');
    return linhas.join('\n');
}

function atualizarLinkComprar(card) {
    const botao = card.querySelector('.btn-comprar');
    const selecionado = card.querySelector('.tamanho.selecionado');
    const url = new URL(botao.href);
    url.searchParams.set('text', montarMensagem(card, selecionado?.textContent.trim()));
    botao.href = url.toString();
}

document.querySelectorAll('.produto-card').forEach((card) => {
    const botoes = card.querySelectorAll('.tamanho');

    botoes.forEach((botao) => {
        botao.addEventListener('click', () => {
            const jaSelecionado = botao.classList.contains('selecionado');
            botoes.forEach((b) => {
                b.classList.remove('selecionado');
                b.setAttribute('aria-pressed', 'false');
            });
            if (!jaSelecionado) {
                botao.classList.add('selecionado');
                botao.setAttribute('aria-pressed', 'true');
            }
            atualizarLinkComprar(card);
        });
    });

    if (card.querySelector('.tamanho.selecionado')) atualizarLinkComprar(card);
});

/* ------------------------------------------------------------------
   Filtro por categoria (somente na página do catálogo)
   ------------------------------------------------------------------ */
const grade = document.getElementById('gradeProdutos');

if (grade) {
    const itens = grade.querySelectorAll('.produto-item');
    const botoesFiltro = document.querySelectorAll('.filtro');
    const linksMenu = document.querySelectorAll('.menu a[data-filtro]');
    const contador = document.getElementById('contadorProdutos');
    const vazio = document.getElementById('catalogoVazio');
    const slugsValidos = ['todos', ...Array.from(botoesFiltro, (b) => b.dataset.filtro)];

    function aplicarFiltro(categoria) {
        if (!slugsValidos.includes(categoria)) categoria = 'todos';

        let visiveis = 0;
        itens.forEach((item) => {
            const mostrar = categoria === 'todos' || item.dataset.categoria === categoria;
            item.hidden = !mostrar;
            if (mostrar) {
                visiveis++;
                // reinicia a animação de entrada
                item.classList.remove('entrando');
                void item.offsetWidth;
                item.classList.add('entrando');
            }
        });

        botoesFiltro.forEach((b) => {
            const ativo = b.dataset.filtro === categoria;
            b.classList.toggle('ativo', ativo);
            b.setAttribute('aria-pressed', String(ativo));
        });
        linksMenu.forEach((a) => a.classList.toggle('ativo', a.dataset.filtro === categoria));

        contador.textContent = visiveis === 1 ? '1 produto' : `${visiveis} produtos`;
        vazio.hidden = visiveis > 0;

        // Mantém a URL compartilhável (?categoria=...) sem recarregar
        const url = new URL(window.location);
        if (categoria === 'todos') url.searchParams.delete('categoria');
        else url.searchParams.set('categoria', categoria);
        history.replaceState(null, '', url);
    }

    botoesFiltro.forEach((b) => b.addEventListener('click', () => aplicarFiltro(b.dataset.filtro)));

    // Links de categoria do menu também filtram sem recarregar quando já estamos no catálogo
    linksMenu.forEach((a) => a.addEventListener('click', (ev) => {
        ev.preventDefault();
        aplicarFiltro(a.dataset.filtro);
        grade.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));

    aplicarFiltro(new URLSearchParams(window.location.search).get('categoria') || 'todos');
}
