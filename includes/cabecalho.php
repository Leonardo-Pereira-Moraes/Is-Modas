<?php
// Cabeçalho público. Defina antes de incluir:
//   $tituloPagina (opcional), $descricaoPagina (opcional), $paginaAtiva ('inicio' | 'catalogo')
$categoriasMenu = listarCategorias($pdo);
$paginaAtiva = $paginaAtiva ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($tituloPagina) ? e($tituloPagina) . ' · ' : '' ?><?= e(LOJA_NOME) ?></title>
    <meta name="description" content="<?= e($descricaoPagina ?? LOJA_NOME . ' — moda feminina, masculina e sapatos masculinos. Escolha suas peças e compre pelo WhatsApp.') ?>">
    <meta name="theme-color" content="#0D0D0D">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="apple-touch-icon" href="assets/img/favicon.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body data-loja="<?= e(LOJA_NOME) ?>">

<header class="site-header">
    <div class="container">
        <a href="index.php" class="logo-wrap" aria-label="<?= e(LOJA_NOME) ?> — página inicial">
            <img src="assets/img/logo.png" alt="<?= e(LOJA_NOME) ?>" class="logo"
                 onerror="this.hidden=true;this.nextElementSibling.hidden=false">
            <span class="logo-texto" hidden><?= e(LOJA_NOME) ?></span>
        </a>
    </div>
    <nav class="menu" aria-label="Menu principal">
        <ul class="container">
            <li><a href="index.php" class="<?= $paginaAtiva === 'inicio' ? 'ativo' : '' ?>">Início</a></li>
            <li><a href="catalogo.php" data-filtro="todos" class="<?= $paginaAtiva === 'catalogo' ? 'ativo' : '' ?>">Catálogo</a></li>
            <?php foreach ($categoriasMenu as $c): ?>
                <li><a href="catalogo.php?categoria=<?= e($c['slug']) ?>" data-filtro="<?= e($c['slug']) ?>"><?= e($c['nome']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>
</header>
