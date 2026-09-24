<?php
// Copie este arquivo para config/db.php e preencha com as credenciais reais.
// Em produção (InfinityFree), pegue estes valores em vPanel > MySQL Databases:
// Host costuma ser algo como "sqlXXX.infinityfree.com" (nunca "localhost")
// DB_NAME e DB_USER vêm com prefixo "if0_XXXXXXX_..."
define('DB_HOST', 'localhost');
define('DB_NAME', 'is_modas');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erro na conexão com o banco: ' . $e->getMessage());
    die('Erro na conexão com o banco de dados.');
}
