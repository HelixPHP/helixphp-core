# 📚 Índice da Documentação PivotPHP

Bem-vindo ao guia completo do PivotPHP! Esta documentação foi criada para ser um guia prático para quem quer usar o framework na prática.

## 🚀 Para Começar

### 📖 Implementação Rápida
- [**API Básica**](implementions/usage_basic.md) - Sua primeira API em 5 minutos
- [**API com Middlewares**](implementions/usage_with_middleware.md) - Usando segurança, CORS e autenticação
- [**Middleware Customizado**](implementions/usage_with_custom_middleware.md) - Criando suas próprias extensões

## 🔧 Referência Técnica

### 📡 Core da Aplicação
- [**Application**](techinical/application.md) - O coração do framework
- [**Request**](techinical/http/request.md) - Manipulando requisições HTTP
- [**Response**](techinical/http/response.md) - Criando respostas poderosas
- [**Router**](techinical/routing/router.md) - Sistema de roteamento avançado
- [**OpenAPI/Swagger**](techinical/http/openapi_documentation.md) - Documentação automática da API

### 🛡️ Segurança e Middlewares
- [**Visão Geral**](techinical/middleware/README.md) - Todos os middlewares disponíveis
- [**SecurityMiddleware**](techinical/middleware/SecurityMiddleware.md) - Proteção XSS, CSRF, Headers
- [**CorsMiddleware**](techinical/middleware/CorsMiddleware.md) - Cross-Origin Resource Sharing
- [**AuthMiddleware**](techinical/middleware/AuthMiddleware.md) - JWT, Basic, Bearer, API Key
- [**RateLimitMiddleware**](techinical/middleware/RateLimitMiddleware.md) - Controle de taxa
- [**ValidationMiddleware**](techinical/middleware/ValidationMiddleware.md) - Validação de dados
- [**Middleware Customizado**](techinical/middleware/CustomMiddleware.md) - Crie o seu próprio

### 🔐 Autenticação
- [**Uso Nativo**](techinical/authentication/usage_native.md) - JWT, Basic, Bearer prontos para usar
- [**Autenticação Customizada**](techinical/authentication/usage_custom.md) - Implemente seu próprio sistema

### ⚠️ Tratamento de Erros
- [**Sistema de Erros**](techinical/exceptions/ErrorHandling.md) - Como o framework trata erros
- [**Exceptions Personalizadas**](techinical/exceptions/CustomExceptions.md) - Crie suas próprias exceções

### 🧩 Extensibilidade
- [**Providers**](techinical/providers/usage.md) - Injeção de dependências
- [**Criando Extensões**](techinical/providers/extension.md) - Desenvolva plugins
- [**Sistema de Extensões**](techinical/extesions/README.md) - Arquitetura de plugins

## ⚡ Performance

### 📊 Monitoramento
- [**PerformanceMonitor**](performance/PerformanceMonitor.md) - Monitore sua aplicação
- [**Benchmarks**](performance/benchmarks/README.md) - Resultados e otimizações

## 📋 Releases e Versões

### 🚀 Histórico de Versões
- [**Documentação de Releases**](releases/README.md) - Índice completo de versões
- [**v1.0.0 (Atual)**](releases/FRAMEWORK_OVERVIEW_v1.0.0.md) - PHP 8.4 compatibility fixes
- [**v1.0.0**](releases/FRAMEWORK_OVERVIEW_v1.0.0.md) - PHP 8.4.8 + JIT optimizations
- [**v1.0.0**](releases/FRAMEWORK_OVERVIEW_v1.0.0.md) - Advanced ML optimizations
- [**v1.0.0**](releases/FRAMEWORK_OVERVIEW_v1.0.0.md) - Core rewrite and PSR compliance

## 🧪 Testes

### 📝 Guias de Teste
- [**Testando sua API**](testing/api_testing.md) - Como testar endpoints
- [**Testando Middlewares**](testing/middleware_testing.md) - Testes de middleware
- [**Mocks e Stubs**](testing/mocks_and_stubs.md) - Simulando dependências
- [**Testes de Integração**](testing/integration_testing.md) - Testando o fluxo completo

## 🤝 Contribuindo

### 💡 Como Ajudar
- [**Guia de Contribuição**](contributing/README.md) - Como contribuir com o projeto

---

## 🎯 Fluxo de Aprendizado Recomendado

### 👶 Iniciante
1. [API Básica](implementions/usage_basic.md)
2. [Application](techinical/application.md)
3. [Request](techinical/http/request.md) [Response](techinical/http/response.md)

### 🚀 Intermediário
1. [API com Middlewares](implementions/usage_with_middleware.md)
2. [Autenticação](techinical/authentication/usage_native.md)
3. [Testando sua API](testing/api_testing.md)

### 🔥 Avançado
1. [Middleware Customizado](implementions/usage_with_custom_middleware.md)
2. [Criando Extensões](techinical/providers/extension.md)
3. [Performance](performance/PerformanceMonitor.md)
4. [Releases e Versões](releases/README.md)

---

*📖 Documentação atualizada em: 6 de julho de 2025*
