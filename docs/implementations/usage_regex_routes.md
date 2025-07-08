# Implementação: Rotas com Regex e Constraints

Este guia demonstra como usar o sistema de regex e constraints do PivotPHP para criar rotas com validação avançada.

## Visão Geral

O PivotPHP oferece três formas de validar parâmetros em rotas:
1. **Shortcuts** - Atalhos predefinidos para padrões comuns
2. **Constraints Customizadas** - Regex personalizado em parâmetros
3. **Blocos Regex Completos** - Controle total sobre partes da rota

## Exemplo Completo: API de Blog

```php
<?php
require_once '../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Factory\ServerRequestFactory;
use PivotPHP\Core\Http\Factory\ResponseFactory;

$app = new Application();

// ===================================
// 1. ROTAS COM SHORTCUTS
// ===================================

// Usar shortcut 'int' para IDs numéricos
$app->get('/posts/:id<int>', function($req, $res) {
    $id = $req->param('id'); // Garantido ser numérico
    
    return $res->json([
        'post_id' => $id,
        'title' => "Post #{$id}"
    ]);
});

// Usar shortcut 'slug' para URLs amigáveis
$app->get('/categories/:slug<slug>', function($req, $res) {
    $slug = $req->param('slug'); // Formato: minha-categoria
    
    return $res->json([
        'category' => $slug,
        'url' => "/categories/{$slug}"
    ]);
});

// Usar shortcut 'uuid' para identificadores únicos
$app->get('/users/:uuid<uuid>', function($req, $res) {
    $uuid = $req->param('uuid'); // Formato UUID válido
    
    return $res->json([
        'user_uuid' => $uuid,
        'type' => 'user'
    ]);
});

// ===================================
// 2. CONSTRAINTS CUSTOMIZADAS
// ===================================

// Validar formato de data específico
$app->get('/archive/:year<\d{4}>/:month<\d{2}>/:day<\d{2}>', function($req, $res) {
    return $res->json([
        'date' => sprintf('%s-%s-%s', 
            $req->param('year'),
            $req->param('month'),
            $req->param('day')
        )
    ]);
});

// Validar código de produto personalizado
$app->get('/products/:sku<[A-Z]{3}-\d{4}-[A-Z]>', function($req, $res) {
    // Aceita: ABC-1234-X
    $sku = $req->param('sku');
    
    return $res->json([
        'product_sku' => $sku,
        'valid' => true
    ]);
});

// Validar tags com caracteres específicos
$app->get('/tags/:tag<[a-z0-9_\-]{3,20}>', function($req, $res) {
    // Tags de 3 a 20 caracteres, lowercase, números, _ e -
    $tag = $req->param('tag');
    
    return $res->json([
        'tag' => $tag,
        'url' => "/tags/{$tag}"
    ]);
});

// ===================================
// 3. BLOCOS REGEX COMPLETOS
// ===================================

// Versionamento de API com regex
$app->group('/api/{^v(\d+)$}', function() use ($app) {
    
    $app->get('/users', function($req, $res) {
        // A versão é capturada automaticamente
        preg_match('#/api/v(\d+)/#', $req->getUri()->getPath(), $matches);
        $version = $matches[1] ?? '1';
        
        return $res->json([
            'api_version' => $version,
            'endpoint' => 'users',
            'data' => []
        ]);
    });
    
    $app->get('/posts/:id<int>', function($req, $res) {
        preg_match('#/api/v(\d+)/#', $req->getUri()->getPath(), $matches);
        $version = $matches[1] ?? '1';
        
        return $res->json([
            'api_version' => $version,
            'post_id' => $req->param('id')
        ]);
    });
});

// Arquivos com extensões específicas
$app->get('/download/{^(.+)\.(pdf|doc|docx|txt)$}', function($req, $res) {
    $path = $req->getUri()->getPath();
    preg_match('#/download/(.+)\.(pdf|doc|docx|txt)$#', $path, $matches);
    
    $filename = $matches[1] ?? 'file';
    $extension = $matches[2] ?? 'txt';
    
    return $res->json([
        'filename' => $filename,
        'extension' => $extension,
        'full_name' => "{$filename}.{$extension}"
    ]);
});

// Estrutura de diretórios complexa
$app->get('/browse/{^([\w\-]+)/([\w\-]+)/(.+\.js)$}', function($req, $res) {
    $path = $req->getUri()->getPath();
    preg_match('#/browse/([\w\-]+)/([\w\-]+)/(.+\.js)$#', $path, $matches);
    
    return $res->json([
        'module' => $matches[1] ?? '',
        'component' => $matches[2] ?? '',
        'file' => $matches[3] ?? '',
        'type' => 'javascript'
    ]);
});

// ===================================
// 4. COMBINAÇÕES AVANÇADAS
// ===================================

// Mix de syntaxes
$app->get('/media/{^(images|videos)$}/:year<\d{4}>/:filename<[a-z0-9\-]+>/{^\.(jpg|mp4)$}', 
    function($req, $res) {
        $path = $req->getUri()->getPath();
        preg_match('#/media/(images|videos)/\d{4}/[a-z0-9\-]+\.(jpg|mp4)$#', $path, $matches);
        
        $type = $matches[1] ?? '';
        $extension = $matches[2] ?? '';
        
        return $res->json([
            'type' => $type,
            'year' => $req->param('year'),
            'filename' => $req->param('filename'),
            'extension' => $extension,
            'mime_type' => $type === 'images' ? 'image/jpeg' : 'video/mp4'
        ]);
    }
);

// Validação de email básica na rota
$app->post('/subscribe/:email<[^@]+@[^@]+\.[^@]+>', function($req, $res) {
    $email = $req->param('email');
    
    // Validação adicional no handler
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $res->status(400)->json([
            'error' => 'Invalid email format'
        ]);
    }
    
    return $res->json([
        'subscribed' => true,
        'email' => $email
    ]);
});

// ===================================
// 5. CASOS DE USO PRÁTICOS
// ===================================

// Sistema de permalinks
$app->get('/:year<\d{4}>/:month<\d{2}>/:slug<[a-z0-9\-]+>', function($req, $res) {
    return $res->json([
        'type' => 'blog_post',
        'permalink' => sprintf('/%s/%s/%s',
            $req->param('year'),
            $req->param('month'),
            $req->param('slug')
        )
    ]);
});

// API com múltiplos formatos
$app->get('/export/:format<json|xml|csv>/:resource<[a-z]+>/:id<int>', function($req, $res) {
    $format = $req->param('format');
    $resource = $req->param('resource');
    $id = $req->param('id');
    
    switch($format) {
        case 'json':
            return $res->json(['data' => []]);
        case 'xml':
            return $res->header('Content-Type', 'application/xml')
                      ->body('<data></data>');
        case 'csv':
            return $res->header('Content-Type', 'text/csv')
                      ->body('id,name\n1,test');
    }
});

// Webhook com validação de token
$app->post('/webhook/:service<github|gitlab|bitbucket>/:token<[a-f0-9]{40}>', 
    function($req, $res) {
        $service = $req->param('service');
        $token = $req->param('token');
        
        return $res->json([
            'webhook' => $service,
            'token_valid' => true,
            'processed' => true
        ]);
    }
);

// ===================================
// 6. TRATAMENTO DE ERROS
// ===================================

// Middleware para rotas não encontradas
$app->use(function($req, $handler) {
    $response = $handler->handle($req);
    
    if ($response->getStatusCode() === 404) {
        $response = (new ResponseFactory())->createResponse(404);
        $response->getBody()->write(json_encode([
            'error' => 'Route not found',
            'path' => $req->getUri()->getPath(),
            'method' => $req->getMethod(),
            'hint' => 'Check if the URL matches the expected pattern'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    return $response;
});

// ===================================
// EXECUTAR APLICAÇÃO
// ===================================

$app->run();
```

## Testando as Rotas

### Com Shortcuts

```bash
# ✅ Válido
curl http://localhost:8000/posts/123
curl http://localhost:8000/categories/meu-artigo-legal
curl http://localhost:8000/users/550e8400-e29b-41d4-a716-446655440000

# ❌ Inválido
curl http://localhost:8000/posts/abc  # Espera número
curl http://localhost:8000/categories/Meu_Artigo  # Maiúsculas não permitidas
```

### Com Constraints Customizadas

```bash
# ✅ Válido
curl http://localhost:8000/archive/2025/07/08
curl http://localhost:8000/products/ABC-1234-X
curl http://localhost:8000/tags/php-framework

# ❌ Inválido
curl http://localhost:8000/archive/25/7/8  # Formato incorreto
curl http://localhost:8000/products/abc-1234-x  # Minúsculas
```

### Com Blocos Regex

```bash
# ✅ Válido
curl http://localhost:8000/api/v1/users
curl http://localhost:8000/api/v2/posts/123
curl http://localhost:8000/download/relatorio.pdf
curl http://localhost:8000/browse/admin/users/controller.js

# ❌ Inválido
curl http://localhost:8000/api/version1/users  # Formato incorreto
curl http://localhost:8000/download/arquivo.exe  # Extensão não permitida
```

## Melhores Práticas

### 1. Performance

```php
// ❌ Evite regex muito complexo
$app->get('/:email<^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$>', ...);

// ✅ Use validação simples na rota, completa no handler
$app->get('/:email<[^@]+@[^@]+>', function($req, $res) {
    $email = $req->param('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $res->status(400)->json(['error' => 'Invalid email']);
    }
    // ...
});
```

### 2. Legibilidade

```php
// ❌ Difícil de entender
$app->get('/:code<[A-Z]{2}\d{4}[A-Z]\d{2}>', ...);

// ✅ Use comentários ou constantes
const PRODUCT_CODE_PATTERN = '[A-Z]{2}\d{4}[A-Z]\d{2}'; // Ex: AB1234C56
$app->get('/:code<' . PRODUCT_CODE_PATTERN . '>', ...);
```

### 3. Reutilização

```php
// Defina padrões comuns como constantes
class RoutePatterns {
    const UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';
    const SLUG = '[a-z0-9\-]+';
    const DATE = '\d{4}-\d{2}-\d{2}';
}

// Use em múltiplas rotas
$app->get('/users/:id<' . RoutePatterns::UUID . '>', ...);
$app->get('/posts/:id<' . RoutePatterns::UUID . '>', ...);
```

## Conclusão

O sistema de regex do PivotPHP oferece flexibilidade total para validação de rotas:

- Use **shortcuts** para padrões comuns (int, slug, uuid, etc.)
- Use **constraints** para validações específicas
- Use **blocos regex** para controle total sobre partes da URL
- Combine diferentes abordagens conforme necessário
- Mantenha o regex simples e documente padrões complexos

Para mais informações, consulte a [documentação completa do Router](../technical/routing/router.md).