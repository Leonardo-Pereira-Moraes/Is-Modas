// Site público Is Modas — JavaScript puro

const LOJA_NOME = document.body.dataset.loja || 'Is Modas';

/* ------------------------------------------------------------------
   Escolha de cor e tamanho + link do WhatsApp com mensagem pré-preenchida
   (mesmo texto de linkWhatsApp() em includes/loja.php)
   ------------------------------------------------------------------ */
function montarMensagem(card, tamanho, cor) {
    const linhas = [
        `Olá, ${LOJA_NOME}! Tenho interesse neste produto:`,
        '',
        `*${card.dataset.nome}*`,
        `Preço: ${card.dataset.preco}`,
    ];
    if (tamanho) linhas.push(`Tamanho: ${tamanho}`);
    if (cor) linhas.push(`Cor: ${cor}`);
    linhas.push('', 'Ainda está disponível?');
    return linhas.join('\n');
}

function atualizarLinkComprar(card) {
    const botao = card.querySelector('.btn-comprar');
    const tamanho = card.querySelector('.tamanho.selecionado')?.textContent.trim();
    const cor = card.querySelector('.cor.selecionado')?.textContent.trim();
    const url = new URL(botao.href);
    url.searchParams.set('text', montarMensagem(card, tamanho, cor));
    botao.href = url.toString();
}

/** Mostra a foto da cor selecionada, ou a foto principal se ela não tiver foto própria. */
function atualizarFoto(card) {
    const img = card.querySelector('.produto-foto img');
    if (!img) return;
    const cor = card.querySelector('.cor.selecionado');
    const nova = cor?.dataset.imagem || img.dataset.principal;
    if (nova && img.getAttribute('src') !== nova) {
        img.classList.add('trocando');
        const pronto = () => img.classList.remove('trocando');
        img.addEventListener('load', pronto, { once: true });
        img.addEventListener('error', pronto, { once: true });
        img.src = nova;
    }
}

function marcar(botao, selecionado) {
    botao.classList.toggle('selecionado', selecionado);
    botao.setAttribute('aria-pressed', String(selecionado));
}

/**
 * Mostra só os tamanhos da cor selecionada (sem cor: todos os tamanhos do produto).
 * Um tamanho escolhido que não existe na nova cor é desmarcado; se sobrar
 * um único tamanho, ele já fica selecionado.
 */
function filtrarTamanhos(card) {
    const cor = card.querySelector('.cor.selecionado');
    const permitidos = cor ? JSON.parse(cor.dataset.tamanhos || '[]') : null;
    const botoes = [...card.querySelectorAll('.tamanho')];

    botoes.forEach((b) => {
        const visivel = !permitidos || permitidos.includes(b.dataset.tamanho);
        b.hidden = !visivel;
        if (!visivel) marcar(b, false);
    });

    const visiveis = botoes.filter((b) => !b.hidden);
    if (visiveis.length === 1) marcar(visiveis[0], true);
}

document.querySelectorAll('.produto-card').forEach((card) => {
    // Cada grupo (cores / tamanhos) permite uma escolha; tocar de novo desmarca
    card.querySelectorAll('.produto-opcoes').forEach((grupo) => {
        const botoes = grupo.querySelectorAll('.opcao');
        botoes.forEach((botao) => {
            botao.addEventListener('click', () => {
                const jaSelecionado = botao.classList.contains('selecionado');
                botoes.forEach((b) => marcar(b, false));
                if (!jaSelecionado) marcar(botao, true);
                if (botao.classList.contains('cor')) {
                    atualizarFoto(card);
                    filtrarTamanhos(card);
                }
                atualizarLinkComprar(card);
            });
        });
    });

    // Começa a baixar a foto da cor quando o dedo/mouse chega no botão
    card.querySelectorAll('.cor').forEach((b) => {
        if (!b.dataset.imagem) return;
        b.addEventListener('pointerenter', () => { new Image().src = b.dataset.imagem; }, { once: true });
    });
});

/* ------------------------------------------------------------------
   Escolha do atendente: qualquer link do WhatsApp abre o modal, e cada
   opção leva a mesma mensagem para o número escolhido
   ------------------------------------------------------------------ */
const whatsModal = document.getElementById('whatsModal');

if (whatsModal) {
    const opcoes = whatsModal.querySelectorAll('.whats-modal-opcao');
    let origem = null; // botão que abriu o modal, para devolver o foco

    function abrirWhatsModal(link) {
        const texto = new URL(link.href).searchParams.get('text') || '';
        opcoes.forEach((o) => {
            o.href = `https://wa.me/${o.dataset.numero}?text=${encodeURIComponent(texto)}`;
        });
        origem = link;
        whatsModal.hidden = false;
        document.body.classList.add('modal-aberto');
        opcoes[0]?.focus();
    }

    function fecharWhatsModal() {
        if (whatsModal.hidden) return;
        whatsModal.hidden = true;
        document.body.classList.remove('modal-aberto');
        origem?.focus();
    }

    document.addEventListener('click', (ev) => {
        const link = ev.target.closest('a[href^="https://wa.me/"]');
        if (!link || whatsModal.contains(link)) return;
        ev.preventDefault();
        abrirWhatsModal(link);
    });

    // O link da opção abre o WhatsApp normalmente; o modal só fecha junto
    opcoes.forEach((o) => o.addEventListener('click', fecharWhatsModal));
    whatsModal.querySelectorAll('[data-fechar]').forEach((el) => el.addEventListener('click', fecharWhatsModal));
    document.addEventListener('keydown', (ev) => { if (ev.key === 'Escape') fecharWhatsModal(); });
}

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
