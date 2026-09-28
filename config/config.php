<?php
// Configurações gerais da loja

define('LOJA_NOME', 'Is Modas');

// Números do WhatsApp que atendem a loja. Ao tocar em um botão do WhatsApp,
// o cliente escolhe com quem falar. Rótulo => número só com dígitos:
// 55 + DDD + número (ex.: 5511987654321). O primeiro é o padrão (usado se o
// JavaScript não carregar).
define('WHATSAPP_CONTATOS', [
    'Isvanice'      => '5564992927610',
    'o Responsável' => '5564992461313',
]);

// Pasta de upload das fotos (relativa à raiz do site)
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/produtos/');
define('UPLOAD_URL', 'assets/uploads/produtos/');
define('UPLOAD_MAX_BYTES', 5 * 1024 * 1024); // 5 MB

require_once __DIR__ . '/db.php';

/** Escapa texto para saída em HTML. */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/** Formata preço no padrão brasileiro: 129.9 -> "R$ 129,90". */
function formatarPreco(float|string $valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}
