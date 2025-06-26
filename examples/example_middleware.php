<?php
/**
 * Exemplo de API com Middlewares - Express PHP
 *
 * Este exemplo demonstra como usar middlewares para
 * implementar funcionalidades como CORS, rate limiting,
 * logging e validação.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Express\ApiExpress;
use Express\Http\Request;
use Express\Http\Response;

// Criar aplicação
$app = new ApiExpress();

// ================================
// DADOS SIMULADOS
// ================================

$products = [
    ['id' => 1, 'name' => 'Laptop Dell', 'price' => 2500.00, 'category' => 'electronics'],
    ['id' => 2, 'name' => 'Mouse Logitech', 'price' => 50.00, 'category' => 'electronics'],
    ['id' => 3, 'name' => 'Teclado Mecânico', 'price' => 150.00, 'category' => 'electronics'],
    ['id' => 4, 'name' => 'Cadeira Gamer', 'price' => 800.00, 'category' => 'furniture']
];

// Simulação de rate limiting (em produção, use Redis ou banco)
$rateLimitStore = [];

// ================================
// MIDDLEWARES CUSTOMIZADOS
// ================================

// Middleware de CORS
$corsMiddleware = function(Request $req, Response $res, callable $next) {
    $res->header('Access-Control-Allow-Origin', '*');
    $res->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    $res->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

    if ($req->getMethod() === 'OPTIONS') {
        $res->status(200)->send();
        return;
    }

    return $next($req, $res);
};

// Middleware de logging detalhado
$loggingMiddleware = function(Request $req, Response $res, callable $next) {
    $start = microtime(true);
    $method = $req->getMethod();
    $path = $req->getPath();
    $ip = $req->getClientIp() ?? 'unknown';
    $userAgent = $req->getHeader('User-Agent') ?? 'unknown';

    // Log da requisição
    error_log("[REQUEST] {$method} {$path} - IP: {$ip} - UA: " . substr($userAgent, 0, 50));

    $result = $next($req, $res);

    $duration = round((microtime(true) - $start) * 1000, 2);
    error_log("[RESPONSE] {$method} {$path} - {$duration}ms");

    return $result;
};

// Middleware de rate limiting simples
$rateLimitMiddleware = function(Request $req, Response $res, callable $next) use (&$rateLimitStore) {
    $ip = $req->getClientIp() ?? 'unknown';
    $now = time();
    $window = 60; // 1 minuto
    $maxRequests = 30; // máximo 30 requests por minuto

    // Limpar registros antigos
    $rateLimitStore = array_filter($rateLimitStore, function($timestamp) use ($now, $window) {
        return ($now - $timestamp) < $window;
    });

    // Contar requests do IP
    $ipRequests = array_filter($rateLimitStore, function($data) use ($ip) {
        return isset($data['ip']) && $data['ip'] === $ip;
    });

    if (count($ipRequests) >= $maxRequests) {
        $res->status(429)->json([
            'success' => false,
            'message' => 'Rate limit excedido. Tente novamente em 1 minuto.',
            'retry_after' => 60
        ]);
        return;
    }

    // Registrar request atual
    $rateLimitStore[] = ['ip' => $ip, 'timestamp' => $now];

    return $next($req, $res);
};

// Middleware de validação JSON
$jsonValidationMiddleware = function(Request $req, Response $res, callable $next) {
    if (in_array($req->getMethod(), ['POST', 'PUT', 'PATCH'])) {
        $contentType = $req->getHeader('Content-Type');

        if ($contentType && strpos($contentType, 'application/json') !== false) {
            $body = $req->getBody();
            if (empty($body)) {
                $res->status(400)->json([
                    'success' => false,
                    'message' => 'Body JSON é obrigatório para este endpoint'
                ]);
                return;
            }
        }
    }

    return $next($req, $res);
};

// Middleware de validação de produto
$productValidationMiddleware = function(Request $req, Response $res, callable $next) {
    $data = $req->getBody();
    $errors = [];

    if (!isset($data['name']) || empty(trim($data['name']))) {
        $errors[] = 'Nome do produto é obrigatório';
    }

    if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0) {
        $errors[] = 'Preço deve ser um número positivo';
    }

    if (!isset($data['category']) || empty(trim($data['category']))) {
        $errors[] = 'Categoria é obrigatória';
    }

    if (!empty($errors)) {
        $res->status(400)->json([
            'success' => false,
            'message' => 'Dados inválidos',
            'errors' => $errors
        ]);
        return;
    }

    return $next($req, $res);
};

// ================================
// APLICAR MIDDLEWARES GLOBAIS
// ================================

$app->use($corsMiddleware);
$app->use($loggingMiddleware);
$app->use($rateLimitMiddleware);

// ================================
// ROTAS DA API
// ================================

// Página inicial
$app->get('/', function(Request $req, Response $res) {
    $res->json([
        'message' => 'API de Produtos - Express PHP',
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
$app->post('/api/products', $jsonValidationMiddleware, $productValidationMiddleware, function(Request $req, Response $res) use (&$products) {
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
$app->put('/api/products/:id', $jsonValidationMiddleware, function(Request $req, Response $res) use (&$products) {
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
    echo "Express PHP Middleware API rodando em http://localhost:8000\n";
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
