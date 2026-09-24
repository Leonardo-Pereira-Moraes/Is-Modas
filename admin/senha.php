<?php
require_once __DIR__ . '/includes/auth.php';
exigirLogin();

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $atual     = $_POST['senha_atual'] ?? '';
    $nova      = $_POST['senha_nova'] ?? '';
    $confirmar = $_POST['senha_confirmar'] ?? '';

    $stmt = $pdo->prepare('SELECT senha_hash FROM usuarios WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($atual, $hash)) {
        $erros[] = 'Senha atual incorreta.';
    }
    if (mb_strlen($nova) < 8) {
        $erros[] = 'A nova senha precisa ter pelo menos 8 caracteres.';
    }
    if ($nova !== $confirmar) {
        $erros[] = 'A confirmação não confere com a nova senha.';
    }

    if (!$erros) {
        $pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?')
            ->execute([password_hash($nova, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
        session_regenerate_id(true);
        flash('sucesso', 'Senha alterada com sucesso.');
        header('Location: index.php');
        exit;
    }
}

$tituloPagina = 'Alterar senha';
require __DIR__ . '/includes/topo.php';
?>
<h1 class="titulo-pagina mb-4">Alterar senha</h1>

<?php if ($erros): ?>
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0 ps-3">
            <?php foreach ($erros as $erro): ?><li><?= e($erro) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="card-admin" style="max-width:480px">
    <?= csrfCampo() ?>
    <div class="mb-3">
        <label for="senha_atual" class="form-label">Senha atual</label>
        <input type="password" class="form-control" id="senha_atual" name="senha_atual" autocomplete="current-password" required>
    </div>
    <div class="mb-3">
        <label for="senha_nova" class="form-label">Nova senha</label>
        <input type="password" class="form-control" id="senha_nova" name="senha_nova" minlength="8" autocomplete="new-password" required>
    </div>
    <div class="mb-4">
        <label for="senha_confirmar" class="form-label">Confirmar nova senha</label>
        <input type="password" class="form-control" id="senha_confirmar" name="senha_confirmar" minlength="8" autocomplete="new-password" required>
    </div>
    <button type="submit" class="btn btn-ouro">Salvar nova senha</button>
</form>
<?php require __DIR__ . '/includes/rodape.php'; ?>
