# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [1.0.0] - 2025-06-26

### ✨ Adicionado

#### 🔐 Sistema de Autenticação Multi-método
- **JWT Authentication**: Suporte completo a JSON Web Tokens
- **Basic Authentication**: Autenticação HTTP Basic
- **API Key Authentication**: Suporte a chaves de API
- **Bearer Token**: Suporte a Bearer tokens
- **Auto-detecção**: Sistema inteligente que detecta automaticamente o método de auth
- **AuthMiddleware**: Middleware unificado para todos os métodos de autenticação

#### 🛡️ Middlewares de Segurança Avançados
- **SecurityMiddleware**: Middleware de segurança tudo-em-um
- **CsrfMiddleware**: Proteção contra Cross-Site Request Forgery
- **XssMiddleware**: Proteção e sanitização contra Cross-Site Scripting
- **SecurityMiddleware**: Headers de segurança automáticos
- **RateLimitMiddleware**: Limitação de taxa de requisições
- **CorsMiddleware**: Configuração flexível de CORS

#### 🎯 Middlewares Core
- **ErrorHandlerMiddleware**: Tratamento centralizado de erros
- **RequestValidationMiddleware**: Validação de requisições
- **OpenApiDocsMiddleware**: Geração automática de documentação

#### 📚 Documentação OpenAPI/Swagger
- **Geração automática** de documentação a partir das rotas
- **Interface interativa** Swagger UI
- **Múltiplos servers**: Local, produção, staging
- **Agrupamento por tags**: Organização por contexto
- **Exemplos práticos**: Requests e responses de exemplo
- **Respostas globais**: Documentação automática de códigos 400, 401, 404, 500

#### 🎯 Exemplos Modulares de Aprendizado
- **example_user.php**: Sistema de usuários e autenticação
- **example_product.php**: CRUD completo de produtos
- **example_upload.php**: Upload e manipulação de arquivos
- **example_admin.php**: Área administrativa protegida
- **example_blog.php**: Sistema de blog com categorias
- **example_security.php**: Demonstração de todos os middlewares
- **example_complete.php**: Integração completa de recursos
- **snippets/**: Sub-routers reutilizáveis

#### 🔧 Sistema de Roteamento Avançado
- **Rotas dinâmicas**: Suporte a parâmetros (ex: `/users/:id`)
- **Agrupamento de rotas**: Prefixos e middlewares por grupo
- **Middlewares por rota**: Aplicação seletiva de middlewares
- **Metadados de rota**: Suporte a documentação inline
- **Múltiplos métodos HTTP**: GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD

#### 🚀 Performance e Qualidade
- **PHP 7.4+**: Compatibilidade ampla
- **PHPStan Level 8**: Máxima análise estática
- **PSR-12**: Code style padronizado
- **186 testes unitários**: Cobertura abrangente
- **Zero dependências obrigatórias**: Framework independente
- **CI/CD completo**: GitHub Actions automatizado

#### 📖 Documentação Completa
- **Guia de início rápido**: Tutorial passo a passo
- **Documentação em português**: Guias completos
- **Exemplos práticos**: Código pronto para usar
- **Guias de desenvolvimento**: Para contribuidores
- **Documentação de API**: Referência completa

### 🎯 Recursos Principais

#### Facilidade de Uso
```php
$app = new ApiExpress();
$app->get('/', fn($req, $res) => $res->json(['hello' => 'world']));
$app->run();
```

#### Segurança por Padrão
```php
$app->use(SecurityMiddleware::create()); // Segurança completa em uma linha
```

#### Autenticação Simples
```php
$app->use(AuthMiddleware::jwt('sua_chave')); // JWT em uma linha
```

#### Documentação Automática
```php
$app->use('/docs', new OpenApiDocsMiddleware()); // Swagger UI automático
```

### 🏆 Qualidade e Padrões

- **✅ 186 testes unitários** - Cobertura abrangente
- **✅ PHPStan Level 8** - Máxima análise estática
- **✅ PSR-12 compliant** - Code style padronizado
- **✅ PHP 7.4+ compatível** - Ampla compatibilidade
- **✅ Zero breaking changes** - API estável
- **✅ Documentação completa** - Guias e exemplos
- **✅ CI/CD automatizado** - GitHub Actions

### 🔒 Segurança

- Proteção CSRF nativa
- Sanitização XSS automática
- Headers de segurança padrão
- Rate limiting configurável
- Validação de entrada robusta
- Sistema de autenticação multi-camadas

### 📦 Instalação

```bash
composer require cafernandes/express-php
```

### 🎉 Primeiros Passos

1. **[Guia de Início Rápido](docs/guides/starter/README.md)** - Tutorial completo
2. **[Documentação](docs/README.md)** - Documentação detalhada
3. **[Exemplos](examples/)** - Código pronto para usar

---

**Express PHP v1.0.0** - Um microframework PHP moderno, seguro e completo! 🚀
