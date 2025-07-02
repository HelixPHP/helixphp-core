<?php
// Snippets para configuração rápida de autenticação
use Express\Http\Psr15\Middleware\AuthMiddleware;
use Express\Helpers\JWTHelper;

echo "=== SNIPPETS DE AUTENTICAÇÃO EXPRESS PHP ===\n\n";

// ========================================
// 1. JWT SIMPLES
// ========================================
echo "1. JWT Simples:\n";
echo "```php\n";
echo "// Configuração básica JWT\n";
echo "\$app->use(new AuthMiddleware([\n    'authMethods' => ['jwt'],\n    'jwtSecret' => 'sua_chave_secreta'\n]));\n\n";
echo "// Rota de login\n";
echo "\$app->post('/login', function(\$req, \$res) {\n";
echo "    // Valida credenciais...\n";
echo "    \$token = JWTHelper::encode([\n";
echo "        'user_id' => \$userId,\n";
echo "        'role' => \$userRole\n";
echo "    ], 'sua_chave_secreta');\n";
echo "    \n";
echo "    \$res->json(['token' => \$token]);\n";
echo "});\n";
echo "```\n\n";

// ========================================
// 2. BASIC AUTH SIMPLES
// ========================================
echo "2. Basic Auth Simples:\n";
echo "```php\n";
echo "// Função de validação\n";
echo "function validateUser(\$username, \$password) {\n";
echo "    \$users = ['admin' => 'password123'];\n";
echo "    return isset(\$users[\$username]) && \$users[\$username] === \$password\n";
echo "        ? ['id' => 1, 'username' => \$username] : false;\n";
echo "}\n\n";
echo "// Aplicar middleware\n";
echo "\$app->use(new AuthMiddleware([\n    'authMethods' => ['basic'],\n    'basicAuthCallback' => 'validateUser'\n]));\n";
echo "```\n\n";

// ========================================
// 3. API KEY SIMPLES
// ========================================
echo "3. API Key Simples:\n";
echo "```php\n";
echo "// Função de validação API Key\n";
echo "function validateApiKey(\$key) {\n";
echo "    \$validKeys = ['key123' => ['name' => 'App Mobile']];\n";
echo "    return \$validKeys[\$key] ?? false;\n";
echo "}\n\n";
echo "// Aplicar middleware\n";
echo "\$app->use(new AuthMiddleware([\n    'authMethods' => ['bearer'],\n    'bearerAuthCallback' => 'validateApiKey'\n]));\n";
echo "// Usar: Header Authorization: Bearer key123\n";
echo "```\n\n";

// ========================================
// 4. MÚLTIPLOS MÉTODOS
// ========================================
echo "4. Múltiplos Métodos de Auth:\n";
echo "```php\n";
echo "\$app->use(new AuthMiddleware([\n    'authMethods' => ['jwt', 'basic', 'bearer'],\n    'jwtSecret' => 'sua_chave_jwt',\n    'basicAuthCallback' => 'validateUser',\n    'bearerAuthCallback' => 'validateApiKey',\n    'allowMultiple' => true,\n    'excludePaths' => ['/public', '/login']\n]));\n";
echo "```\n\n";

// ========================================
// 5. AUTENTICAÇÃO POR ROTA
// ========================================
echo "5. Autenticação Específica por Rota:\n";
echo "```php\n";
echo "// Rota apenas com JWT\n";
echo "\$app->get('/jwt-only', new AuthMiddleware([\n    'authMethods' => ['jwt'],\n    'jwtSecret' => 'chave_secreta'\n]), function(\$req, \$res) {\n    \$res->json(['user' => \$req->user]);\n});\n\n";
echo "// Rota apenas com API Key\n";
echo "\$app->get('/api-only', new AuthMiddleware([\n    'authMethods' => ['bearer'],\n    'bearerAuthCallback' => 'validateApiKey'\n]), function(\$req, \$res) {\n    \$res->json(['api_user' => \$req->user]);\n});\n";
echo "```\n\n";

// ========================================
// 6. VALIDAÇÃO DE ROLES
// ========================================
echo "7. Validação de Roles/Permissões:\n";
echo "```php\n";
echo "// Middleware para verificar role de admin\n";
echo "function requireAdmin(\$req, \$res, \$next) {\n";
echo "    if (!\$req->user || \$req->user['role'] !== 'admin') {\n";
echo "        \$res->status(403)->json(['error' => 'Admin required']);\n";
echo "        return;\n";
echo "    }\n";
echo "    \$next();\n";
echo "}\n\n";

echo "// Usar em rotas\n";
echo "\$app->get('/admin/panel',\n";
echo "    AuthMiddleware::jwt('chave_secreta'),\n";
echo "    'requireAdmin',\n";
echo "    function(\$req, \$res) {\n";
echo "        \$res->json(['message' => 'Admin panel']);\n";
echo "    }\n";
echo ");\n";
echo "```\n\n";

// ========================================
// 7. REFRESH TOKEN
// ========================================
echo "8. Sistema de Refresh Token:\n";
echo "```php\n";
echo "// Login com refresh token\n";
echo "\$app->post('/login', function(\$req, \$res) {\n";
echo "    // Validar credenciais...\n";
echo "    \n";
echo "    \$accessToken = JWTHelper::encode([\n";
echo "        'user_id' => \$userId\n";
echo "    ], 'jwt_secret', ['expiresIn' => 900]); // 15 min\n";
echo "    \n";
echo "    \$refreshToken = JWTHelper::createRefreshToken(\n";
echo "        \$userId, \n";
echo "        'refresh_secret'\n";
echo "    ); // 30 dias\n";
echo "    \n";
echo "    \$res->json([\n";
echo "        'access_token' => \$accessToken,\n";
echo "        'refresh_token' => \$refreshToken\n";
echo "    ]);\n";
echo "});\n\n";

echo "// Renovar token\n";
echo "\$app->post('/refresh', function(\$req, \$res) {\n";
echo "    \$refreshToken = \$req->body['refresh_token'];\n";
echo "    \$payload = JWTHelper::validateRefreshToken(\$refreshToken, 'refresh_secret');\n";
echo "    \n";
echo "    if (\$payload) {\n";
echo "        \$newToken = JWTHelper::encode([\n";
echo "            'user_id' => \$payload['user_id']\n";
echo "        ], 'jwt_secret');\n";
echo "        \$res->json(['access_token' => \$newToken]);\n";
echo "    } else {\n";
echo "        \$res->status(401)->json(['error' => 'Invalid refresh token']);\n";
echo "    }\n";
echo "});\n";
echo "```\n\n";

// ========================================
// 8. CONFIGURAÇÕES AVANÇADAS
// ========================================
echo "9. Configurações Avançadas:\n";
echo "```php\n";
echo "\$app->use(new AuthMiddleware([\n";
echo "    'authMethods' => ['jwt', 'basic', 'apikey'],\n";
echo "    'jwtSecret' => getenv('JWT_SECRET'),\n";
echo "    'jwtAlgorithm' => 'HS256',\n";
echo "    'basicAuthCallback' => 'validateBasicAuth',\n";
echo "    'apiKeyCallback' => 'validateApiKey',\n";
echo "    'headerName' => 'X-API-Key', // para API Key\n";
echo "    'queryParam' => 'api_key',   // parâmetro query\n";
echo "    'excludePaths' => ['/health', '/docs'],\n";
echo "    'requireAuth' => true,\n";
echo "    'userProperty' => 'currentUser', // \$req->currentUser\n";
echo "    'allowMultiple' => false,\n";
echo "    'errorMessages' => [\n";
echo "        'missing' => 'Token de acesso requerido',\n";
echo "        'invalid' => 'Token inválido',\n";
echo "        'expired' => 'Token expirado'\n";
echo "    ]\n";
echo "]));\n";
echo "```\n\n";

// ========================================
// 9. EXEMPLOS DE TESTE
// ========================================
echo "10. Como Testar (cURL):\n";
echo "```bash\n";
echo "# JWT\n";
echo "curl -H \"Authorization: Bearer seu_jwt_token\" \\\n";
echo "     http://localhost/api/protected\n\n";

echo "# Basic Auth\n";
echo "curl -u admin:password123 \\\n";
echo "     http://localhost/api/protected\n\n";

echo "# API Key (Header)\n";
echo "curl -H \"X-API-Key: key123456\" \\\n";
echo "     http://localhost/api/protected\n\n";

echo "# API Key (Query)\n";
echo "curl \"http://localhost/api/protected?api_key=key123456\"\n\n";

echo "# Bearer Token\n";
echo "curl -H \"Authorization: Bearer custom_token\" \\\n";
echo "     http://localhost/api/protected\n";
echo "```\n\n";

echo "=== FIM DOS SNIPPETS ===\n\n";

echo "💡 DICAS:\n";
echo "- Use JWT para SPAs e apps mobile\n";
echo "- Use API Key para integrações de serviços\n";
echo "- Use Basic Auth para casos simples\n";
echo "- Combine métodos para máxima flexibilidade\n";
echo "- Sempre use HTTPS em produção!\n\n";

echo "📚 Para mais exemplos, veja: examples/example_auth.php\n";
