# 🎯 Exemplos Práticos - Express PHP

Esta pasta contém exemplos práticos para aprender a usar o Express PHP Microframework.

## 🚀 Como usar os exemplos

### Método 1: Servidor PHP Built-in
```bash
# Navegar para a pasta examples
cd examples

# Executar um exemplo específico
php -S localhost:8000 example_user.php

# Acessar no navegador: http://localhost:8000
```

### Método 2: Executar diretamente
```bash
# Executar exemplo diretamente
php examples/example_user.php

# Servidor será iniciado automaticamente na porta 8000
```

## 📚 Exemplos Disponíveis

### 🟢 Exemplos Básicos (Comece aqui!)

| Arquivo | Descrição | Conceitos |
|---------|-----------|-----------|
| **[example_user.php](example_user.php)** | Sistema de usuários básico | Rotas, JSON, parâmetros |
| **[example_product.php](example_product.php)** | CRUD completo de produtos | REST API, validação, middleware |
| **[example_auth.php](example_auth.php)** | Sistema de autenticação | JWT, login, middleware auth |

### 🟡 Exemplos Intermediários

| Arquivo | Descrição | Conceitos |
|---------|-----------|-----------|
| **[example_upload.php](example_upload.php)** | Upload de arquivos | File upload, validação, storage |
| **[example_blog.php](example_blog.php)** | Sistema de blog | Categorias, posts, relacionamentos |
| **[example_security.php](example_security.php)** | Middlewares de segurança | CORS, XSS, CSRF, Rate Limiting |

### 🔴 Exemplos Avançados

| Arquivo | Descrição | Conceitos |
|---------|-----------|-----------|
| **[example_admin.php](example_admin.php)** | Área administrativa | Permissões, dashboards, relatórios |
| **[example_streaming.php](example_streaming.php)** | Server-Sent Events | SSE, streaming, real-time |
| **[example_complete.php](example_complete.php)** | Aplicação completa | Integração total dos recursos |

### 🛠️ Exemplos de Configuração

| Arquivo | Descrição | Conceitos |
|---------|-----------|-----------|
| **[app.php](app.php)** | App modular completo | Sub-routers, modularização |
| **[example_modular.php](example_modular.php)** | Estrutura modular | Organização, arquitetura |
| **[example_advanced.php](example_advanced.php)** | Recursos avançados | Performance, otimização |

## 🎓 Roteiro de Aprendizado

### 1️⃣ Iniciante
1. **[example_user.php](example_user.php)** - Aprenda rotas básicas
2. **[example_auth.php](example_auth.php)** - Sistema de login
3. **[example_security.php](example_security.php)** - Segurança básica

### 2️⃣ Intermediário
1. **[example_product.php](example_product.php)** - CRUD completo
2. **[example_upload.php](example_upload.php)** - Upload de arquivos
3. **[example_blog.php](example_blog.php)** - Sistema mais complexo

### 3️⃣ Avançado
1. **[example_streaming.php](example_streaming.php)** - Real-time
2. **[example_admin.php](example_admin.php)** - Sistema administrativo
3. **[example_complete.php](example_complete.php)** - Tudo junto

## 🔧 Configuração de Ambiente

### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Docker (Opcional)
```dockerfile
FROM php:8.1-apache
COPY . /var/www/html/
RUN a2enmod rewrite
```

## 🧪 Testando os Exemplos

### Com curl
```bash
# Testar endpoint GET
curl http://localhost:8000/api/users

# Testar endpoint POST
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"João","email":"joao@email.com"}'

# Testar com autenticação
curl -X GET http://localhost:8000/api/profile \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

### Com navegador
- **GET requests**: Acesse diretamente a URL
- **Interfaces web**: Alguns exemplos incluem formulários HTML
- **Documentação**: Acesse `/docs` quando disponível

## 📝 Personalizando os Exemplos

### 1. Copie um exemplo
```bash
cp example_user.php meu_exemplo.php
```

### 2. Modifique conforme necessário
```php
// Altere as rotas, adicione novos endpoints, etc.
$app->get('/minha-rota', function($req, $res) {
    $res->json(['message' => 'Minha funcionalidade!']);
});
```

### 3. Execute seu exemplo
```bash
php -S localhost:8001 meu_exemplo.php
```

## 🆘 Problemas Comuns

### Erro: "Class not found"
```bash
# Certifique-se de que o autoload está correto
composer dump-autoload
```

### Erro: "Port already in use"
```bash
# Use uma porta diferente
php -S localhost:8001 example_user.php
```

### Erro 404 em sub-rotas
- Verifique a configuração do servidor web (.htaccess ou nginx.conf)
- Teste primeiro com o servidor PHP built-in

## 📚 Documentação Adicional

- **[Documentação Completa](../docs/pt-br/README.md)**
- **[Guia de Autenticação](../docs/pt-br/AUTH_MIDDLEWARE.md)**
- **[Referência de API](../docs/pt-br/objetos.md)**
- **[Middlewares de Segurança](../docs/guides/SECURITY_IMPLEMENTATION.md)**

## 🤝 Contribuindo com Exemplos

Quer adicionar um novo exemplo? Ótimo!

1. Crie um arquivo `example_meu_recurso.php`
2. Documente bem o código com comentários
3. Adicione na tabela acima
4. Faça um pull request

### Template para novos exemplos
```php
<?php
/**
 * Exemplo: [Nome do Recurso]
 *
 * Este exemplo demonstra como [funcionalidade].
 *
 * Recursos demonstrados:
 * - [Recurso 1]
 * - [Recurso 2]
 *
 * Para testar:
 * php -S localhost:8000 example_meu_recurso.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Express\ApiExpress;

$app = new ApiExpress();

// Sua implementação aqui...

$app->run();
```

---

**🚀 Comece agora:** Escolha um exemplo da lista acima e execute com `php -S localhost:8000 nome_do_exemplo.php`!
