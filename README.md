# Is Modas — Catálogo online

Catálogo de roupas com compra via WhatsApp e painel admin.
Stack: PHP 8 + MySQL (PDO) + Bootstrap 5 + JavaScript puro.

## Instalação local (XAMPP)

1. Inicie Apache e MySQL no XAMPP.
2. Importe `database/schema.sql` no phpMyAdmin (cria o banco `is_modas`).
3. Copie `config/db.example.php` para `config/db.php` e ajuste as credenciais (o `db.php` não vai para o Git).
4. Em `config/config.php`, ajuste os números do WhatsApp em `WHATSAPP_CONTATOS` (rótulo => só dígitos: 55 + DDD + número). Ao tocar em um botão do WhatsApp, o cliente escolhe com quem falar.
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
| `categorias`       | feminino, masculino, sapatos masculinos (`slug` usado no filtro) |
| `tamanhos`         | PP…XG, 34…46 (com 37, 39, 41, 43), Único, com `tipo` (letra, numero, unico) e `ordem` de exibição |
| `produtos`         | nome, preço, descrição, foto, `tipo_tamanho`, destaque, ativo |
| `produto_cores`    | cores de cada produto: nome + foto opcional (sem foto, usa a principal) |
| `variacoes`        | quais tamanhos cada cor tem (produto × cor × tamanho); os tamanhos do produto são a união das suas cores |

Nova categoria ou tamanho: basta um `INSERT` na tabela (tamanho com o `tipo` certo), e o site e o painel já passam a exibir.

### Atualizando um banco antigo

Bancos criados com versões antigas do `schema.sql` rodam as migrações que
faltam, **uma vez cada e nesta ordem** (phpMyAdmin → Importar):

1. `database/migracao_001_cores_tipo_tamanho.sql`: cores e tipo de tamanho por produto
   (deduz o tipo pelos tamanhos já marcados).
2. `database/migracao_002_variacoes.sql`: tamanhos por cor. Cada cor recebe os
   tamanhos que o produto tinha e a tabela `produto_tamanhos` é removida.
3. `database/migracao_003_sapatos_masculinos.sql`: "Acessórios" vira "Sapatos Masculinos"
   e entram os tamanhos 37, 39, 41 e 43.

Como saber o que falta: sem a tabela `produto_cores`, rode a partir da 001; com `produto_cores`
mas sem `variacoes`, a partir da 002; com a categoria "Acessórios" ainda no banco, rode a 003. Instalações novas usam só o `schema.sql`, que já inclui tudo.
