<?php

namespace Express\Middleware\Security;

use Express\Middleware\Core\BaseMiddleware;
use Express\Authentication\JWTHelper;

/**
 * Middleware de autenticação automática para Express PHP.
 * Suporta JWT, Basic Auth, Bearer Token, API Key e outros métodos de autorização nativamente.
 */
class AuthMiddleware extends BaseMiddleware
{
    /**
     * @var array<string, mixed>
     */
    private array $options;

    /**
     * @param array<string, mixed> $options Opções de configuração
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge(
            [
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
                'allowMultiple' => false,
                'errorMessages' => [
                    'missing' => 'Authorization is required',
                    'invalid' => 'Invalid authentication credentials',
                    'expired' => 'Authentication token expired',
                    'malformed' => 'Malformed authorization header'
                ]
            ],
            $options
        );
    }

    public function handle($request, $response, callable $next)
    {
        // Verifica se o caminho atual deve ser excluído
        $currentPath = $request->path ?? $_SERVER['REQUEST_URI'];
        if (!is_string($currentPath)) {
            $currentPath = '/';
        }

        if (is_array($this->options['excludePaths'])) {
            foreach ($this->options['excludePaths'] as $excludePath) {
                if (is_string($excludePath) && strpos($currentPath, $excludePath) === 0) {
                    return $next();
                }
            }
        }

        $authResult = $this->authenticateRequest($request);

        if (!$authResult['success']) {
            if ($this->options['requireAuth']) {
                return $response->status($authResult['status'])->json(
                    [
                        'error' => true,
                        'message' => $authResult['message'],
                        'type' => 'AuthenticationError'
                    ]
                );
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

        return $next();
    }

    /**
     * Autentica a requisição usando todos os métodos configurados
     *
     * @param  mixed $request
     * @return array<string, mixed>
     */
    private function authenticateRequest($request): array
    {
        $errors = [];
        $methods = $this->options['authMethods'];

        // Tenta cada método de autenticação
        if (is_array($methods)) {
            foreach ($methods as $method) {
                if (!is_string($method)) {
                    continue;
                }

                switch ($method) {
                    case 'jwt':
                        $result = $this->authenticateJWT($request);
                        break;
                    case 'basic':
                        $result = $this->authenticateBasic($request);
                        break;
                    case 'bearer':
                        $result = $this->authenticateBearer($request);
                        break;
                    case 'apikey':
                        $result = $this->authenticateApiKey($request);
                        break;
                    case 'custom':
                        $result = $this->authenticateCustom($request);
                        break;
                    default:
                        $result = ['success' => false, 'message' => 'Unknown auth method: ' . $method];
                        break;
                }

                if ($result['success']) {
                    return array_merge($result, ['method' => $method]);
                }

                $errors[$method] = $result['message'];

            // Se não permite múltiplos métodos, retorna o primeiro erro
                if (!$this->options['allowMultiple']) {
                    break;
                }
            }
        }

        $invalidMessage = 'Authentication failed';
        if (
            isset($this->options['errorMessages']) &&
            is_array($this->options['errorMessages']) &&
            isset($this->options['errorMessages']['invalid'])
        ) {
            $invalidMessage = (string) $this->options['errorMessages']['invalid'];
        }

        return [
            'success' => false,
            'status' => 401,
            'message' => $invalidMessage,
            'errors' => $errors
        ];
    }

    /**
     * Autentica usando JWT
     *
     * @param mixed $request
     */
    private function authenticateJWT($request): array
    {
        $authHeader = $this->getAuthorizationHeader($request);

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return ['success' => false, 'message' => 'Missing or invalid Bearer token'];
        }

        $token = substr($authHeader, 7);

        if (!$this->options['jwtSecret'] || !is_string($this->options['jwtSecret'])) {
            return ['success' => false, 'message' => 'JWT secret not configured'];
        }

        try {
            $payload = JWTHelper::decode(
                $token,
                $this->options['jwtSecret'],
                [
                    'algorithm' => $this->options['jwtAlgorithm']
                ]
            );
            return ['success' => true, 'user' => $payload];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Invalid JWT token: ' . $e->getMessage()];
        }
    }

    /**
     * Autentica usando Basic Auth
     *
     * @param mixed $request
     */
    private function authenticateBasic($request): array
    {
        $authHeader = $this->getAuthorizationHeader($request);

        if (!$authHeader || !str_starts_with($authHeader, 'Basic ')) {
            return ['success' => false, 'message' => 'Missing or invalid Basic auth'];
        }

        $credentials = base64_decode(substr($authHeader, 6));
        if (!$credentials || !str_contains($credentials, ':')) {
            return ['success' => false, 'message' => 'Malformed Basic auth credentials'];
        }

        [$username, $password] = explode(':', $credentials, 2);

        if ($this->options['basicAuthCallback'] && is_callable($this->options['basicAuthCallback'])) {
            $callback = $this->options['basicAuthCallback'];
            $user = $callback($username, $password);
            if ($user) {
                return ['success' => true, 'user' => $user];
            }
        }

        return ['success' => false, 'message' => 'Invalid Basic auth credentials'];
    }

    /**
     * Autentica usando Bearer Token
     *
     * @param mixed $request
     */
    private function authenticateBearer($request): array
    {
        $authHeader = $this->getAuthorizationHeader($request);

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return ['success' => false, 'message' => 'Missing or invalid Bearer token'];
        }

        $token = substr($authHeader, 7);

        if ($this->options['bearerTokenCallback'] && is_callable($this->options['bearerTokenCallback'])) {
            $callback = $this->options['bearerTokenCallback'];
            $user = $callback($token);
            if ($user) {
                return ['success' => true, 'user' => $user];
            }
        }

        return ['success' => false, 'message' => 'Invalid Bearer token'];
    }

    /**
     * Autentica usando API Key
     *
     * @param mixed $request
     */
    private function authenticateApiKey($request): array
    {
        $apiKey = null;

        // Tenta obter API key do header
        $headerName = $this->options['headerName'];
        if (is_string($headerName) && isset($_SERVER['HTTP_' . str_replace('-', '_', strtoupper($headerName))])) {
            $serverKey = $_SERVER['HTTP_' . str_replace('-', '_', strtoupper($headerName))];
            $apiKey = is_string($serverKey) ? $serverKey : null;
        }

        // Tenta obter API key do query parameter
        if (!$apiKey && isset($_GET[$this->options['queryParam']])) {
            $queryKey = $_GET[$this->options['queryParam']];
            $apiKey = is_string($queryKey) ? $queryKey : null;
        }

        if (!$apiKey) {
            return ['success' => false, 'message' => 'Missing API key'];
        }

        if ($this->options['apiKeyCallback'] && is_callable($this->options['apiKeyCallback'])) {
            $callback = $this->options['apiKeyCallback'];
            $user = $callback($apiKey);
            if ($user) {
                return ['success' => true, 'user' => $user];
            }
        }

        return ['success' => false, 'message' => 'Invalid API key'];
    }

    /**
     * Autentica usando método customizado
     *
     * @param mixed $request
     */
    private function authenticateCustom($request): array
    {
        if ($this->options['customAuthCallback'] && is_callable($this->options['customAuthCallback'])) {
            $callback = $this->options['customAuthCallback'];
            $result = $callback($request);
            if (is_array($result)) {
                // Se já tem a estrutura esperada, retorna como está
                if (isset($result['success'])) {
                    return $result;
                }
                // Senão, trata como dados do usuário
                return ['success' => true, 'user' => $result];
            } elseif ($result) {
                return ['success' => true, 'user' => $result];
            }
        }

        return ['success' => false, 'message' => 'Custom authentication failed'];
    }

    /**
     * Obtém o header de Authorization
     *
     * @param mixed $request
     */
    private function getAuthorizationHeader($request): ?string
    {
        $result = $this->getHeader($request, 'authorization') ?? $this->getHeader($request, 'Authorization');
        return is_string($result) ? $result : null;
    }

    /**
     * Factory method para JWT authentication
     */
    public static function jwt(string $secret, string $algorithm = 'HS256'): self
    {
        return new self(
            [
                'authMethods' => ['jwt'],
                'jwtSecret' => $secret,
                'jwtAlgorithm' => $algorithm
            ]
        );
    }

    /**
     * Factory method para Basic authentication
     */
    public static function basic(callable $callback): self
    {
        return new self(
            [
                'authMethods' => ['basic'],
                'basicAuthCallback' => $callback
            ]
        );
    }

    /**
     * Factory method para Bearer token authentication
     */
    public static function bearer(callable $callback): self
    {
        return new self(
            [
                'authMethods' => ['bearer'],
                'bearerTokenCallback' => $callback
            ]
        );
    }

    /**
     * Factory method para custom authentication
     */
    public static function custom(callable $callback): self
    {
        return new self(
            [
                'authMethods' => ['custom'],
                'customAuthCallback' => $callback
            ]
        );
    }

    /**
     * Factory method para API Key authentication
     */
    public static function apiKey(
        callable $callback,
        string $headerName = 'X-API-Key',
        string $queryParam = 'api_key'
    ): self {
        return new self(
            [
                'authMethods' => ['apikey'],
                'apiKeyCallback' => $callback,
                'headerName' => $headerName,
                'queryParam' => $queryParam
            ]
        );
    }

    /**
     * Compatibilidade PSR-15: permite uso como middleware PSR-15
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Server\RequestHandlerInterface $handler): \Psr\Http\Message\ResponseInterface
    {
        // Adapta para o formato do Express
        $response = $this->handle($request, new \Express\Http\Psr7\Response(), function (
            $req = null,
            $res = null
        ) use ($handler, $request) {
            // Compatível com next() sem argumentos (caso customAuth)
            if ($req === null) {
                $req = $request;
            }
            return $handler->handle($req);
        });
        return $response;
    }
}
