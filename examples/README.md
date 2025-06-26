# 📚 Exemplos do Express PHP

Esta pasta contém exemplos práticos e funcionais do framework Express PHP. Todos os exemplos foram testados e são totalmente funcionais.

## 🚀 Exemplos Principais

### 1. **example_basic.php** - Exemplo Básico ⭐
Demonstra o uso básico do framework para criar uma API REST simples.

**Funcionalidades:**
- Rotas básicas (GET, POST, PUT, DELETE)
- API REST para gerenciamento de usuários
- Middleware de logging
- Validação simples

**Como executar:**
```bash
php -S localhost:8000 examples/example_basic.php
```

**Endpoints:**
- `GET /` - Página inicial
- `GET /test` - Teste da API
- `GET /api/users` - Listar usuários
- `POST /api/users` - Criar usuário
- `PUT /api/users/:id` - Atualizar usuário
- `DELETE /api/users/:id` - Remover usuário

### 2. **example_auth_simple.php** - Autenticação JWT 🔐
Demonstra implementação de autenticação usando JWT de forma simples.

**Funcionalidades:**
- Sistema de login com JWT
- Rotas protegidas
- Middleware de autenticação
- Controle de roles (admin/user)
- CORS básico

**Como executar:**
```bash
php -S localhost:8000 examples/example_auth_simple.php
```

**Credenciais de teste:**
- `admin@example.com` : `123456` (admin)
- `user@example.com` : `123456` (user)

**Endpoints:**
- `POST /auth/login` - Fazer login
- `GET /auth/me` - Dados do usuário (requer token)
- `GET /protected` - Rota protegida (requer token)
- `GET /admin/dashboard` - Apenas admins (requer token)

### 3. **example_middleware.php** - API com Middlewares 🛡️
Demonstra uso avançado de middlewares para funcionalidades como CORS, rate limiting e validação.

**Funcionalidades:**
- CORS completo
- Rate limiting (30 req/min)
- Logging detalhado
- Validação automática de JSON
- API de produtos com filtros

**Como executar:**
```bash
php -S localhost:8000 examples/example_middleware.php
```

**Endpoints:**
- `GET /api/products` - Listar produtos
- `GET /api/products?category=electronics` - Filtrar produtos
- `POST /api/products` - Criar produto
- `PUT /api/products/:id` - Atualizar produto
- `DELETE /api/products/:id` - Remover produto
- `GET /test/rate-limit` - Testar rate limiting

### 4. **app.php** - Exemplo Completo 🚀
Aplicação completa com todas as funcionalidades do framework.

**Como executar:**
```bash
php -S localhost:8000 examples/app.php
```

## 🧩 Snippets Úteis

A pasta `snippets/` contém trechos de código reutilizáveis:

- `auth_snippets.php` - Funções de autenticação
- `utils_cors.php` - Utilitários para CORS
- `utils_sanitizacao.php` - Funções de sanitização
- `utils_log.php` - Sistema de logging
- E muito mais...

## 🚀 Como Usar

### Instalação
```bash
composer install
```

### Executar Exemplo
```bash
# Escolha um exemplo
php -S localhost:8000 examples/example_basic.php

# Ou use o script de inicialização
./examples/start-server.sh
```

### Testar Endpoints
```bash
# Página inicial
curl http://localhost:8000/

# API REST
curl http://localhost:8000/api/users

# Criar usuário
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"João Silva","email":"joao@example.com"}'

# Autenticação
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"123456"}'
```

## 📁 Estrutura dos Exemplos

```
examples/
├── example_basic.php       # ⭐ Exemplo básico - COMECE AQUI
├── example_auth_simple.php # 🔐 Autenticação JWT simples
├── example_middleware.php  # 🛡️ Middlewares avançados
├── app.php                # 🚀 Aplicação completa
├── README.md              # 📚 Esta documentação
├── snippets/              # 🧩 Trechos reutilizáveis
└── start-server.sh        # 🎬 Script de inicialização
```

## 💡 Dicas

1. **Começe pelo `example_basic.php`** - É o mais simples e didático
2. **Use os snippets** - Reaproveite código das snippets em seus projetos
3. **Teste todos os endpoints** - Use curl ou Postman para testar
4. **Veja os logs** - Os exemplos incluem logging para debug
5. **Personalize** - Use os exemplos como base para seu projeto

## 🔗 Links Úteis

- [Documentação Completa](../docs/README.md)
- [Guia de Middleware](../docs/pt-br/AUTH_MIDDLEWARE.md)
- [API Reference](../docs/pt-br/objetos.md)
- [Exemplos Avançados](../docs/guides/starter/)

## 🆘 Problemas Comuns

### Erro 404 nas rotas
Certifique-se de que o servidor built-in do PHP está sendo usado:
```bash
php -S localhost:8000 examples/example_basic.php
```

### JWT não funciona
Verifique se a biblioteca JWT está instalada:
```bash
composer install
```

### Problemas de CORS
Use o `example_middleware.php` que inclui CORS completo.

---

**💪 Todos os exemplos são funcionais e testados!**
