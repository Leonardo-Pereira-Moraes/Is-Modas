<?php
// Cabeçalho do painel. Defina $tituloPagina antes de incluir.
$paginaAtual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <title><?= e($tituloPagina ?? 'Painel') ?> · <?= e(LOJA_NOME) ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-md admin-nav">
    <div class="container">
        <a class="navbar-brand" href="index.php"><?= e(LOJA_NOME) ?> <small>admin</small></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuAdmin" aria-label="Abrir menu">
            <i class="bi bi-list"></i>
        </button>
        <div class="collapse navbar-collapse" id="menuAdmin">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link <?= $paginaAtual === 'index.php' ? 'active' : '' ?>" href="index.php"><i class="bi bi-grid"></i> Produtos</a></li>
                <li class="nav-item"><a class="nav-link <?= $paginaAtual === 'produto_form.php' ? 'active' : '' ?>" href="produto_form.php"><i class="bi bi-plus-lg"></i> Novo produto</a></li>
                <li class="nav-item"><a class="nav-link <?= $paginaAtual === 'senha.php' ? 'active' : '' ?>" href="senha.php"><i class="bi bi-key"></i> Alterar senha</a></li>
                <li class="nav-item"><a class="nav-link" href="../index.php" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Ver site</a></li>
                <li class="nav-item">
                    <form action="logout.php" method="post" class="d-inline">
                        <?= csrfCampo() ?>
                        <button class="nav-link btn btn-link" type="submit"><i class="bi bi-box-arrow-right"></i> Sair</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4">
<?php if ($f = pegarFlash()): ?>
    <div class="alert alert-<?= $f['tipo'] === 'sucesso' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
        <?= e($f['mensagem']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
<?php endif; ?>
