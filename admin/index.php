<?php
require_once __DIR__ . '/includes/auth.php';
exigirLogin();

$categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY ordem, nome')->fetchAll();

$filtroCategoria = (int) ($_GET['categoria'] ?? 0);
$busca = trim($_GET['busca'] ?? '');

$where = [];
$params = [];
if ($filtroCategoria > 0) {
    $where[] = 'p.categoria_id = ?';
    $params[] = $filtroCategoria;
}
if ($busca !== '') {
    $where[] = 'p.nome LIKE ?';
    $params[] = '%' . $busca . '%';
}

$sql = "SELECT p.id, p.nome, p.preco, p.imagem, p.destaque, p.ativo, c.nome AS categoria
        FROM produtos p
        JOIN categorias c ON c.id = p.categoria_id"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY p.criado_em DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll();

// Cada cor com seus tamanhos, ex.: "Bege: M, G"
$variacoesPorProduto = [];
if ($produtos) {
    $ids = array_column($produtos, 'id');
    $stmt = $pdo->prepare("SELECT pc.produto_id, pc.nome,
                                  GROUP_CONCAT(t.nome ORDER BY t.ordem SEPARATOR ', ') AS tamanhos
                           FROM produto_cores pc
                           LEFT JOIN variacoes v ON v.cor_id = pc.id
                           LEFT JOIN tamanhos t ON t.id = v.tamanho_id
                           WHERE pc.produto_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")
                           GROUP BY pc.id
                           ORDER BY pc.ordem, pc.id");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $v) {
        $variacoesPorProduto[$v['produto_id']][] = $v['nome'] . ': ' . ($v['tamanhos'] ?: '—');
    }
}

$tituloPagina = 'Produtos';
require __DIR__ . '/includes/topo.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h1 class="titulo-pagina mb-0">Produtos <span class="contador"><?= count($produtos) ?></span></h1>
    <a href="produto_form.php" class="btn btn-ouro"><i class="bi bi-plus-lg"></i> Novo produto</a>
</div>

<form class="row g-2 mb-4" method="get">
    <div class="col-12 col-md-5">
        <input type="search" name="busca" class="form-control" placeholder="Buscar por nome..." value="<?= e($busca) ?>">
    </div>
    <div class="col-8 col-md-4">
        <select name="categoria" class="form-select">
            <option value="0">Todas as categorias</option>
            <?php foreach ($categorias as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $filtroCategoria === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-4 col-md-3">
        <button class="btn btn-outline-ouro w-100" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
    </div>
</form>

<?php if (!$produtos): ?>
    <div class="vazio">
        <i class="bi bi-bag"></i>
        <p>Nenhum produto encontrado.</p>
        <a href="produto_form.php" class="btn btn-ouro">Cadastrar o primeiro produto</a>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table tabela-admin align-middle">
            <thead>
                <tr>
                    <th style="width:72px">Foto</th>
                    <th>Produto</th>
                    <th class="d-none d-md-table-cell">Categoria</th>
                    <th>Preço</th>
                    <th class="d-none d-lg-table-cell">Cores e tamanhos</th>
                    <th class="d-none d-sm-table-cell">Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($produtos as $p): ?>
                <tr>
                    <td>
                        <?php if ($p['imagem']): ?>
                            <img src="../<?= UPLOAD_URL . e($p['imagem']) ?>" alt="" class="thumb">
                        <?php else: ?>
                            <div class="thumb thumb-vazia"><i class="bi bi-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td class="fw-semibold"><?= e($p['nome']) ?></td>
                    <td class="d-none d-md-table-cell"><?= e($p['categoria']) ?></td>
                    <td class="text-nowrap"><?= formatarPreco($p['preco']) ?></td>
                    <td class="d-none d-lg-table-cell small">
                        <?php foreach ($variacoesPorProduto[$p['id']] ?? ['Sem cores'] as $linhaVariacao): ?>
                            <span class="d-block"><?= e($linhaVariacao) ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td class="d-none d-sm-table-cell">
                        <?php if ($p['ativo']): ?>
                            <span class="badge badge-ativo">Visível</span>
                        <?php else: ?>
                            <span class="badge badge-inativo">Oculto</span>
                        <?php endif; ?>
                        <?php if ($p['destaque']): ?>
                            <span class="badge badge-destaque"><i class="bi bi-star-fill"></i> Destaque</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="produto_form.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-ouro" title="Editar"><i class="bi bi-pencil"></i></a>
                        <form action="produto_excluir.php" method="post" class="d-inline form-excluir" data-nome="<?= e($p['nome']) ?>">
                            <?= csrfCampo() ?>
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/rodape.php'; ?>
