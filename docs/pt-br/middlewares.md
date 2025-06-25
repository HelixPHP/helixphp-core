# Middlewares - Express PHP

[![English](https://img.shields.io/badge/Language-English-blue)](../../SRC/Middlewares/README.md) [![Português](https://img.shields.io/badge/Language-Português-green)](middlewares.md)

Esta pasta contém todos os middlewares organizados por categoria para o framework Express PHP.

## 📁 Estrutura de Pastas

### 🔒 Security/
Middlewares relacionados à segurança da aplicação:

- **CsrfMiddleware.php** - Proteção contra ataques CSRF
- **XssMiddleware.php** - Proteção contra ataques XSS
- **SecurityMiddleware.php** - Middleware combinado de segurança

### ⚙️ Core/
Middlewares fundamentais do framework:

- **AttachmentMiddleware.php** - Gerenciamento de uploads de arquivos
- **CorsMiddleware.php** - Configuração de CORS
- **ErrorHandlerMiddleware.php** - Tratamento global de erros
- **OpenApiDocsMiddleware.php** - Documentação automática OpenAPI/Swagger
- **RateLimitMiddleware.php** - Limitação de taxa de requisições
- **RequestValidationMiddleware.php** - Validação de requisições

## 🚀 Como Usar

### Importação Individual

```php
// Middlewares de Segurança
use Express\SRC\Middlewares\Security\SecurityMiddleware;
use Express\SRC\Middlewares\Security\CsrfMiddleware;
use Express\SRC\Middlewares\Security\XssMiddleware;

// Middlewares Core
use Express\SRC\Middlewares\Core\CorsMiddleware;
use Express\SRC\Middlewares\Core\RateLimitMiddleware;
```

### Importação Completa

```php
// Importa todos os middlewares
require_once 'SRC/Middlewares/index.php';
```

### Aplicação nos Exemplos

```php
$app = new ApiExpress();

// Segurança completa
$app->use(SecurityMiddleware::create());

// CORS
$app->use(new CorsMiddleware());

// Rate limiting
$app->use(new RateLimitMiddleware());
```

## 🔄 Compatibilidade

Para manter compatibilidade com código existente, aliases são criados automaticamente:

- `Express\SRC\Services\SecurityMiddleware` → `Express\SRC\Middlewares\Security\SecurityMiddleware`
- `Express\SRC\Services\CsrfMiddleware` → `Express\SRC\Middlewares\Security\CsrfMiddleware`
- `Express\SRC\Services\XssMiddleware` → `Express\SRC\Middlewares\Security\XssMiddleware`
- E todos os outros middlewares...

## 📚 Documentação

Para documentação detalhada de cada middleware, consulte:

- [🇺🇸 README principal](../../README.md)
- [🇧🇷 README em português](README.md)
- [Documentação de objetos](objetos.md)
- [Exemplos práticos](../../examples/)

## 🆕 Novos Middlewares

Para adicionar novos middlewares:

1. **Segurança**: Coloque em `Security/` se relacionado à proteção/sanitização
2. **Core**: Coloque em `Core/` se for funcionalidade fundamental
3. **Categorias futuras**: Crie novas pastas conforme necessário

### Template para Novo Middleware

```php
<?php
namespace Express\SRC\Middlewares\[Categoria];

class NovoMiddleware
{
    private $options;

    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            // opções padrão
        ], $options);
    }

    public function __invoke($request, $response, $next)
    {
        // lógica do middleware
        $next();
    }
}
```

## 🧪 Testes

Execute os testes específicos:

```bash
php test/security_test.php  # Middlewares de segurança
```

## 📋 Checklist de Migração

Se você está migrando código existente:

- [ ] Atualize imports de `Express\SRC\Services\*` para `Express\SRC\Middlewares\[Categoria]\*`
- [ ] Verifique se os aliases funcionam corretamente
- [ ] Execute testes para garantir funcionamento
- [ ] Atualize documentação se necessário

---

**Nota**: Esta reorganização mantém 100% de compatibilidade com código existente através de aliases automáticos.
