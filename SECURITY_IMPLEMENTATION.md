# Middlewares de Segurança - Express PHP

## ✅ Implementação Concluída

Foram implementados com sucesso os seguintes middlewares de segurança para o framework Express PHP:

### 🛡️ 1. CsrfMiddleware
**Arquivo:** `SRC/Services/CsrfMiddleware.php`

**Funcionalidades:**
- ✅ Proteção contra ataques CSRF (Cross-Site Request Forgery)
- ✅ Geração automática de tokens seguros
- ✅ Validação de tokens via headers ou body
- ✅ Configuração de caminhos excluídos
- ✅ Métodos utilitários para formulários HTML
- ✅ Suporte a requisições AJAX

**Métodos principais:**
- `getToken()` - Obtém o token CSRF atual
- `hiddenField()` - Gera campo hidden HTML
- `metaTag()` - Gera meta tag HTML para AJAX

### 🔒 2. XssMiddleware  
**Arquivo:** `SRC/Services/XssMiddleware.php`

**Funcionalidades:**
- ✅ Proteção contra ataques XSS (Cross-Site Scripting)
- ✅ Sanitização automática de dados de entrada
- ✅ Cabeçalhos de segurança automáticos
- ✅ Detecção de conteúdo malicioso
- ✅ Tags HTML permitidas configuráveis
- ✅ Limpeza de URLs maliciosas

**Métodos principais:**
- `sanitize()` - Sanitiza strings individuais
- `containsXss()` - Verifica se contém XSS
- `cleanUrl()` - Limpa URLs maliciosas

### 🛡️🔒 3. SecurityMiddleware
**Arquivo:** `SRC/Services/SecurityMiddleware.php`

**Funcionalidades:**
- ✅ Middleware combinado (CSRF + XSS)
- ✅ Rate limiting básico
- ✅ Configuração segura de sessão
- ✅ Múltiplas configurações predefinidas
- ✅ Configuração personalizada flexível

**Métodos principais:**
- `create()` - Configuração básica
- `strict()` - Configuração rigorosa
- `csrfOnly()` - Apenas CSRF
- `xssOnly()` - Apenas XSS

## 📚 Documentação e Exemplos

### Arquivos de Documentação:
- ✅ `README.md` - Documentação principal atualizada
- ✅ `docs/objetos.md` - Documentação de objetos atualizada

### Exemplos Práticos:
- ✅ `examples/exemplo_seguranca.php` - Exemplo completo de uso
- ✅ `examples/snippets/utils_csrf.php` - Snippets CSRF
- ✅ `examples/snippets/utils_xss.php` - Snippets XSS  
- ✅ `examples/snippets/utils_seguranca.php` - Configurações

### Testes:
- ✅ `test/teste_seguranca.php` - Teste completo dos middlewares

## 🔧 Cabeçalhos de Segurança Implementados

Os middlewares adicionam automaticamente os seguintes cabeçalhos:

```
X-XSS-Protection: 1; mode=block
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: [configurável]
```

## 🚀 Como Usar

### Uso Básico (Recomendado):
```php
use Express\SRC\Services\SecurityMiddleware;

$app = new ApiExpress();
$app->use(SecurityMiddleware::create());
```

### Uso Estrito (Máxima Segurança):
```php
$app->use(SecurityMiddleware::strict());
```

### Uso Individual:
```php
use Express\SRC\Services\CsrfMiddleware;
use Express\SRC\Services\XssMiddleware;

$app->use(new XssMiddleware());
$app->use(new CsrfMiddleware());
```

## ✨ Recursos Avançados

### 🎯 Configuração Personalizada:
```php
$app->use(new SecurityMiddleware([
    'enableCsrf' => true,
    'enableXss' => true,
    'rateLimiting' => false,
    'csrf' => [
        'excludePaths' => ['/api/webhook'],
        'generateTokenResponse' => true
    ],
    'xss' => [
        'excludeFields' => ['content'],
        'allowedTags' => '<p><strong><em>'
    ]
]));
```

### 🔄 Integração com Formulários:
```php
// No template PHP
echo CsrfMiddleware::metaTag();
echo CsrfMiddleware::hiddenField();

// Em JavaScript
const token = document.querySelector('meta[name="csrf-token"]').content;
fetch('/api/endpoint', {
    headers: { 'X-CSRF-Token': token }
});
```

## 🧪 Testes Realizados

✅ Geração e validação de tokens CSRF  
✅ Sanitização de dados XSS  
✅ Detecção de conteúdo malicioso  
✅ Configuração de middlewares  
✅ Simulação de requisições  
✅ Cabeçalhos de segurança  
✅ Configuração de sessão segura  

## 📋 Checklist de Segurança

- [x] Proteção CSRF implementada
- [x] Proteção XSS implementada  
- [x] Cabeçalhos de segurança configurados
- [x] Sanitização de entrada automática
- [x] Configuração de sessão segura
- [x] Rate limiting básico
- [x] Documentação completa
- [x] Exemplos práticos
- [x] Testes funcionais

## 🎉 Status: IMPLEMENTAÇÃO COMPLETA

Todos os middlewares de segurança foram implementados com sucesso e estão prontos para uso em produção. A documentação foi atualizada e exemplos práticos foram criados para facilitar a adoção.
