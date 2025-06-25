<?php
namespace Express\Middlewares\Security;

/**
 * Middleware de autenticação automática para Express PHP.
 * Suporta JWT, Basic Auth, Bearer Token, API Key e outros métodos de autorização nativamente.
 */
class AuthMiddleware
{
    private $options;
    
    /**
     * @param array $options Opções de configuração:
     *   - jwtSecret: string (chave secreta para JWT)
     *   - jwtAlgorithm: string (algoritmo JWT, default: 'HS256')
     *   - basicAuthCallback: callable (callback para validar Basic Auth)
     *   - apiKeyCallback: callable (callback para validar API Key)
     *   - bearerTokenCallback: callable (callback para validar Bearer Token)
     *   - customAuthCallback: callable (callback customizado de autenticação)
     *   - authMethods: array (métodos de auth permitidos: 'jwt', 'basic', 'bearer', 'apikey', 'custom')
     *   - excludePaths: array (caminhos que devem ser excluídos da autenticação)
     *   - requireAuth: bool (se deve exigir autenticação, default: true)
     *   - userProperty: string (propriedade onde armazenar dados do usuário, default: 'user')
     *   - headerName: string (nome do header para API Key, default: 'X-API-Key')
     *   - queryParam: string (parâmetro de query para API Key, default: 'api_key')
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'jwtSecret' => null,
            'jwtAlgorithm' => 'HS256',
            'basicAuthCallback' => null,
            'apiKeyCallback' => null,
            'bearerTokenCallback' => null,
            'customAuthCallback' => null,
            'authMethods' => ['jwt', 'basic', 'bearer', 'apikey'],
            'excludePaths' => [],
            'requireAuth' => true,
            'userProperty' => 'user',
            'headerName' => 'X-API-Key',
            'queryParam' => 'api_key',
            'allowMultiple' => false, // permite múltiplos métodos
            'errorMessages' => [
                'missing' => 'Authorization is required',
                'invalid' => 'Invalid authentication credentials',
                'expired' => 'Authentication token expired',
                'malformed' => 'Malformed authorization header'
            ]
        ], $options);
    }

    public function __invoke($request, $response, $next)
    {
        // Verifica se o caminho atual deve ser excluído
        $currentPath = $request->path ?? $_SERVER['REQUEST_URI'];
        foreach ($this->options['excludePaths'] as $excludePath) {
            if (strpos($currentPath, $excludePath) === 0) {
                $next();
                return;
            }
        }

        $authResult = $this->authenticateRequest($request);
        
        if (!$authResult['success']) {
            if ($this->options['requireAuth']) {
                $response->status($authResult['status'])->json([
                    'error' => true,
                    'message' => $authResult['message'],
                    'type' => 'AuthenticationError'
                ]);
                return;
            }
        } else {
            // Adiciona dados do usuário à requisição
            $userProperty = $this->options['userProperty'];
            $request->$userProperty = $authResult['user'];
            
            // Adiciona informações de autenticação
            $request->auth = [
                'method' => $authResult['method'],
                'authenticated' => true,
                'user' => $authResult['user']
            ];
        }

        $next();
    }

    /**
     * Autentica a requisição usando todos os métodos configurados
     */
    private function authenticateRequest($request)
    {
        $errors = [];
        $methods = $this->options['authMethods'];
        
        // Tenta cada método de autenticação
        foreach ($methods as $method) {
            $result = $this->tryAuthMethod($method, $request);
            
            if ($result['success']) {
                return $result;
            }
            
            $errors[$method] = $result['message'];
            
            // Se não permite múltiplos métodos e encontrou headers, para por aqui
            if (!$this->options['allowMultiple'] && $result['found_headers']) {
                return $result;
            }
        }
        
        // Nenhum método funcionou
        return [
            'success' => false,
            'status' => 401,
            'message' => $this->options['errorMessages']['missing'],
            'errors' => $errors
        ];
    }

    /**
     * Tenta um método específico de autenticação
     */
    private function tryAuthMethod($method, $request)
    {
        switch ($method) {
            case 'jwt':
                return $this->authenticateJWT($request);
            case 'basic':
                return $this->authenticateBasic($request);
            case 'bearer':
                return $this->authenticateBearer($request);
            case 'apikey':
                return $this->authenticateAPIKey($request);
            case 'custom':
                return $this->authenticateCustom($request);
            default:
                return [
                    'success' => false,
                    'status' => 500,
                    'message' => "Unknown authentication method: $method",
                    'found_headers' => false
                ];
        }
    }

    /**
     * Autenticação JWT
     */
    private function authenticateJWT($request)
    {
        $authHeader = $this->getAuthorizationHeader($request);
        
        if (!$authHeader || !preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return [
                'success' => false,
                'status' => 401,
                'message' => $this->options['errorMessages']['missing'],
                'found_headers' => !empty($authHeader)
            ];
        }

        $token = $matches[1];
        
        try {
            // Verifica se a biblioteca JWT está disponível
            if (!class_exists('Firebase\JWT\JWT')) {
                throw new \Exception('JWT library not found. Install firebase/php-jwt');
            }
            
            $decoded = \Firebase\JWT\JWT::decode(
                $token, 
                new \Firebase\JWT\Key($this->options['jwtSecret'], $this->options['jwtAlgorithm'])
            );
            
            return [
                'success' => true,
                'method' => 'jwt',
                'user' => (array) $decoded,
                'token' => $token
            ];
            
        } catch (\Firebase\JWT\ExpiredException $e) {
            return [
                'success' => false,
                'status' => 401,
                'message' => $this->options['errorMessages']['expired'],
                'found_headers' => true
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 401,
                'message' => $this->options['errorMessages']['invalid'],
                'found_headers' => true,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Autenticação Basic Auth
     */
    private function authenticateBasic($request)
    {
        $authHeader = $this->getAuthorizationHeader($request);
        
        if (!$authHeader || !preg_match('/^Basic\s+(.+)$/i', $authHeader, $matches)) {
            return [
                'success' => false,
                'status' => 401,
                'message' => $this->options['errorMessages']['missing'],
                'found_headers' => !empty($authHeader)
            ];
        }

        $credentials = base64_decode($matches[1]);
        $parts = explode(':', $credentials, 2);
        
        if (count($parts) !== 2) {
            return [
                'success' => false,
                'status' => 401,
                'message' => $this->options['errorMessages']['malformed'],
                'found_headers' => true
            ];
        }

        list($username, $password) = $parts;
        
        // Usa callback personalizado se fornecido
        if ($this->options['basicAuthCallback']) {
            $callback = $this->options['basicAuthCallback'];
            $user = $callback($username, $password);
            
            if ($user) {
                return [
                    'success' => true,
                    'method' => 'basic',
                    'user' => $user,
                    'username' => $username
                ];
            }
        }
        
        return [
            'success' => false,
            'status' => 401,
            'message' => $this->options['errorMessages']['invalid'],
            'found_headers' => true
        ];
    }

    /**
     * Autenticação Bearer Token
     */
    private function authenticateBearer($request)
    {
        $authHeader = $this->getAuthorizationHeader($request);
        
        if (!$authHeader || !preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return [
                'success' => false,
                'status' => 401,
                'message' => $this->options['errorMessages']['missing'],
                'found_headers' => !empty($authHeader)
            ];
        }

        $token = $matches[1];
        
        // Usa callback personalizado se fornecido
        if ($this->options['bearerTokenCallback']) {
            $callback = $this->options['bearerTokenCallback'];
            $user = $callback($token);
            
            if ($user) {
                return [
                    'success' => true,
                    'method' => 'bearer',
                    'user' => $user,
                    'token' => $token
                ];
            }
        }
        
        return [
            'success' => false,
            'status' => 401,
            'message' => $this->options['errorMessages']['invalid'],
            'found_headers' => true
        ];
    }

    /**
     * Autenticação API Key
     */
    private function authenticateAPIKey($request)
    {
        // Procura a API Key no header
        $apiKey = $this->getHeader($request, $this->options['headerName']);
        
        // Se não encontrou no header, procura na query string
        if (!$apiKey) {
            $apiKey = $request->query($this->options['queryParam']) ?? $_GET[$this->options['queryParam']] ?? null;
        }
        
        if (!$apiKey) {
            return [
                'success' => false,
                'status' => 401,
                'message' => $this->options['errorMessages']['missing'],
                'found_headers' => false
            ];
        }
        
        // Usa callback personalizado se fornecido
        if ($this->options['apiKeyCallback']) {
            $callback = $this->options['apiKeyCallback'];
            $user = $callback($apiKey);
            
            if ($user) {
                return [
                    'success' => true,
                    'method' => 'apikey',
                    'user' => $user,
                    'api_key' => $apiKey
                ];
            }
        }
        
        return [
            'success' => false,
            'status' => 401,
            'message' => $this->options['errorMessages']['invalid'],
            'found_headers' => true
        ];
    }

    /**
     * Autenticação customizada
     */
    private function authenticateCustom($request)
    {
        if (!$this->options['customAuthCallback']) {
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Custom auth callback not configured',
                'found_headers' => false
            ];
        }
        
        $callback = $this->options['customAuthCallback'];
        $result = $callback($request);
        
        if (is_array($result) && isset($result['success'])) {
            if ($result['success']) {
                $result['method'] = 'custom';
            }
            return $result;
        }
        
        // Se o callback retornou dados do usuário diretamente
        if ($result) {
            return [
                'success' => true,
                'method' => 'custom',
                'user' => $result
            ];
        }
        
        return [
            'success' => false,
            'status' => 401,
            'message' => $this->options['errorMessages']['invalid'],
            'found_headers' => false
        ];
    }

    /**
     * Obtém o header Authorization
     */
    private function getAuthorizationHeader($request)
    {
        // Tenta diferentes formas de obter o header
        if (isset($request->headers) && method_exists($request->headers, 'getHeader')) {
            return $request->headers->getHeader('Authorization');
        }
        
        if (isset($request->headers->authorization)) {
            return $request->headers->authorization;
        }
        
        // Fallback para $_SERVER
        return $_SERVER['HTTP_AUTHORIZATION'] ?? 
               $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 
               (function_exists('apache_request_headers') ? (apache_request_headers()['Authorization'] ?? null) : null);
    }

    /**
     * Obtém um header específico
     */
    private function getHeader($request, $headerName)
    {
        // Tenta diferentes formas de obter o header
        if (isset($request->headers) && method_exists($request->headers, 'getHeader')) {
            return $request->headers->getHeader($headerName);
        }
        
        $serverKey = 'HTTP_' . str_replace('-', '_', strtoupper($headerName));
        return $_SERVER[$serverKey] ?? null;
    }

    /**
     * Métodos estáticos para criação rápida de instâncias
     */
    
    /**
     * Cria uma instância para autenticação JWT apenas
     */
    public static function jwt($secret, array $options = [])
    {
        return new self(array_merge($options, [
            'authMethods' => ['jwt'],
            'jwtSecret' => $secret
        ]));
    }

    /**
     * Cria uma instância para Basic Auth apenas
     */
    public static function basic(callable $callback, array $options = [])
    {
        return new self(array_merge($options, [
            'authMethods' => ['basic'],
            'basicAuthCallback' => $callback
        ]));
    }

    /**
     * Cria uma instância para Bearer Token apenas
     */
    public static function bearer(callable $callback, array $options = [])
    {
        return new self(array_merge($options, [
            'authMethods' => ['bearer'],
            'bearerTokenCallback' => $callback
        ]));
    }

    /**
     * Cria uma instância para API Key apenas
     */
    public static function apiKey(callable $callback, array $options = [])
    {
        return new self(array_merge($options, [
            'authMethods' => ['apikey'],
            'apiKeyCallback' => $callback
        ]));
    }

    /**
     * Cria uma instância para autenticação customizada
     */
    public static function custom(callable $callback, array $options = [])
    {
        return new self(array_merge($options, [
            'authMethods' => ['custom'],
            'customAuthCallback' => $callback
        ]));
    }

    /**
     * Cria uma instância flexível que aceita múltiplos métodos
     */
    public static function flexible(array $options = [])
    {
        return new self(array_merge([
            'allowMultiple' => true,
            'requireAuth' => false
        ], $options));
    }

    /**
     * Cria uma instância estrita que requer autenticação
     */
    public static function strict(array $options = [])
    {
        return new self(array_merge([
            'requireAuth' => true,
            'allowMultiple' => false
        ], $options));
    }
}
