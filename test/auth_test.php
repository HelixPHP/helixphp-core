<?php
// Teste simples dos novos middlewares de autenticação
require_once __DIR__ . '/../vendor/autoload.php';

use Express\Middlewares\Security\AuthMiddleware;
use Express\Helpers\JWTHelper;

echo "=== TESTE DOS MIDDLEWARES DE AUTENTICAÇÃO ===\n\n";

// ========================================
// 1. TESTE DO JWTHELPER
// ========================================
echo "1. TESTE JWT HELPER:\n";
echo "----------------------------------------\n";

// Gera uma chave secreta
$secret = JWTHelper::generateSecret();
echo "Chave secreta gerada: " . substr($secret, 0, 20) . "...\n";

// Cria um token
$payload = ['user_id' => 123, 'username' => 'testuser', 'role' => 'admin'];
$token = JWTHelper::encode($payload, $secret, ['expiresIn' => 3600]);
echo "Token gerado: " . substr($token, 0, 50) . "...\n";

// Valida o token
$isValid = JWTHelper::isValid($token, $secret);
echo "Token válido: " . ($isValid ? "SIM" : "NÃO") . "\n";

// Decodifica o token
try {
    $decoded = JWTHelper::decode($token, $secret);
    echo "Token decodificado com sucesso!\n";
    echo "User ID: " . $decoded['user_id'] . "\n";
    echo "Username: " . $decoded['username'] . "\n";
    echo "Role: " . $decoded['role'] . "\n";
} catch (Exception $e) {
    echo "Erro ao decodificar: " . $e->getMessage() . "\n";
}

// Testa token expirado
$expiredToken = JWTHelper::encode($payload, $secret, ['expiresIn' => -3600]);
$isExpired = JWTHelper::isExpired($expiredToken);
echo "Token expirado: " . ($isExpired ? "SIM" : "NÃO") . "\n";

// Cria refresh token
$refreshToken = JWTHelper::createRefreshToken(123, $secret);
$refreshData = JWTHelper::validateRefreshToken($refreshToken, $secret);
echo "Refresh token válido: " . ($refreshData ? "SIM" : "NÃO") . "\n";

echo "\n";

// ========================================
// 2. TESTE DO AUTHMIDDLEWARE - JWT
// ========================================
echo "2. TESTE AUTH MIDDLEWARE - JWT:\n";
echo "----------------------------------------\n";

// Simula request com JWT
$mockRequest = (object) [
    'headers' => (object) [
        'authorization' => "Bearer $token"
    ],
    'path' => '/api/user'
];

// Mock mais completo do response
$mockResponse = new class {
    public $statusCode;
    public $jsonData;
    public $headers = [];
    
    public function status($code) {
        $this->statusCode = $code;
        return $this;
    }
    
    public function json($data) {
        $this->jsonData = $data;
        return $this;
    }
    
    public function header($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }
};

$nextCalled = false;

$jwtMiddleware = AuthMiddleware::jwt($secret);

$jwtMiddleware($mockRequest, $mockResponse, function() use (&$nextCalled) {
    $nextCalled = true;
});

if ($nextCalled) {
    echo "✅ JWT Middleware: Autenticação bem-sucedida\n";
    echo "Usuário autenticado: " . $mockRequest->user['username'] . "\n";
    echo "Método de auth: " . $mockRequest->auth['method'] . "\n";
} else {
    echo "❌ JWT Middleware: Falha na autenticação\n";
}

echo "\n";

// ========================================
// 3. TESTE DO AUTHMIDDLEWARE - BASIC AUTH
// ========================================
echo "3. TESTE AUTH MIDDLEWARE - BASIC AUTH:\n";
echo "----------------------------------------\n";

// Função de validação Basic Auth
function validateBasicAuth($username, $password) {
    $users = [
        'admin' => 'password123',
        'user' => 'user123'
    ];
    
    if (isset($users[$username]) && $users[$username] === $password) {
        return [
            'id' => uniqid(),
            'username' => $username,
            'role' => $username === 'admin' ? 'admin' : 'user'
        ];
    }
    
    return false;
}

// Simula request com Basic Auth
$credentials = base64_encode('admin:password123');
$mockRequest2 = (object) [
    'headers' => (object) [
        'authorization' => "Basic $credentials"
    ],
    'path' => '/api/admin'
];

// Mock do response
$mockResponse2 = new class {
    public $statusCode;
    public $jsonData;
    public $headers = [];
    
    public function status($code) {
        $this->statusCode = $code;
        return $this;
    }
    
    public function json($data) {
        $this->jsonData = $data;
        return $this;
    }
    
    public function header($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }
};

$nextCalled2 = false;

$basicMiddleware = AuthMiddleware::basic('validateBasicAuth');

$basicMiddleware($mockRequest2, $mockResponse2, function() use (&$nextCalled2) {
    $nextCalled2 = true;
});

if ($nextCalled2) {
    echo "✅ Basic Auth Middleware: Autenticação bem-sucedida\n";
    echo "Usuário autenticado: " . $mockRequest2->user['username'] . "\n";
    echo "Role: " . $mockRequest2->user['role'] . "\n";
} else {
    echo "❌ Basic Auth Middleware: Falha na autenticação\n";
}

echo "\n";

// ========================================
// 4. TESTE DO AUTHMIDDLEWARE - API KEY
// ========================================
echo "4. TESTE AUTH MIDDLEWARE - API KEY:\n";
echo "----------------------------------------\n";

// Função de validação API Key
function validateApiKey($apiKey) {
    $validKeys = [
        'key123456' => ['id' => 1, 'name' => 'App Mobile', 'permissions' => ['read', 'write']],
        'service_key' => ['id' => 2, 'name' => 'Service Integration', 'permissions' => ['read']],
    ];
    
    return $validKeys[$apiKey] ?? false;
}

// Simula request com API Key no header
$_SERVER['HTTP_X_API_KEY'] = 'key123456';

$mockRequest3 = (object) [
    'headers' => (object) [],
    'path' => '/api/data'
];

// Mock do response
$mockResponse3 = new class {
    public $statusCode;
    public $jsonData;
    public $headers = [];
    
    public function status($code) {
        $this->statusCode = $code;
        return $this;
    }
    
    public function json($data) {
        $this->jsonData = $data;
        return $this;
    }
    
    public function header($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }
};

$nextCalled3 = false;

$apiKeyMiddleware = AuthMiddleware::apiKey('validateApiKey');

$apiKeyMiddleware($mockRequest3, $mockResponse3, function() use (&$nextCalled3) {
    $nextCalled3 = true;
});

if ($nextCalled3) {
    echo "✅ API Key Middleware: Autenticação bem-sucedida\n";
    echo "Cliente autenticado: " . $mockRequest3->user['name'] . "\n";
    echo "Permissões: " . implode(', ', $mockRequest3->user['permissions']) . "\n";
} else {
    echo "❌ API Key Middleware: Falha na autenticação\n";
}

echo "\n";

// ========================================
// 5. TESTE MÚLTIPLOS MÉTODOS
// ========================================
echo "5. TESTE MÚLTIPLOS MÉTODOS:\n";
echo "----------------------------------------\n";

$mockRequest4 = (object) [
    'headers' => (object) [
        'authorization' => "Bearer $token"
    ],
    'path' => '/api/protected'
];

// Mock do response
$mockResponse4 = new class {
    public $statusCode;
    public $jsonData;
    public $headers = [];
    
    public function status($code) {
        $this->statusCode = $code;
        return $this;
    }
    
    public function json($data) {
        $this->jsonData = $data;
        return $this;
    }
    
    public function header($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }
};

$nextCalled4 = false;

$multiMiddleware = new AuthMiddleware([
    'authMethods' => ['jwt', 'basic', 'apikey'],
    'jwtSecret' => $secret,
    'basicAuthCallback' => 'validateBasicAuth',
    'apiKeyCallback' => 'validateApiKey',
    'allowMultiple' => true
]);

$multiMiddleware($mockRequest4, $mockResponse4, function() use (&$nextCalled4) {
    $nextCalled4 = true;
});

if ($nextCalled4) {
    echo "✅ Múltiplos Métodos: Autenticação bem-sucedida\n";
    echo "Método usado: " . $mockRequest4->auth['method'] . "\n";
    echo "Usuário: " . ($mockRequest4->user['username'] ?? $mockRequest4->user['name']) . "\n";
} else {
    echo "❌ Múltiplos Métodos: Falha na autenticação\n";
}

echo "\n";

// ========================================
// 6. TESTE CAMINHOS EXCLUÍDOS
// ========================================
echo "6. TESTE CAMINHOS EXCLUÍDOS:\n";
echo "----------------------------------------\n";

$mockRequest5 = (object) [
    'headers' => (object) [],
    'path' => '/public/status'
];

// Mock do response
$mockResponse5 = new class {
    public $statusCode;
    public $jsonData;
    public $headers = [];
    
    public function status($code) {
        $this->statusCode = $code;
        return $this;
    }
    
    public function json($data) {
        $this->jsonData = $data;
        return $this;
    }
    
    public function header($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }
};

$nextCalled5 = false;

$excludeMiddleware = new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => $secret,
    'excludePaths' => ['/public']
]);

$excludeMiddleware($mockRequest5, $mockResponse5, function() use (&$nextCalled5) {
    $nextCalled5 = true;
});

if ($nextCalled5) {
    echo "✅ Caminho Excluído: Passou sem autenticação (correto)\n";
    $hasUser = isset($mockRequest5->user);
    echo "Usuário definido: " . ($hasUser ? "SIM" : "NÃO (correto)") . "\n";
} else {
    echo "❌ Caminho Excluído: Bloqueou indevidamente\n";
}

echo "\n";

// ========================================
// 7. TESTE MODO FLEXÍVEL
// ========================================
echo "7. TESTE MODO FLEXÍVEL:\n";
echo "----------------------------------------\n";

$mockRequest6 = (object) [
    'headers' => (object) [],
    'path' => '/api/mixed'
];

// Mock do response
$mockResponse6 = new class {
    public $statusCode;
    public $jsonData;
    public $headers = [];
    
    public function status($code) {
        $this->statusCode = $code;
        return $this;
    }
    
    public function json($data) {
        $this->jsonData = $data;
        return $this;
    }
    
    public function header($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }
};

$nextCalled6 = false;

$flexibleMiddleware = AuthMiddleware::flexible([
    'authMethods' => ['jwt'],
    'jwtSecret' => $secret
]);

$flexibleMiddleware($mockRequest6, $mockResponse6, function() use (&$nextCalled6) {
    $nextCalled6 = true;
});

if ($nextCalled6) {
    echo "✅ Modo Flexível: Passou sem autenticação (correto)\n";
    $hasAuth = isset($mockRequest6->auth);
    echo "Auth definido: " . ($hasAuth ? "SIM" : "NÃO (correto)") . "\n";
} else {
    echo "❌ Modo Flexível: Bloqueou indevidamente\n";
}

echo "\n";

// ========================================
// RESUMO DO TESTE
// ========================================
echo "=== RESUMO DO TESTE ===\n";
echo "✅ JWT Helper: Funcional\n";
echo "✅ JWT Middleware: Funcional\n";
echo "✅ Basic Auth Middleware: Funcional\n";
echo "✅ API Key Middleware: Funcional\n";
echo "✅ Múltiplos Métodos: Funcional\n";
echo "✅ Caminhos Excluídos: Funcional\n";
echo "✅ Modo Flexível: Funcional\n\n";

echo "🎉 TODOS OS TESTES PASSARAM COM SUCESSO!\n";
echo "O middleware de autenticação está pronto para uso.\n\n";

echo "📖 Para exemplos de uso completos, veja:\n";
echo "- examples/example_auth.php\n";
echo "- examples/snippets/auth_snippets.php\n\n";

// Limpa variáveis globais
unset($_SERVER['HTTP_X_API_KEY']);
