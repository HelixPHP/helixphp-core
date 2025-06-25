# Express PHP Microframework

## Novidade: Exemplos Modulares e Aprendizagem Guiada

A partir da versão 2025, o Express PHP traz uma coleção de exemplos modulares para facilitar o aprendizado e a especialização em cada recurso do framework. Veja a pasta `examples/`:

- `exemplo_user.php`: Rotas de usuário e autenticação.
- `exemplo_produto.php`: Rotas de produto, parâmetros e exemplos OpenAPI.
- `exemplo_upload.php`: Upload de arquivos com exemplos práticos.
- `exemplo_admin.php`: Rotas administrativas e autenticação.
- `exemplo_blog.php`: Rotas de blog.
- `exemplo_completo.php`: Integração de todos os recursos e documentação automática.

Cada exemplo utiliza sub-routers especializados, facilitando o estudo isolado de cada contexto. Os arquivos em `examples/snippets/` podem ser reutilizados em qualquer app Express PHP.

## Documentação Automática OpenAPI/Swagger

- **Agrupamento por tags**: Endpoints organizados por contexto (User, Produto, Upload, Admin, Blog) na interface Swagger.
- **Múltiplos servers**: Documentação já inclui ambientes local, produção e homologação.
- **Exemplos práticos**: Requests e responses de exemplo para facilitar testes e integração.
- **Respostas globais**: Todos os endpoints já documentam respostas 400, 401, 404 e 500.
- **BaseUrl dinâmica**: O campo `servers` é ajustado automaticamente conforme o ambiente.

Acesse `/docs/index` para a interface interativa.

## Como estudar cada recurso

- Para aprender sobre rotas de usuário: rode `php examples/exemplo_user.php`
- Para upload: `php examples/exemplo_upload.php`
- Para produto: `php examples/exemplo_produto.php`
- Para admin: `php examples/exemplo_admin.php`
- Para blog: `php examples/exemplo_blog.php`
- Para ver tudo integrado e a documentação automática: `php examples/exemplo_completo.php`

## Estrutura Recomendada para Projetos

- `examples/` ? Exemplos práticos e didáticos
- `examples/snippets/` ? Sub-routers prontos para reuso
- `SRC/` ? Framework e middlewares
- `test/` ? Testes e experimentos

## Dica

Você pode criar seu próprio app Express PHP copiando e adaptando qualquer exemplo da pasta `examples/`.

---

## Índice

1. [Introdução](#introducao)
2. [Instalação](#instalacao)
3. [Conceitos Principais](#conceitos-principais)
4. [Middlewares Padrão](#middlewares-padrao)
    - [Middleware Global](#middleware-global)
    - [Middleware de Grupo](#middleware-de-grupo)
    - [Middleware de Rota](#middleware-de-rota)
    - [Middleware de Upload (AttachmentMiddleware)](#middleware-de-upload-attachmentmiddleware)
    - [Middleware de CORS (CorsMiddleware)](#middleware-de-cors-corsmiddleware)
    - [Middleware de Erro (ErrorHandlerMiddleware)](#middleware-de-erro-errorhandlermiddleware)
    - [Middleware de Documentação OpenAPI (OpenApiDocsMiddleware)](#middleware-de-documentacao-openapi-openapidocsmiddleware)
    - [Middleware de Rate Limiting](#middleware-de-rate-limiting)
5. [Tratamento de Erros](#tratamento-de-erros)
6. [Sub-Routers Modulares (RouterInstance)](#sub-routers-modulares-routerinstance)
7. [Documentação Automática (OpenAPI/Swagger)](#documentacao-automatica-openapiswagger)
8. [Configuração da URL Base](#configuracao-da-url-base)
9. [Exemplos de Uso](#exemplos-de-uso)
10. [FAQ](#faq)
11. [Considerações Finais](#consideracoes-finais)
12. [Exemplos Avançados e Casos de Uso](#exemplos-avancados-e-casos-de-uso)
13. [Performance e Otimização](#performance-e-otimizacao)

## Introdução

O Express PHP é um microframework para PHP que visa proporcionar uma maneira simples e rápida de desenvolver aplicações web e APIs. Com uma sintaxe limpa e recursos poderosos, o Express PHP é ideal tanto para iniciantes quanto para desenvolvedores experientes que buscam agilidade no desenvolvimento.

## Instalação

Para instalar o Express PHP, você pode usar o Composer. Execute o seguinte comando em seu terminal:

```bash
composer require nome/do-pacote
```

## Conceitos Principais

O Express PHP é construído em torno de alguns conceitos principais:

- **Roteamento**: Definição de rotas para sua aplicação de forma simples e intuitiva.
- **Middlewares**: Funções que podem ser usadas para modificar requisições, respostas ou finalizar o ciclo de requisição.
- **Injeção de Dependência**: Facilita a gestão de dependências em suas classes.

## Middlewares Padrão

O Express PHP vem com alguns middlewares padrão que podem ser úteis na maioria das aplicações:

### Middleware Global

Executado em todas as requisições.

```php
$app->use(function($req, $res, $next) {
    // Código aqui será executado em todas as requisições
    $next();
});
```

### Middleware de Grupo

Executado apenas em um grupo específico de rotas.

```php
$app->group('/api', function() {
    // Rotas aqui dentro terão o middleware aplicado
});
```

### Middleware de Rota

Executado apenas em uma rota específica.

```php
$app->get('/usuario', function($req, $res) {
    // Código aqui será executado apenas para a rota /usuario
});
```

### Middleware de Upload (AttachmentMiddleware)

Para gerenciar uploads de arquivos.

```php
$app->post('/upload', function($req, $res) {
    $arquivo = $req->file('foto');
    // Lógica para manipulação do arquivo
});
```

### Middleware de CORS (CorsMiddleware)

Para habilitar CORS em sua API.

```php
$app->use(new CorsMiddleware());
```

### Middleware de Erro (ErrorHandlerMiddleware)

Para tratamento global de erros.

```php
$app->use(new ErrorHandlerMiddleware());
```

### Middleware de Documentação OpenAPI (OpenApiDocsMiddleware)

Para gerar documentação automática da sua API.

```php
$app->use(new OpenApiDocsMiddleware());
```

### Middleware de Rate Limiting

Utilize o RateLimitMiddleware para limitar requisições por IP:

```php
use Express\SRC\Services\RateLimitMiddleware;
$app->use(new RateLimitMiddleware([
    'max' => 60,      // máximo de requisições
    'window' => 60    // janela em segundos
]));
```

- Retorna status 429 e mensagem padronizada ao exceder o limite.
- Personalize os valores conforme a necessidade da sua API.

Exemplo de resposta ao exceder o limite:
```json
{
  "error": true,
  "message": "Rate limit exceeded",
  "limit": 60,
  "window": 60
}
```

## Middlewares de Segurança

O Express PHP inclui middlewares robustos de segurança para proteger sua aplicação contra ataques CSRF e XSS.

### Middleware de Segurança Combinado (SecurityMiddleware)

O SecurityMiddleware oferece proteção completa contra CSRF e XSS em um único middleware:

```php
use Express\SRC\Services\SecurityMiddleware;

// Configuração básica (recomendada)
$app->use(SecurityMiddleware::create());

// Configuração estrita (máxima segurança)
$app->use(SecurityMiddleware::strict());

// Configuração personalizada
$app->use(new SecurityMiddleware([
    'enableCsrf' => true,
    'enableXss' => true,
    'rateLimiting' => false,
    'csrf' => [
        'excludePaths' => ['/api/webhook', '/api/public'],
        'generateTokenResponse' => true
    ],
    'xss' => [
        'excludeFields' => ['content', 'description'],
        'allowedTags' => '<p><br><strong><em><ul><ol><li><a>'
    ]
]));
```

### Middleware de Proteção CSRF (CsrfMiddleware)

Protege contra ataques Cross-Site Request Forgery:

```php
use Express\SRC\Services\CsrfMiddleware;

// Aplicar globalmente
$app->use(new CsrfMiddleware());

// Com configurações personalizadas
$app->use(new CsrfMiddleware([
    'headerName' => 'X-CSRF-Token',
    'fieldName' => 'csrf_token',
    'excludePaths' => ['/api/public'],
    'methods' => ['POST', 'PUT', 'PATCH', 'DELETE']
]));

// Obter token CSRF para formulários
$app->get('/form', function($req, $res) {
    $csrfField = CsrfMiddleware::hiddenField();
    $csrfMeta = CsrfMiddleware::metaTag();
    // Use $csrfField em formulários HTML
    // Use $csrfMeta para requisições AJAX
});
```

### Middleware de Proteção XSS (XssMiddleware)

Protege contra ataques Cross-Site Scripting:

```php
use Express\SRC\Services\XssMiddleware;

// Aplicar globalmente
$app->use(new XssMiddleware());

// Com configurações personalizadas
$app->use(new XssMiddleware([
    'sanitizeInput' => true,
    'securityHeaders' => true,
    'excludeFields' => ['rich_content'],
    'allowedTags' => '<p><br><strong><em><ul><ol><li><a>',
    'contentSecurityPolicy' => "default-src 'self'; script-src 'self';"
]));

// Sanitização manual
$cleanData = XssMiddleware::sanitize($userInput);
$safeUrl = XssMiddleware::cleanUrl($url);
$hasXss = XssMiddleware::containsXss($input);
```

### Cabeçalhos de Segurança Incluídos

Os middlewares de segurança automaticamente adicionam os seguintes cabeçalhos:

- `X-XSS-Protection`: Ativa proteção XSS no navegador
- `X-Content-Type-Options`: Previne MIME sniffing
- `X-Frame-Options`: Protege contra clickjacking
- `Referrer-Policy`: Controla informações de referrer
- `Content-Security-Policy`: Define política de segurança de conteúdo

### Configurações de Sessão Segura

O SecurityMiddleware também configura parâmetros seguros de sessão:

- Cookies HttpOnly (não acessíveis via JavaScript)
- Regeneração periódica de ID da sessão
- SameSite cookies para proteção CSRF
- Configurações de tempo de vida da sessão

### Exemplo Completo de Uso

```php
use Express\SRC\ApiExpress;
use Express\SRC\Services\SecurityMiddleware;
use Express\SRC\Services\CsrfMiddleware;

$app = new ApiExpress();

// Aplicar segurança globalmente
$app->use(SecurityMiddleware::create());

// Rota para obter token CSRF
$app->get('/csrf-token', function($req, $res) {
    $res->json([
        'csrf_token' => CsrfMiddleware::getToken(),
        'meta_tag' => CsrfMiddleware::metaTag()
    ]);
});

// Rota protegida
$app->post('/api/user', function($req, $res) {
    // Dados já sanitizados automaticamente
    $userData = $req->body;
    $res->json(['message' => 'User created', 'data' => $userData]);
});

$app->run();
```

Para mais exemplos, consulte `examples/exemplo_seguranca.php` e os snippets em `examples/snippets/`.

## Tratamento de Erros

O tratamento de erros pode ser feito através do middleware de erro ou utilizando blocos try/catch em suas rotas.

```php
$app->get('/usuario', function($req, $res) {
    try {
        // Código que pode gerar exceção
    } catch (Exception $e) {
        // Tratamento da exceção
        $res->status(500)->json(['error' => 'Erro interno']);
    }
});
```

## 5.1 Documentação de Casos de Erro e Handlers Customizados

O ErrorHandlerMiddleware permite personalizar a resposta de erro para diferentes tipos de exceção.

### Exemplo: Handler customizado para erros de validação
```php
$customHandler = function($e, $req, $res) {
    if ($e instanceof ValidationException) {
        $res->status(422)->json([
            'error' => true,
            'message' => 'Erro de validação',
            'fields' => $e->getErrors()
        ]); exit;
    }
    // fallback para outros erros
    $res->status(500)->json([
        'error' => true,
        'message' => $e->getMessage(),
        'type' => get_class($e)
    ]); exit;
};
$app->use(new ErrorHandlerMiddleware($customHandler));
```

### Exemplo: Handler para erros de autenticação
```php
$customHandler = function($e, $req, $res) {
    if ($e instanceof AuthException) {
        $res->status(401)->json([
            'error' => true,
            'message' => 'Acesso não autorizado'
        ]); exit;
    }
    // fallback padrão
    $res->status(500)->json([
        'error' => true,
        'message' => $e->getMessage(),
        'type' => get_class($e)
    ]); exit;
};
```

---

## Sub-Routers Modulares (RouterInstance)

Para aplicações maiores, é possível ter sub-routers modulares.

```php
$router = new RouterInstance();
$router->get('/produtos', function($req, $res) {
    // Lógica para listar produtos
});
$app->use('/api', $router);
```

## Documentação Automática (OpenAPI/Swagger)

Para gerar documentação automática da sua API, você pode usar o middleware OpenApiDocsMiddleware. Ele irá gerar a documentação com base nas rotas e nos comentários em seu código.

```php
$app->use(new OpenApiDocsMiddleware());
```

Acesse a documentação em `/docs`.

## Configuração da URL Base

Para configurar a URL base da sua aplicação, você pode usar o método `setBaseUrl`.

```php
$app->setBaseUrl('/api/v1');
```

## Exemplos de Uso

### Exemplo 1: Olá Mundo

```php
$app->get('/hello', function($req, $res) {
    $res->json(['message' => 'Olá, Mundo!']);
});
```

### Exemplo 2: Parâmetros de Rota

```php
$app->get('/usuario/{id}', function($req, $res, $args) {
    $id = $args['id'];
    // Lógica para buscar o usuário pelo ID
});
```

### Exemplo 3: Consultas com Filtros

```php
$app->get('/produtos', function($req, $res) {
    $categoria = $req->query('categoria');
    // Lógica para filtrar produtos pela categoria
});
```

## FAQ

**P: O Express PHP é adequado para aplicações grandes?**

R: Sim, o Express PHP pode ser usado para aplicações de qualquer tamanho. Para aplicações maiores, recomenda-se o uso de sub-routers modulares.

**P: Como é feito o tratamento de erros?**

R: O tratamento de erros pode ser feito através do middleware de erro ou utilizando blocos try/catch em suas rotas.

## Considerações Finais

O Express PHP é uma ótima escolha para quem busca um microframework leve, mas poderoso. Com ele, é possível desenvolver aplicações web e APIs de forma rápida e eficiente.

## Exemplos Avançados e Casos de Uso

### Integração com Banco de Dados (PDO)

```php
$app->get('/produtos', function($req, $res) {
    $pdo = new PDO('mysql:host=localhost;dbname=meubanco', 'user', 'senha');
    $stmt = $pdo->query('SELECT * FROM produtos');
    $res->json($stmt->fetchAll(PDO::FETCH_ASSOC));
});
```

### Autenticação JWT

```php
use Firebase\JWT\JWT;
$app->use(function($req, $res, $next) {
    $auth = $req->headers->authorization;
    if (!$auth || !preg_match('/Bearer (.+)/', $auth, $m)) {
        $res->status(401)->json(['error' => 'Token ausente']); exit;
    }
    try {
        $payload = JWT::decode($m[1], 'chave_secreta', ['HS256']);
        $req->user = $payload;
        $next();
    } catch (Exception $e) {
        $res->status(401)->json(['error' => 'Token inválido']); exit;
    }
});
```

### Upload Avançado (múltiplos arquivos)

```php
$app->post('/upload', function($req, $res) {
    $arquivos = $req->files['fotos'] ?? [];
    $salvos = [];
    foreach ($arquivos as $file) {
        move_uploaded_file($file['tmp_name'], '/tmp/' . $file['name']);
        $salvos[] = $file['name'];
    }
    $res->json(['ok' => true, 'arquivos' => $salvos]);
});
```

### Uso em Produção (nginx)

```nginx
server {
    listen 80;
    server_name api.meusite.com;
    root /caminho/para/public;
    location / {
        try_files $uri /index.php?$query_string;
    }
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Performance e Otimização

### Benchmarks Simples
Para medir o tempo de resposta de uma rota:
```php
$app->use(function($req, $res, $next) {
    $req->_start = microtime(true);
    $next();
    $tempo = round((microtime(true) - $req->_start) * 1000, 2);
    error_log("Tempo de resposta: {$tempo} ms");
});
```

### Dicas de Otimização
- Utilize cache de respostas para rotas estáticas ou dados pouco mutáveis.
- Ative compressão gzip no servidor web (nginx, Apache).
- Use PHP-FPM em modo production e opcache habilitado.
- Prefira PDO com prepared statements para acesso a banco.
- Evite lógica pesada em middlewares globais.
- Monitore consumo de memória e gargalos com ferramentas como Blackfire, Xdebug ou NewRelic.

### Exemplo de Cache Simples (APCu)
```php
$app->get('/dados', function($req, $res) {
    $cacheKey = 'dados_api';
    $dados = apcu_fetch($cacheKey);
    if ($dados === false) {
        // Simula consulta lenta
        sleep(2);
        $dados = ['foo' => 'bar'];
        apcu_store($cacheKey, $dados, 60);
    }
    $res->json($dados);
});
```

## 7.1 Helpers para Versionamento e Depreciação de Rotas

Implemente helpers para marcar rotas como deprecadas e documentar versões:

```php
function deprecated($handler, $msg = 'Esta rota está obsoleta.') {
    return function($req, $res, $next) use ($handler, $msg) {
        $res->header('X-Deprecation-Notice', $msg);
        $handler($req, $res, $next);
    };
}

$v1 = new RouterInstance('/v1');
$v1->get('/user', deprecated(function($req, $res) {
    $res->json(['msg' => 'v1']);
}, 'Use a versão /v2/user'));
$app->use($v1);
```

---

## 8.1 Endpoint de Healthcheck e Estrutura de Monitoramento

Adicione um endpoint simples para healthcheck e monitore recursos:

```php
$app->get('/health', function($req, $res) {
    $res->json([
        'status' => 'ok',
        'php_version' => PHP_VERSION,
        'memory_usage' => memory_get_usage(true),
        'time' => date('c')
    ]);
});
```

- Expanda para incluir métricas customizadas, uso de CPU, conexões a banco, etc.
- Integre com ferramentas externas de monitoramento conforme necessário.

---

## Helpers Utilitários (Utils)

A biblioteca inclui helpers prontos para uso em qualquer parte do seu app:

### Sanitização e Validação

```php
use Express\SRC\Helpers\Utils;

// Sanitização
$nome = Utils::sanitizeString($input['nome']);
$email = Utils::sanitizeEmail($input['email']);
$tags = Utils::sanitizeArray($input['tags'] ?? []);

// Validação
if (!Utils::isEmail($email)) {
    throw new Exception('E-mail inválido');
}
if (!Utils::isInt($input['idade'])) {
    throw new Exception('Idade deve ser inteira');
}
```

### CORS Dinâmico

```php
use Express\SRC\Helpers\Utils;

// Em um middleware customizado:
$app->use(function($req, $res, $next) {
    $headers = Utils::corsHeaders(['https://meusite.com'], ['GET','POST'], ['Content-Type','Authorization']);
    foreach ($headers as $k => $v) {
        $res->header($k, $v);
    }
    $next();
});
```

### Log Simples

```php
use Express\SRC\Helpers\Utils;
Utils::log('Usuário autenticado', 'info');
Utils::log('Tentativa de acesso negada', 'warning');
```

### Geração de Token Seguro e CSRF

```php
use Express\SRC\Helpers\Utils;
$token = Utils::randomToken();
$csrf = Utils::csrfToken();
if (!Utils::checkCsrf($_POST['csrf_token'] ?? '')) {
    die('CSRF inválido!');
}
```

Esses helpers podem ser usados em qualquer rota, middleware ou serviço do seu app Express PHP.
