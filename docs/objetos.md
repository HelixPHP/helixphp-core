# Objetos e Funcionalidades do Express PHP

## Índice
- [ApiExpress](#apiexpress)
- [Router](#router)
- [Request](#request)
- [Response](#response)
- [HeaderRequest](#headerrequest)
- [ServerExpress](#serverexpress)
- [Middlewares](#middlewares)

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

Consulte o README principal para visão geral e exemplos de uso.
