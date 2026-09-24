<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';
exigirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
validarCsrf();

$id = (int) ($_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT nome, imagem FROM produtos WHERE id = ?');
$stmt->execute([$id]);
$produto = $stmt->fetch();

if (!$produto) {
    flash('erro', 'Produto não encontrado.');
} else {
    // produto_tamanhos é apagado junto (ON DELETE CASCADE)
    $pdo->prepare('DELETE FROM produtos WHERE id = ?')->execute([$id]);
    removerImagemProduto($produto['imagem']);
    flash('sucesso', 'Produto "' . $produto['nome'] . '" excluído.');
}

header('Location: index.php');
exit;
