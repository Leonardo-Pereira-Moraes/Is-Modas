<?php
require_once __DIR__ . '/includes/auth.php';

if (estaLogado()) {
    header('Location: index.php');
    exit;
}

const MAX_TENTATIVAS = 5;
const BLOQUEIO_SEGUNDOS = 5 * 60;

$erro = '';
$usuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = $_POST['senha'] ?? '';

    $bloqueadoAte = $_SESSION['login_bloqueado_ate'] ?? 0;
    if ($bloqueadoAte > time()) {
        $erro = 'Muitas tentativas. Aguarde alguns minutos e tente novamente.';
    } elseif ($usuario === '' || $senha === '') {
        $erro = 'Informe usuário e senha.';
    } else {
        $stmt = $pdo->prepare('SELECT id, nome, senha_hash FROM usuarios WHERE usuario = ?');
        $stmt->execute([$usuario]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($senha, $admin['senha_hash'])) {
            session_regenerate_id(true);
            unset($_SESSION['login_tentativas'], $_SESSION['login_bloqueado_ate']);
            $_SESSION['admin_id']         = (int) $admin['id'];
            $_SESSION['admin_nome']       = $admin['nome'];
            $_SESSION['ultima_atividade'] = time();

            $pdo->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?')->execute([$admin['id']]);

            header('Location: index.php');
            exit;
        }

        $_SESSION['login_tentativas'] = ($_SESSION['login_tentativas'] ?? 0) + 1;
        if ($_SESSION['login_tentativas'] >= MAX_TENTATIVAS) {
            $_SESSION['login_bloqueado_ate'] = time() + BLOQUEIO_SEGUNDOS;
            $_SESSION['login_tentativas'] = 0;
        }
        $erro = 'Usuário ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <title>Entrar · <?= e(LOJA_NOME) ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="login-page">
<main class="login-box">
    <img src="../assets/img/logo.png" alt="<?= e(LOJA_NOME) ?>" class="login-logo">
    <h1>Painel administrativo</h1>

    <?php if ($erro): ?>
        <div class="alert alert-danger py-2" role="alert"><?= e($erro) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="on" novalidate>
        <?= csrfCampo() ?>
        <div class="mb-3">
            <label for="usuario" class="form-label">Usuário</label>
            <input type="text" class="form-control" id="usuario" name="usuario" value="<?= e($usuario) ?>" autocomplete="username" required autofocus>
        </div>
        <div class="mb-4">
            <label for="senha" class="form-label">Senha</label>
            <input type="password" class="form-control" id="senha" name="senha" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn btn-ouro w-100">Entrar</button>
    </form>
    <a href="../index.php" class="voltar-site">← Voltar para o site</a>
</main>
</body>
</html>
