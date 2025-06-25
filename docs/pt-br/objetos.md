# Objetos e Funcionalidades do Express PHP

## Índice
- [ApiExpress](#apiexpress)
- [Router](#router)
- [Request](#request)
- [Response](#response)
- [HeaderRequest](#headerrequest)
- [ServerExpress](#serverexpress)
- [Middlewares](#middlewares)
- [Middlewares de Segurança](#middlewares-de-segurança)

---

## ApiExpress
Classe principal para inicialização e execução da aplicação.
- **Função:** Gerencia o ciclo de vida da aplicação, delegando o roteamento e execução dos handlers.
- **Principais métodos:**
  - `run()`: Inicia o processamento da requisição, identifica a rota e executa o handler correspondente.
  - `use($middleware)`: Registra middlewares globais ou agrupamento de rotas.
  - Métodos mágicos para delegar chamadas de rotas (`get`, `post`, etc) para o Router.
- **Exemplo:**
```php
$app = new ApiExpress();
$app->use(function($req, $res, $next) { /* ... */ $next(); });
$app->get('/user/:id', function($req, $res) { ... });
$app->run();
```

## Router
Classe estática responsável pelo registro e identificação de rotas.
- **Função:** Permite agrupar rotas, registrar handlers e middlewares para métodos HTTP e identificar a rota correspondente a uma requisição.
- **Principais métodos:**
  - `use($path)`: Define um prefixo/base para rotas.
  - `get`, `post`, `put`, `delete`, etc: Registram rotas para métodos HTTP, aceitando múltiplos middlewares e handler final.
  - `identify($method, $path)`: Retorna o handler, middlewares e parâmetros para a rota correspondente.

## Request
Representa a requisição HTTP recebida.
- **Função:** Facilita o acesso a parâmetros de rota, query string, corpo da requisição e cabeçalhos.
- **Principais propriedades:**
  - `$method`: Método HTTP.
  - `$path`: Padrão da rota.
  - `$params`: Parâmetros extraídos da URL.
  - `$query`: Parâmetros da query string.
  - `$body`: Corpo da requisição (JSON ou form-data).
  - `$headers`: Instância de `HeaderRequest` para acesso aos cabeçalhos.
- **Exemplo:**
```php
$app->get('/user/:id', function($req, $res) {
  $id = $req->params->id;
  $token = $req->headers->authorization;
});
```

## Response
Constrói e envia a resposta HTTP.
- **Função:** Permite definir status, cabeçalhos e corpo da resposta em diferentes formatos.
- **Principais métodos:**
  - `status($code)`: Define o status HTTP.
  - `header($name, $value)`: Define um cabeçalho.
  - `json($data)`: Envia resposta JSON.
  - `text($text)`: Envia resposta em texto puro.
  - `html($html)`: Envia resposta em HTML.
- **Exemplo:**
```php
$res->status(200)->json(['ok' => true]);
```

## HeaderRequest
Gerencia e facilita o acesso aos cabeçalhos da requisição.
- **Função:** Converte os cabeçalhos para camelCase e permite acesso via propriedades ou métodos.
- **Principais métodos:**
  - `getHeader($name)`: Retorna o valor de um cabeçalho.
  - `getAllHeaders()`: Retorna todos os cabeçalhos.
  - `hasHeader($name)`: Verifica se um cabeçalho existe.
- **Exemplo:**
```php
if ($req->headers->hasHeader('authorization')) {
  $token = $req->headers->authorization;
}
```

## ServerExpress
Classe placeholder para futuras implementações de funcionalidades de servidor.
- **Função:** Atualmente vazia, pode ser estendida para customizações.

## Middlewares
O Express PHP suporta middlewares globais e por rota, com assinatura compatível ao Express.js:

- **Middleware global:**
```php
$app->use(function($req, $res, $next) {
    // Executa para todas as rotas
    $next();
});
```

- **Middleware por rota:**
```php
$app->get('/rota',
    function($req, $res, $next) {
        // Middleware 1
        $next();
    },
    function($req, $res, $next) {
        // Middleware 2
        $next();
    },
    function($req, $res) {
        // Handler final
        $res->json(['ok' => true]);
    }
);
```

- **Encadeamento:**
  - Cada middleware deve chamar `$next()` para passar o controle adiante.
  - É possível modificar o objeto `$request` entre middlewares.

---

## Middlewares de Segurança

### SecurityMiddleware
Middleware combinado que oferece proteção completa contra CSRF e XSS.
- **Função:** Aplica múltiplas camadas de segurança em uma única configuração.
- **Principais recursos:**
  - Proteção CSRF automática
  - Sanitização XSS de entrada
  - Cabeçalhos de segurança
  - Rate limiting opcional
  - Configuração segura de sessão
- **Exemplo:**
```php
// Configuração básica
$app->use(SecurityMiddleware::create());

// Configuração estrita
$app->use(SecurityMiddleware::strict());

// Configuração personalizada
$app->use(new SecurityMiddleware([
    'enableCsrf' => true,
    'enableXss' => true,
    'rateLimiting' => false,
    'csrf' => ['excludePaths' => ['/api/public']],
    'xss' => ['excludeFields' => ['content']]
]));
```

### CsrfMiddleware
Middleware específico para proteção contra ataques CSRF.
- **Função:** Valida tokens CSRF em requisições POST, PUT, PATCH e DELETE.
- **Principais recursos:**
  - Geração automática de tokens
  - Validação em headers ou body
  - Exclusão de caminhos específicos
  - Métodos utilitários para formulários
- **Exemplo:**
```php
$app->use(new CsrfMiddleware([
    'headerName' => 'X-CSRF-Token',
    'fieldName' => 'csrf_token',
    'excludePaths' => ['/webhook'],
    'methods' => ['POST', 'PUT', 'DELETE']
]));

// Obter token para formulários
$token = CsrfMiddleware::getToken();
$hiddenField = CsrfMiddleware::hiddenField();
$metaTag = CsrfMiddleware::metaTag();
```

### XssMiddleware
Middleware específico para proteção contra ataques XSS.
- **Função:** Sanitiza dados de entrada e adiciona cabeçalhos de segurança.
- **Principais recursos:**
  - Sanitização automática de input
  - Cabeçalhos de segurança (X-XSS-Protection, CSP, etc.)
  - Detecção de conteúdo malicioso
  - Tags HTML permitidas configuráveis
  - Limpeza de URLs
- **Exemplo:**
```php
$app->use(new XssMiddleware([
    'sanitizeInput' => true,
    'securityHeaders' => true,
    'excludeFields' => ['rich_content'],
    'allowedTags' => '<p><strong><em>',
    'contentSecurityPolicy' => "default-src 'self';"
]));

// Métodos utilitários
$clean = XssMiddleware::sanitize($input);
$hasXss = XssMiddleware::containsXss($input);
$safeUrl = XssMiddleware::cleanUrl($url);
```

### Cabeçalhos de Segurança Aplicados
Os middlewares de segurança automaticamente adicionam:
- `X-XSS-Protection`: Proteção XSS do navegador
- `X-Content-Type-Options`: Prevenção de MIME sniffing  
- `X-Frame-Options`: Proteção contra clickjacking
- `Referrer-Policy`: Controle de informações de referrer
- `Content-Security-Policy`: Política de segurança de conteúdo

### Configuração de Sessão Segura
O SecurityMiddleware configura automaticamente:
- Cookies HttpOnly (não acessíveis via JavaScript)
- Regeneração periódica de ID da sessão
- SameSite cookies para proteção CSRF
- Parâmetros seguros de tempo de vida

### AuthMiddleware
Middleware automático de autenticação com suporte nativo para múltiplos métodos.
- **Função:** Autentica requisições usando JWT, Basic Auth, Bearer Token, API Key ou métodos customizados.
- **Principais recursos:**
  - Suporte a JWT com biblioteca Firebase ou implementação nativa
  - Basic Authentication com callback customizado
  - Bearer Token authentication
  - API Key via header ou query parameter
  - Autenticação customizada via callback
  - Múltiplos métodos em uma única configuração
  - Caminhos excluídos da autenticação
  - Modo flexível (opcional)
- **Exemplo JWT:**
```php
// JWT apenas
$app->use(AuthMiddleware::jwt('sua_chave_secreta'));

// JWT com configurações
$app->use(AuthMiddleware::jwt('chave_secreta', [
    'excludePaths' => ['/public', '/login']
]));
```

- **Exemplo Basic Auth:**
```php
function validateUser($username, $password) {
    // Validar no banco de dados
    return $username === 'admin' && $password === 'senha123'
        ? ['id' => 1, 'username' => 'admin'] : false;
}

$app->use(AuthMiddleware::basic('validateUser'));
```

- **Exemplo API Key:**
```php
function validateApiKey($key) {
    $validKeys = ['key123' => ['name' => 'App Mobile']];
    return $validKeys[$key] ?? false;
}

$app->use(AuthMiddleware::apiKey('validateApiKey'));
// Usar: Header X-API-Key: key123 OU ?api_key=key123
```

- **Exemplo Múltiplos Métodos:**
```php
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt', 'basic', 'apikey'],
    'jwtSecret' => 'chave_jwt',
    'basicAuthCallback' => 'validateUser',
    'apiKeyCallback' => 'validateApiKey',
    'allowMultiple' => true,
    'excludePaths' => ['/public']
]));
```

- **Acessar dados do usuário:**
```php
$app->get('/profile', function($req, $res) {
    $user = $req->user; // dados do usuário autenticado
    $method = $req->auth['method']; // método usado (jwt, basic, etc)
    $res->json(['user' => $user, 'auth_method' => $method]);
});
```

### JWTHelper
Helper para trabalhar com JSON Web Tokens de forma simples.
- **Função:** Facilita criação, validação e decodificação de tokens JWT.
- **Principais métodos:**
  - `encode($payload, $secret, $options)`: Gera um token JWT
  - `decode($token, $secret, $options)`: Decodifica e valida token
  - `isValid($token, $secret)`: Verifica se token é válido
  - `isExpired($token, $leeway)`: Verifica se token expirou
  - `getPayload($token)`: Extrai payload sem validar assinatura
  - `generateSecret($length)`: Gera chave secreta aleatória
  - `createRefreshToken($userId, $secret)`: Cria refresh token
  - `validateRefreshToken($token, $secret)`: Valida refresh token
- **Exemplo:**
```php
use Express\Helpers\JWTHelper;

// Gerar token
$token = JWTHelper::encode([
    'user_id' => 123,
    'username' => 'usuario',
    'role' => 'admin'
], 'chave_secreta', [
    'expiresIn' => 3600 // 1 hora
]);

// Validar token
if (JWTHelper::isValid($token, 'chave_secreta')) {
    $payload = JWTHelper::decode($token, 'chave_secreta');
    echo "Usuário: " . $payload['username'];
}

// Refresh token
$refreshToken = JWTHelper::createRefreshToken(123, 'chave_secreta');
$refreshData = JWTHelper::validateRefreshToken($refreshToken, 'chave_secreta');
```
