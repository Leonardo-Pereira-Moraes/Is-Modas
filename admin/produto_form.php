<?php
// Cadastro e edição de produto (mesmo formulário).
// GET  produto_form.php          -> novo produto
// GET  produto_form.php?id=5     -> editar produto 5
// POST                           -> salva e volta para a lista
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';
exigirLogin();

const TIPOS_TAMANHO = [
    'letra'  => 'Letra (PP a XG)',
    'numero' => 'Numeração (34 a 46)',
    'unico'  => 'Único',
];

$categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY ordem, nome')->fetchAll();
$tamanhos   = $pdo->query('SELECT id, nome, tipo FROM tamanhos ORDER BY ordem')->fetchAll();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$produto = [
    'nome' => '', 'descricao' => '', 'preco' => '', 'categoria_id' => 0,
    'imagem' => null, 'tipo_tamanho' => 'letra', 'destaque' => 0, 'ativo' => 1,
];
$coresExistentes = []; // id => linha do banco
// Linhas de cor exibidas no formulário: chave => [id, nome, imagem, tamanhos]
// chave "e<id>" para cores já salvas, "n<numero>" para novas
$corVazia = ['id' => null, 'nome' => '', 'imagem' => null, 'tamanhos' => []];
$cores = ['n0' => $corVazia];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = ?');
    $stmt->execute([$id]);
    $produto = $stmt->fetch();
    if (!$produto) {
        flash('erro', 'Produto não encontrado.');
        header('Location: index.php');
        exit;
    }

    // Tamanhos de cada cor (tabela variacoes)
    $stmt = $pdo->prepare('SELECT cor_id, tamanho_id FROM variacoes WHERE produto_id = ?');
    $stmt->execute([$id]);
    $tamanhosPorCor = [];
    foreach ($stmt->fetchAll() as $v) {
        $tamanhosPorCor[(int) $v['cor_id']][] = (int) $v['tamanho_id'];
    }

    $stmt = $pdo->prepare('SELECT id, nome, imagem FROM produto_cores WHERE produto_id = ? ORDER BY ordem, id');
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll() as $c) {
        $coresExistentes[(int) $c['id']] = $c;
    }
    if ($coresExistentes) {
        $cores = [];
        foreach ($coresExistentes as $cid => $c) {
            $cores['e' . $cid] = [
                'id' => $cid, 'nome' => $c['nome'], 'imagem' => $c['imagem'],
                'tamanhos' => $tamanhosPorCor[$cid] ?? [],
            ];
        }
    }
}

/** Aceita "1.299,90", "129,90" ou "129.90" e devolve float (ou null se inválido). */
function lerPreco(string $valor): ?float
{
    $valor = trim(str_replace(['R$', ' '], '', $valor));
    if (str_contains($valor, ',')) {
        $valor = str_replace(['.', ','], ['', '.'], $valor);
    }
    return is_numeric($valor) && (float) $valor > 0 ? round((float) $valor, 2) : null;
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    $produto['nome']         = trim($_POST['nome'] ?? '');
    $produto['descricao']    = trim($_POST['descricao'] ?? '');
    $produto['preco']        = trim($_POST['preco'] ?? '');
    $produto['categoria_id'] = (int) ($_POST['categoria_id'] ?? 0);
    $produto['tipo_tamanho'] = (string) ($_POST['tipo_tamanho'] ?? '');
    $produto['destaque']     = isset($_POST['destaque']) ? 1 : 0;
    $produto['ativo']        = isset($_POST['ativo']) ? 1 : 0;

    $preco = lerPreco($produto['preco']);
    $tipoValido = isset(TIPOS_TAMANHO[$produto['tipo_tamanho']]);
    $tipoUnico = $produto['tipo_tamanho'] === 'unico';

    // Só são aceitos tamanhos do tipo escolhido; "Único" é marcado automaticamente
    $idsDoTipo = [];
    foreach ($tamanhos as $t) {
        if ($t['tipo'] === $produto['tipo_tamanho']) {
            $idsDoTipo[] = (int) $t['id'];
        }
    }

    // ----- Cores e seus tamanhos -----
    $cores = [];
    $coresValidas = [];
    $nomesUsados = [];
    foreach ((array) ($_POST['cores'] ?? []) as $chave => $linha) {
        $chave = (string) $chave;
        if (!is_array($linha) || !preg_match('/^[en]\d{1,9}$/', $chave)) {
            continue;
        }
        $cid = (int) ($linha['id'] ?? 0);
        $cid = isset($coresExistentes[$cid]) ? $cid : null; // ignora ids de outro produto
        $nome = trim((string) ($linha['nome'] ?? ''));
        $arquivo = arquivoDaLista('cor_imagem', $chave);
        $temArquivo = $arquivo && $arquivo['error'] !== UPLOAD_ERR_NO_FILE;
        $marcados = array_values(array_intersect(
            array_map('intval', (array) ($linha['tamanhos'] ?? [])),
            $idsDoTipo
        ));
        $cor = [
            'id'           => $cid,
            'nome'         => $nome,
            'imagem'       => $cid ? $coresExistentes[$cid]['imagem'] : null,
            'remover_foto' => isset($linha['remover_foto']),
            'tamanhos'     => $tipoUnico ? $idsDoTipo : $marcados,
            'chave'        => $chave,
        ];
        $cores[$chave] = $cor;

        if ($nome === '') {
            if ($temArquivo || (!$tipoUnico && $marcados)) {
                $erros[] = 'Há uma cor com foto ou tamanhos marcados, mas sem nome.';
            }
            continue; // linha em branco é ignorada
        }
        if (mb_strlen($nome) > 40) {
            $erros[] = "O nome da cor \"$nome\" é longo demais (até 40 caracteres).";
        }
        $nomeComparar = mb_strtolower($nome);
        if (isset($nomesUsados[$nomeComparar])) {
            $erros[] = "A cor \"$nome\" foi adicionada mais de uma vez.";
        }
        $nomesUsados[$nomeComparar] = true;
        if ($tipoValido && !$cor['tamanhos']) {
            $erros[] = "Marque os tamanhos disponíveis da cor \"$nome\".";
        }
        $coresValidas[] = $cor;
    }
    if (!$cores) {
        $cores = ['n0' => $corVazia];
    }

    if ($produto['nome'] === '' || mb_strlen($produto['nome']) > 120) {
        $erros[] = 'Informe o nome do produto (até 120 caracteres).';
    }
    if ($preco === null) {
        $erros[] = 'Informe um preço válido (ex.: 129,90).';
    }
    if (!in_array($produto['categoria_id'], array_map('intval', array_column($categorias, 'id')), true)) {
        $erros[] = 'Selecione uma categoria.';
    }
    if (!$tipoValido) {
        $erros[] = 'Escolha o tipo de tamanho.';
        $produto['tipo_tamanho'] = 'letra';
    }
    if (!$coresValidas) {
        $erros[] = 'Adicione pelo menos uma cor com seus tamanhos.';
    }

    // Uploads só depois de tudo validado, para não deixar arquivos órfãos
    $novaImagem = null;
    $arquivosNovos = [];
    if (!$erros) {
        try {
            $novaImagem = salvarImagemProduto('imagem');
            if ($novaImagem) {
                $arquivosNovos[] = $novaImagem;
            } elseif (!$produto['imagem']) {
                $erros[] = 'Envie a foto principal do produto.';
            }
            foreach ($coresValidas as &$cor) {
                try {
                    $cor['nova_imagem'] = salvarImagem(arquivoDaLista('cor_imagem', $cor['chave']));
                } catch (RuntimeException $ex) {
                    throw new RuntimeException('Foto da cor "' . $cor['nome'] . '": ' . $ex->getMessage());
                }
                if ($cor['nova_imagem']) {
                    $arquivosNovos[] = $cor['nova_imagem'];
                }
            }
        } catch (RuntimeException $ex) {
            $erros[] = $ex->getMessage();
        }
        unset($cor);
        if ($erros) {
            array_map('removerImagemProduto', $arquivosNovos);
        }
    }

    if (!$erros) {
        $imagemAntiga = $produto['imagem'];
        $imagemFinal  = $novaImagem ?? $imagemAntiga;
        $arquivosParaApagar = $novaImagem && $imagemAntiga ? [$imagemAntiga] : [];

        try {
            $pdo->beginTransaction();

            $dados = [
                $produto['categoria_id'], $produto['nome'], $produto['descricao'] ?: null,
                $preco, $imagemFinal, $produto['tipo_tamanho'], $produto['destaque'], $produto['ativo'],
            ];
            if ($id > 0) {
                $pdo->prepare('UPDATE produtos SET categoria_id = ?, nome = ?, descricao = ?, preco = ?,
                                      imagem = ?, tipo_tamanho = ?, destaque = ?, ativo = ? WHERE id = ?')
                    ->execute([...$dados, $id]);
            } else {
                $pdo->prepare('INSERT INTO produtos (categoria_id, nome, descricao, preco, imagem, tipo_tamanho, destaque, ativo)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute($dados);
                $id = (int) $pdo->lastInsertId();
            }

            // Cores: atualiza as mantidas, insere as novas, apaga as removidas
            $upd = $pdo->prepare('UPDATE produto_cores SET nome = ?, imagem = ?, ordem = ? WHERE id = ? AND produto_id = ?');
            $insCor = $pdo->prepare('INSERT INTO produto_cores (produto_id, nome, imagem, ordem) VALUES (?, ?, ?, ?)');
            $idsMantidos = [];
            $tamanhosPorCorSalva = []; // id da cor => tamanhos
            foreach ($coresValidas as $ordem => $cor) {
                $imagemCor = $cor['nova_imagem'] ?? ($cor['remover_foto'] ? null : $cor['imagem']);
                if ($cor['imagem'] && $imagemCor !== $cor['imagem']) {
                    $arquivosParaApagar[] = $cor['imagem'];
                }
                if ($cor['id']) {
                    $upd->execute([$cor['nome'], $imagemCor, $ordem, $cor['id'], $id]);
                    $idsMantidos[] = $cid = $cor['id'];
                } else {
                    $insCor->execute([$id, $cor['nome'], $imagemCor, $ordem]);
                    $cid = (int) $pdo->lastInsertId();
                }
                $tamanhosPorCorSalva[$cid] = $cor['tamanhos'];
            }
            // Cores removidas: as variações delas somem junto (ON DELETE CASCADE)
            $delCor = $pdo->prepare('DELETE FROM produto_cores WHERE id = ?');
            foreach ($coresExistentes as $cid => $c) {
                if (!in_array($cid, $idsMantidos, true)) {
                    $delCor->execute([$cid]);
                    if ($c['imagem']) {
                        $arquivosParaApagar[] = $c['imagem'];
                    }
                }
            }

            // Variações (cor x tamanho) são regravadas do zero
            $pdo->prepare('DELETE FROM variacoes WHERE produto_id = ?')->execute([$id]);
            $insVar = $pdo->prepare('INSERT INTO variacoes (produto_id, cor_id, tamanho_id) VALUES (?, ?, ?)');
            foreach ($tamanhosPorCorSalva as $cid => $tids) {
                foreach ($tids as $tid) {
                    $insVar->execute([$id, $cid, $tid]);
                }
            }

            $pdo->commit();
        } catch (PDOException $ex) {
            $pdo->rollBack();
            array_map('removerImagemProduto', $arquivosNovos);
            error_log('Erro ao salvar produto: ' . $ex->getMessage());
            $erros[] = 'Erro ao salvar no banco de dados. Tente novamente.';
        }

        if (!$erros) {
            // Fotos substituídas/removidas só são apagadas depois de salvar com sucesso
            array_map('removerImagemProduto', $arquivosParaApagar);
            flash('sucesso', 'Produto "' . $produto['nome'] . '" salvo com sucesso.');
            header('Location: index.php');
            exit;
        }
    }
}

// No formulário o preço aparece no padrão brasileiro
$precoCampo = is_numeric($produto['preco'])
    ? number_format((float) $produto['preco'], 2, ',', '.')
    : $produto['preco'];

/**
 * HTML de uma linha de cor com seus tamanhos (também usado como modelo pelo JavaScript).
 * Todos os tamanhos são impressos; os de outro tipo ficam escondidos (admin.js alterna).
 */
function linhaCor(string $chave, array $cor, array $tamanhos, string $tipo): string
{
    $src = $cor['imagem'] ? '../' . UPLOAD_URL . e($cor['imagem']) : '';
    $k = e($chave);
    ob_start(); ?>
    <div class="cor-linha" data-chave="<?= $k ?>">
        <input type="hidden" name="cores[<?= $k ?>][id]" value="<?= $cor['id'] ?: '' ?>">
        <label class="cor-foto" title="Foto desta cor (opcional)">
            <img src="<?= $src ?>" alt="" class="<?= $src ? '' : 'd-none' ?>">
            <i class="bi bi-camera <?= $src ? 'd-none' : '' ?>"></i>
            <input type="file" name="cor_imagem[<?= $k ?>]" accept="image/jpeg,image/png,image/webp" class="d-none cor-arquivo">
        </label>
        <div class="cor-campos">
            <input type="text" class="form-control form-control-sm" name="cores[<?= $k ?>][nome]"
                   value="<?= e($cor['nome']) ?>" maxlength="40" placeholder="Nome da cor (ex.: Preto)" aria-label="Nome da cor">
            <?php if ($cor['imagem']): ?>
                <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" name="cores[<?= $k ?>][remover_foto]"
                           id="rem_<?= $k ?>" <?= !empty($cor['remover_foto']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="rem_<?= $k ?>">Usar a foto principal</label>
                </div>
            <?php else: ?>
                <small class="text-muted-admin">Sem foto: usa a foto principal</small>
            <?php endif; ?>

            <div class="tamanhos-check tamanhos-cor mt-2" role="group" aria-label="Tamanhos desta cor">
                <?php foreach ($tamanhos as $t):
                    $mesmoTipo = $t['tipo'] === $tipo;
                    $marcado = $mesmoTipo && ($tipo === 'unico' || in_array((int) $t['id'], $cor['tamanhos'], true)); ?>
                    <span class="tamanho-opcao" data-tipo="<?= e($t['tipo']) ?>" <?= $mesmoTipo ? '' : 'hidden' ?>>
                        <input type="checkbox" class="btn-check" name="cores[<?= $k ?>][tamanhos][]"
                               id="tam_<?= $k ?>_<?= $t['id'] ?>" value="<?= $t['id'] ?>" <?= $marcado ? 'checked' : '' ?>
                               <?= $t['tipo'] === 'unico' ? 'onclick="return false"' : '' ?>>
                        <label class="btn btn-tamanho" for="tam_<?= $k ?>_<?= $t['id'] ?>"><?= e($t['nome']) ?></label>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger cor-remover" title="Remover cor" aria-label="Remover cor">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <?php return ob_get_clean();
}

$tituloPagina = $id > 0 ? 'Editar produto' : 'Novo produto';
require __DIR__ . '/includes/topo.php';
?>
<a href="index.php" class="link-voltar"><i class="bi bi-arrow-left"></i> Voltar para produtos</a>
<h1 class="titulo-pagina mt-2 mb-4"><?= e($tituloPagina) ?></h1>

<?php if ($erros): ?>
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0 ps-3">
            <?php foreach ($erros as $erro): ?><li><?= e($erro) ?></li><?php endforeach; ?>
        </ul>
        <?php if (!empty($_FILES)): ?>
            <small class="d-block mt-2">Por segurança o navegador não reenvia fotos: selecione-as novamente.</small>
        <?php endif; ?>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card-admin" novalidate>
    <?= csrfCampo() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= UPLOAD_MAX_BYTES ?>">

    <div class="row g-4">
        <!-- Foto principal -->
        <div class="col-12 col-md-4">
            <label class="form-label">Foto principal</label>
            <label for="imagem" class="upload-area" id="uploadArea">
                <img id="previewImagem"
                     src="<?= $produto['imagem'] ? '../' . UPLOAD_URL . e($produto['imagem']) : '' ?>"
                     alt="Pré-visualização" class="<?= $produto['imagem'] ? '' : 'd-none' ?>">
                <span class="upload-dica <?= $produto['imagem'] ? 'd-none' : '' ?>" id="uploadDica">
                    <i class="bi bi-cloud-arrow-up"></i>
                    Toque para escolher a foto<br><small>JPG, PNG ou WEBP · até 5 MB</small>
                </span>
            </label>
            <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp" class="d-none">
            <?php if ($produto['imagem']): ?>
                <p class="form-text">Envie outra foto apenas se quiser substituir a atual.</p>
            <?php endif; ?>
        </div>

        <!-- Dados -->
        <div class="col-12 col-md-8">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome *</label>
                <input type="text" class="form-control" id="nome" name="nome" maxlength="120" required value="<?= e($produto['nome']) ?>">
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label for="preco" class="form-label">Preço (R$) *</label>
                    <input type="text" class="form-control" id="preco" name="preco" inputmode="decimal" placeholder="129,90" required value="<?= e($precoCampo) ?>">
                </div>
                <div class="col-6">
                    <label for="categoria_id" class="form-label">Categoria *</label>
                    <select class="form-select" id="categoria_id" name="categoria_id" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (int) $produto['categoria_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Tipo de tamanho + cores, cada cor com seus tamanhos (admin.js) -->
            <div class="bloco-form mb-3">
                <div class="row g-3 mb-3">
                    <div class="col-12 col-sm-6">
                        <label for="tipo_tamanho" class="form-label">Tipo de tamanho *</label>
                        <select class="form-select" id="tipo_tamanho" name="tipo_tamanho" required>
                            <?php foreach (TIPOS_TAMANHO as $valor => $rotulo): ?>
                                <option value="<?= $valor ?>" <?= $produto['tipo_tamanho'] === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <span class="form-label d-block">Cores e tamanhos *</span>
                <p class="form-text mt-0 mb-2">Adicione cada cor e marque os tamanhos que <strong>aquela cor</strong> tem. A foto é opcional: sem ela, o site mostra a foto principal.</p>
                <div id="listaCores">
                    <?php foreach ($cores as $chave => $c): ?>
                        <?= linhaCor($chave, $c, $tamanhos, $produto['tipo_tamanho']) ?>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline-ouro mt-2" id="adicionarCor">
                    <i class="bi bi-plus-lg"></i> Adicionar cor
                </button>
                <template id="modeloCor"><?= linhaCor('__CHAVE__', $corVazia, $tamanhos, $produto['tipo_tamanho']) ?></template>
            </div>

            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição <small class="text-muted-admin">(opcional)</small></label>
                <textarea class="form-control" id="descricao" name="descricao" rows="3" maxlength="1000"><?= e($produto['descricao']) ?></textarea>
            </div>

            <div class="d-flex flex-wrap gap-4 mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" <?= $produto['ativo'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ativo">Visível no site</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="destaque" name="destaque" <?= $produto['destaque'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="destaque">Destaque na página inicial</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-ouro"><i class="bi bi-check-lg"></i> Salvar produto</button>
                <a href="index.php" class="btn btn-outline-ouro">Cancelar</a>
            </div>
        </div>
    </div>
</form>
<?php require __DIR__ . '/includes/rodape.php'; ?>
