# Exemplos PivotPHP

Esta pasta contém exemplos práticos e funcionais do PivotPHP Framework. Cada exemplo demonstra diferentes recursos e funcionalidades do framework.

## 📂 Lista de Exemplos

### 🌟 **Exemplos Principais**

- **[⭐ example_basic.php](example_basic.php)** - API REST básica e conceitos fundamentais
- **[🔐 example_auth.php](example_auth.php)** - Sistema completo de autenticação multi-método
- **[🔑 example_auth_simple.php](example_auth_simple.php)** - JWT básico e controle de acesso
- **[🛡️ example_middleware.php](example_middleware.php)** - CORS, rate limiting e validação
- **[� example_standard_middlewares.php](example_standard_middlewares.php)** - Demonstração dos middlewares padrão inclusos
- **[�📚 example_openapi_docs.php](example_openapi_docs.php)** - Documentação OpenAPI/Swagger automática
- **[🚀 example_complete_optimizations.php](example_complete_optimizations.php)** - App completo com otimizações

### 🎯 **Para Começar**

1. **Novo no PivotPHP?** → Comece com `example_basic.php`
2. **Precisa de autenticação?** → Veja `example_auth_simple.php`
3. **Quer usar middlewares padrão?** → Execute `example_standard_middlewares.php`
4. **Quer documentação automática?** → Execute `example_openapi_docs.php`
5. **Middlewares personalizados?** → Explore `example_middleware.php`

## 🚀 Como Executar

### Usando o servidor PHP embutido:

```bash
# Executar um exemplo específico
php -S localhost:8080 example_basic.php

# Ou usar o script start-server.sh
./start-server.sh example_basic.php
```

### Usando o script de inicialização:

```bash
# Dar permissão de execução (primeira vez)
chmod +x start-server.sh

# Executar servidor na porta 8080
./start-server.sh

# Executar em porta específica
./start-server.sh example_basic.php 3000
```

## 📖 Detalhes dos Exemplos

### 🌟 example_basic.php
- **Objetivo**: Introdução aos conceitos básicos
- **Recursos**: Rotas GET/POST, JSON responses, parâmetros
- **Ideal para**: Iniciantes e primeiros passos

### 🔐 example_auth.php
- **Objetivo**: Sistema completo de autenticação
- **Recursos**: JWT, Basic Auth, API Key, múltiplos métodos
- **Ideal para**: Apps que precisam de autenticação robusta

### 🔑 example_auth_simple.php
- **Objetivo**: Autenticação JWT simplificada
- **Recursos**: Login, proteção de rotas, validação de token
- **Ideal para**: Implementação rápida de auth

### 🛡️ example_middleware.php
- **Objetivo**: Demonstrar sistema de middlewares
- **Recursos**: CORS, rate limiting, validação, logging
- **Ideal para**: Entender pipeline de middlewares

### � example_standard_middlewares.php
- **Objetivo**: Demonstrar middlewares padrão inclusos
- **Recursos**: SecurityMiddleware, CorsMiddleware, AuthMiddleware, CsrfMiddleware, RateLimitMiddleware
- **Ideal para**: Conhecer todos os middlewares disponíveis no framework

### �📚 example_openapi_docs.php
- **Objetivo**: Documentação automática da API
- **Recursos**: OpenAPI 3.0, Swagger UI, metadados de rotas
- **Ideal para**: APIs que precisam de documentação
- **Acesso**: `http://localhost:8080/docs` (interface Swagger UI)

### 🚀 example_complete_optimizations.php
- **Objetivo**: Aplicação otimizada para produção
- **Recursos**: Cache, grupos de rotas, performance
- **Ideal para**: Deploy em produção

## 🔧 Estrutura de Arquivos

```
examples/
├── README.md                           # Este arquivo
├── start-server.sh                     # Script para iniciar servidor
├── example_basic.php                   # Exemplo básico
├── example_auth.php                    # Autenticação completa
├── example_auth_simple.php             # Auth simplificada
├── example_middleware.php              # Middlewares
├── example_standard_middlewares.php    # Middlewares padrão ✨
├── example_openapi_docs.php            # Documentação OpenAPI ✨
├── example_complete_optimizations.php  # App otimizado
└── snippets/                          # Trechos de código reutilizáveis
    ├── app_base.php                   # Base comum para apps
    └── ...
```

## 💡 Dicas

### 🎯 **Testando APIs**
```bash
# Testar endpoint básico
curl http://localhost:8080/api/users

# Testar com dados POST
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"João","email":"joao@test.com"}'
```

### 📚 **Acessando Documentação**
- Interface Swagger: `http://localhost:8080/docs`
- JSON OpenAPI: `http://localhost:8080/docs/openapi.json`

### 🔐 **Testando Autenticação**
```bash
# Login (exemplo)
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"123456"}'

# Usar token recebido
curl http://localhost:8080/protected \
  -H "Authorization: Bearer SEU_JWT_TOKEN"
```

## 🌟 Próximos Passos

1. **Execute os exemplos** na ordem sugerida
2. **Leia os comentários** no código para entender cada funcionalidade
3. **Modifique e experimente** com diferentes configurações
4. **Consulte a documentação** em `docs/` para funcionalidades avançadas
5. **Crie sua primeira API** usando os exemplos como base

---

**📋 Mais informações:** [Documentação Completa](../docs/) | [Guia Rápido](../docs/guides/QUICK_START_GUIDE.md)
