<?php
/**
 * Exemplo de API com Middlewares PSR-15 - HelixPHP
 *
 * Demonstra o uso dos middlewares oficiais PSR-15 do framework
 * e como criar um middleware customizado seguindo o padrão PSR-15.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Helix\Core\Application;
use Helix\Http\Request;
use Helix\Http\Response;
use Helix\Http\Psr15\Middleware\CorsMiddleware;
use Helix\Http\Psr15\Middleware\RateLimitMiddleware;
use Helix\Http\Psr15\Middleware\XssMiddleware;
use Helix\Http\Psr15\Middleware\SecurityHeadersMiddleware;

// Criar aplicação
$app = new Application();

// ================================
// MIDDLEWARES PSR-15 OFICIAIS
// ================================

// CORS Middleware (PSR-15)
$app->use(new CorsMiddleware([
    'origins' => ['*'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'headers' => ['Content-Type', 'Authorization', 'X-Requested-With']
]));

// Rate Limiting Middleware (PSR-15)
$app->use(new RateLimitMiddleware([
    'maxRequests' => 30,
    'timeWindow' => 60, // 1 minuto
    'keyGenerator' => function($req) { return $req->ip() ?? 'unknown'; }
]));

// XSS Protection Middleware (PSR-15)
$app->use(new XssMiddleware());

// Security Headers Middleware (PSR-15)
$app->use(new SecurityHeadersMiddleware());

// ================================
// MIDDLEWARE CUSTOMIZADO PSR-15
// ================================

/**
 * Como criar um middleware customizado PSR-15:
 *
 * 1. Implemente a interface Psr\Http\Server\MiddlewareInterface.
 * 2. Implemente o método process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
 * 3. Faça qualquer lógica antes/depois de $handler->handle($request).
 * 4. Sempre retorne um ResponseInterface.
 *
 * Exemplo abaixo:
 */

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class LoggingMiddleware implements MiddlewareInterface {
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        // Lógica antes do próximo middleware/handler
        $start = microtime(true);
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $request->getHeaderLine('User-Agent') ?? 'unknown';
        error_log("[REQUEST] {$method} {$path} - IP: {$ip} - UA: " . substr($userAgent, 0, 50));

        // Chama o próximo middleware ou a rota
        $response = $handler->handle($request);

        // Lógica após o próximo middleware/handler
        $duration = round((microtime(true) - $start) * 1000, 2);
        error_log("[RESPONSE] {$method} {$path} - {$duration}ms");
        return $response;
    }
}

// Adicionar o middleware customizado PSR-15
$app->use(new LoggingMiddleware());

// ================================
// DADOS SIMULADOS
// ================================

$products = [
    ['id' => 1, 'name' => 'Laptop Dell', 'price' => 2500.00, 'category' => 'electronics'],
    ['id' => 2, 'name' => 'Mouse Logitech', 'price' => 50.00, 'category' => 'electronics'],
    ['id' => 3, 'name' => 'Teclado Mecânico', 'price' => 150.00, 'category' => 'electronics'],
    ['id' => 4, 'name' => 'Cadeira Gamer', 'price' => 800.00, 'category' => 'furniture']
];

// ================================
// ROTAS DA API
// ================================

// Página inicial
$app->get('/', function(Request $req, Response $res) {
    $res->json([
        'message' => 'API de Produtos - HelixPHP',
        'version' => '2.0',
        'features' => [
            'CORS habilitado',
            'Rate limiting (30 req/min)',
            'Logging detalhado',
            'Validação automática'
        ],
        'endpoints' => [
            'GET /api/products' => 'Listar produtos',
            'GET /api/products/:id' => 'Buscar produto',
            'POST /api/products' => 'Criar produto',
            'PUT /api/products/:id' => 'Atualizar produto',
            'DELETE /api/products/:id' => 'Remover produto'
        ]
    ]);
});

// GET /api/products - Listar produtos com filtros
$app->get('/api/products', function(Request $req, Response $res) use ($products) {
    $category = $req->getQuery('category');
    $minPrice = $req->getQuery('min_price');
    $maxPrice = $req->getQuery('max_price');

    $filteredProducts = $products;

    // Filtrar por categoria
    if ($category) {
        $filteredProducts = array_filter($filteredProducts, function($product) use ($category) {
            return strtolower($product['category']) === strtolower($category);
        });
    }

    // Filtrar por preço mínimo
    if ($minPrice && is_numeric($minPrice)) {
        $filteredProducts = array_filter($filteredProducts, function($product) use ($minPrice) {
            return $product['price'] >= floatval($minPrice);
        });
    }

    // Filtrar por preço máximo
    if ($maxPrice && is_numeric($maxPrice)) {
        $filteredProducts = array_filter($filteredProducts, function($product) use ($maxPrice) {
            return $product['price'] <= floatval($maxPrice);
        });
    }

    $res->json([
        'success' => true,
        'data' => array_values($filteredProducts),
        'total' => count($filteredProducts),
        'filters_applied' => [
            'category' => $category,
            'min_price' => $minPrice,
            'max_price' => $maxPrice
        ]
    ]);
});

// GET /api/products/:id - Buscar produto por ID
$app->get('/api/products/:id', function(Request $req, Response $res) use ($products) {
    $id = (int) $req->getParam('id');

    $product = array_filter($products, function($p) use ($id) {
        return $p['id'] === $id;
    });

    if ($product) {
        $res->json([
            'success' => true,
            'data' => array_values($product)[0]
        ]);
    } else {
        $res->status(404)->json([
            'success' => false,
            'message' => 'Produto não encontrado'
        ]);
    }
});

// POST /api/products - Criar produto (com validação)
$app->post('/api/products', function(Request $req, Response $res) use (&$products) {
    $data = $req->getBody();

    $newProduct = [
        'id' => count($products) + 1,
        'name' => trim($data['name']),
        'price' => floatval($data['price']),
        'category' => trim($data['category'])
    ];

    $products[] = $newProduct;

    $res->status(201)->json([
        'success' => true,
        'message' => 'Produto criado com sucesso',
        'data' => $newProduct
    ]);
});

// PUT /api/products/:id - Atualizar produto
$app->put('/api/products/:id', function(Request $req, Response $res) use (&$products) {
    $id = (int) $req->getParam('id');
    $data = $req->getBody();

    $productIndex = null;
    foreach ($products as $index => $product) {
        if ($product['id'] === $id) {
            $productIndex = $index;
            break;
        }
    }

    if ($productIndex === null) {
        $res->status(404)->json([
            'success' => false,
            'message' => 'Produto não encontrado'
        ]);
        return;
    }

    // Atualizar apenas campos fornecidos
    if (isset($data['name'])) {
        $products[$productIndex]['name'] = trim($data['name']);
    }
    if (isset($data['price']) && is_numeric($data['price'])) {
        $products[$productIndex]['price'] = floatval($data['price']);
    }
    if (isset($data['category'])) {
        $products[$productIndex]['category'] = trim($data['category']);
    }

    $res->json([
        'success' => true,
        'message' => 'Produto atualizado com sucesso',
        'data' => $products[$productIndex]
    ]);
});

// DELETE /api/products/:id - Remover produto
$app->delete('/api/products/:id', function(Request $req, Response $res) use (&$products) {
    $id = (int) $req->getParam('id');

    $productIndex = null;
    foreach ($products as $index => $product) {
        if ($product['id'] === $id) {
            $productIndex = $index;
            break;
        }
    }

    if ($productIndex === null) {
        $res->status(404)->json([
            'success' => false,
            'message' => 'Produto não encontrado'
        ]);
        return;
    }

    array_splice($products, $productIndex, 1);

    $res->json([
        'success' => true,
        'message' => 'Produto removido com sucesso'
    ]);
});

// Rota para testar rate limiting
$app->get('/test/rate-limit', function(Request $req, Response $res) {
    $res->json([
        'success' => true,
        'message' => 'Request processado com sucesso',
        'timestamp' => date('Y-m-d H:i:s'),
        'tip' => 'Faça 30+ requests em 1 minuto para testar o rate limit'
    ]);
});

// ================================
// EXECUTAR APLICAÇÃO
// ================================

if (php_sapi_name() === 'cli-server') {
    echo "HelixPHP Middleware API rodando em http://localhost:8000\n";
    echo "\nFuncionalidades:\n";
    echo "  ✓ CORS habilitado\n";
    echo "  ✓ Rate limiting (30 requests/minuto)\n";
    echo "  ✓ Logging detalhado\n";
    echo "  ✓ Validação automática de JSON\n";
    echo "\nEndpoints:\n";
    echo "  GET  /api/products           - Listar produtos\n";
    echo "  GET  /api/products?category=electronics - Filtrar por categoria\n";
    echo "  POST /api/products           - Criar produto\n";
    echo "  PUT  /api/products/1         - Atualizar produto\n";
    echo "  GET  /test/rate-limit        - Testar rate limiting\n";
    echo "\nExemplo de criação:\n";
    echo "  curl -X POST http://localhost:8000/api/products \\\n";
    echo "    -H 'Content-Type: application/json' \\\n";
    echo "    -d '{\"name\":\"Novo Produto\",\"price\":99.99,\"category\":\"test\"}'\n";
    echo "\n";
}

$app->run();
