<?php
// Sessão, proteção de páginas e CSRF do painel admin.
// Toda página do admin começa com: require_once __DIR__ . '/includes/auth.php';

require_once __DIR__ . '/../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('ISMODAS_ADMIN');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

const SESSAO_TIMEOUT = 2 * 60 * 60; // 2h sem atividade desloga

function estaLogado(): bool
{
    if (empty($_SESSION['admin_id'])) {
        return false;
    }
    if (time() - ($_SESSION['ultima_atividade'] ?? 0) > SESSAO_TIMEOUT) {
        $_SESSION = [];
        session_destroy();
        return false;
    }
    $_SESSION['ultima_atividade'] = time();
    return true;
}

/** Chame no topo de toda página protegida. */
function exigirLogin(): void
{
    if (!estaLogado()) {
        header('Location: login.php');
        exit;
    }
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfCampo(): string
{
    return '<input type="hidden" name="csrf" value="' . csrfToken() . '">';
}

/** Aborta a requisição se o token CSRF do POST não bater. */
function validarCsrf(): void
{
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(csrfToken(), $enviado)) {
        http_response_code(400);
        die('Sessão expirada ou requisição inválida. Volte e tente novamente.');
    }
}

/** Mensagem única exibida na próxima página (sucesso/erro). */
function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function pegarFlash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}
