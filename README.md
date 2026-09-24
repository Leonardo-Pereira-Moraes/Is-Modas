# Is Modas — Catálogo online

Catálogo de roupas com compra via WhatsApp e painel admin.
Stack: PHP 8 + MySQL (PDO) + Bootstrap 5 + JavaScript puro.

## Instalação local (XAMPP)

1. Inicie Apache e MySQL no XAMPP.
2. Importe `database/schema.sql` no phpMyAdmin (cria o banco `is_modas`).
3. Copie `config/db.example.php` para `config/db.php` e ajuste as credenciais (o `db.php` não vai para o Git).
4. Em `config/config.php`, coloque o número do WhatsApp em `WHATSAPP_NUMERO` (só dígitos: 55 + DDD + número).
5. Coloque a logo em `assets/img/logo.png` (PNG com fundo transparente fica melhor).
6. Acesse:
   - Site: http://localhost/Ls%20Modas/
   - Admin: http://localhost/Ls%20Modas/admin/ — usuário `admin`, senha `admin123` (**troque em "Alterar senha"**)

## Estrutura

```
index.php, catalogo.php      páginas públicas
includes/                    cabeçalho, rodapé, card do produto e consultas
admin/                       login, lista, formulário (criar/editar), exclusão, senha
admin/includes/              sessão/CSRF, upload, layout do painel
assets/css, assets/js        style.css + main.js (site) · admin.css + admin.js (painel)
assets/uploads/produtos/     fotos enviadas pelo painel (scripts bloqueados via .htaccess)
config/                      config.php (loja, WhatsApp, upload) e db.php
database/schema.sql          tabelas + dados iniciais
```

## Banco de dados

| Tabela             | Função                                                   |
|--------------------|----------------------------------------------------------|
| `usuarios`         | login do painel (senha com `password_hash`)              |
| `categorias`       | feminino, masculino, acessórios (`slug` usado no filtro) |
| `tamanhos`         | PP…XG, 34…46, Único (`ordem` define a exibição)          |
| `produtos`         | nome, preço, descrição, foto, destaque, ativo            |
| `produto_tamanhos` | N:N produto × tamanho                                    |

Nova categoria ou tamanho: basta um `INSERT` na tabela, e o site e o painel já passam a exibir.
