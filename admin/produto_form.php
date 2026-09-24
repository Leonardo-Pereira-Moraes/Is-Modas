<?php
// Cadastro e edição de produto (mesmo formulário).
// GET  produto_form.php          -> novo produto
// GET  produto_form.php?id=5     -> editar produto 5
// POST                           -> salva e volta para a lista
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';
exigirLogin();

$categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY ordem, nome')->fetchAll();
$tamanhos   = $pdo->query('SELECT id, nome FROM tamanhos ORDER BY ordem')->fetchAll();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$produto = [
    'nome' => '', 'descricao' => '', 'preco' => '', 'categoria_id' => 0,
    'imagem' => null, 'destaque' => 0, 'ativo' => 1,
];
$tamanhosMarcados = [];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = ?');
    $stmt->execute([$id]);
    $produto = $stmt->fetch();
    if (!$produto) {
        flash('erro', 'Produto não encontrado.');
        header('Location: index.php');
        exit;
    }
    $stmt = $pdo->prepare('SELECT tamanho_id FROM produto_tamanhos WHERE produto_id = ?');
    $stmt->execute([$id]);
    $tamanhosMarcados = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
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
    $produto['destaque']     = isset($_POST['destaque']) ? 1 : 0;
    $produto['ativo']        = isset($_POST['ativo']) ? 1 : 0;

    $idsValidos = array_map('intval', array_column($tamanhos, 'id'));
    $tamanhosMarcados = array_values(array_intersect(
        array_map('intval', (array) ($_POST['tamanhos'] ?? [])),
        $idsValidos
    ));

    $preco = lerPreco($produto['preco']);

    if ($produto['nome'] === '' || mb_strlen($produto['nome']) > 120) {
        $erros[] = 'Informe o nome do produto (até 120 caracteres).';
    }
    if ($preco === null) {
        $erros[] = 'Informe um preço válido (ex.: 129,90).';
    }
    if (!in_array($produto['categoria_id'], array_map('intval', array_column($categorias, 'id')), true)) {
        $erros[] = 'Selecione uma categoria.';
    }
    if (!$tamanhosMarcados) {
        $erros[] = 'Marque pelo menos um tamanho.';
    }

    $novaImagem = null;
    if (!$erros) {
        try {
            $novaImagem = salvarImagemProduto('imagem');
            if (!$novaImagem && !$produto['imagem']) {
                $erros[] = 'Envie uma foto do produto.';
            }
        } catch (RuntimeException $ex) {
            $erros[] = $ex->getMessage();
        }
    }

    if (!$erros) {
        $imagemAntiga = $produto['imagem'];
        $imagemFinal  = $novaImagem ?? $imagemAntiga;

        try {
            $pdo->beginTransaction();

            $dados = [
                $produto['categoria_id'], $produto['nome'], $produto['descricao'] ?: null,
                $preco, $imagemFinal, $produto['destaque'], $produto['ativo'],
            ];
            if ($id > 0) {
                $pdo->prepare('UPDATE produtos SET categoria_id = ?, nome = ?, descricao = ?, preco = ?,
                                      imagem = ?, destaque = ?, ativo = ? WHERE id = ?')
                    ->execute([...$dados, $id]);
            } else {
                $pdo->prepare('INSERT INTO produtos (categoria_id, nome, descricao, preco, imagem, destaque, ativo)
                               VALUES (?, ?, ?, ?, ?, ?, ?)')
                    ->execute($dados);
                $id = (int) $pdo->lastInsertId();
            }

            $pdo->prepare('DELETE FROM produto_tamanhos WHERE produto_id = ?')->execute([$id]);
            $ins = $pdo->prepare('INSERT INTO produto_tamanhos (produto_id, tamanho_id) VALUES (?, ?)');
            foreach ($tamanhosMarcados as $tid) {
                $ins->execute([$id, $tid]);
            }

            $pdo->commit();
        } catch (PDOException $ex) {
            $pdo->rollBack();
            removerImagemProduto($novaImagem);
            error_log('Erro ao salvar produto: ' . $ex->getMessage());
            $erros[] = 'Erro ao salvar no banco de dados. Tente novamente.';
        }

        if (!$erros) {
            // Foto trocada: apaga a antiga só depois de salvar com sucesso
            if ($novaImagem && $imagemAntiga) {
                removerImagemProduto($imagemAntiga);
            }
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
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card-admin" novalidate>
    <?= csrfCampo() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= UPLOAD_MAX_BYTES ?>">

    <div class="row g-4">
        <!-- Foto -->
        <div class="col-12 col-md-4">
            <label class="form-label">Foto do produto</label>
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

            <div class="mb-3">
                <span class="form-label d-block">Tamanhos disponíveis *</span>
                <div class="tamanhos-check">
                    <?php foreach ($tamanhos as $t): ?>
                        <input type="checkbox" class="btn-check" name="tamanhos[]" id="tam<?= $t['id'] ?>" value="<?= $t['id'] ?>"
                               <?= in_array((int) $t['id'], $tamanhosMarcados, true) ? 'checked' : '' ?>>
                        <label class="btn btn-tamanho" for="tam<?= $t['id'] ?>"><?= e($t['nome']) ?></label>
                    <?php endforeach; ?>
                </div>
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
