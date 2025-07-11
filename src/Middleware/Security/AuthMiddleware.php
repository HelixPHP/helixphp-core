<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware\Security;

use PivotPHP\Core\Http\Psr15\AbstractMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 Authentication Middleware
 */
class AuthMiddleware extends AbstractMiddleware
{
    private array $config;
    private array $publicPaths;

    /**
     * __construct method
     */
    public function __construct(array $config = [], array $publicPaths = [])
    {
        $this->config = array_merge(
            [
                'header' => 'Authorization',
                'prefix' => 'Bearer ',
                'secret' => '',
                'algorithm' => 'HS256',
            ],
            $config
        );

        $this->publicPaths = $publicPaths;
    }

    protected function shouldContinue(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();

        // Allow public paths
        foreach ($this->publicPaths as $publicPath) {
            if (fnmatch($publicPath, $path)) {
                return true;
            }
        }

        // Check for valid authentication
        return $this->isAuthenticated($request);
    }

    protected function getResponse(ServerRequestInterface $request): ResponseInterface
    {
        $jsonResponse = json_encode(['error' => 'Authentication required']);
        if ($jsonResponse === false) {
            $jsonResponse = '{"error": "Authentication required"}';
        }
        return new \PivotPHP\Core\Http\Psr7\Response(
            401,
            ['Content-Type' => 'application/json'],
            \PivotPHP\Core\Http\Psr7\Stream::createFromString($jsonResponse)
        );
    }

    /**
     * Process the request
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authMethods = $this->config['authMethods'] ?? ['jwt'];
        foreach ($authMethods as $method) {
            switch ($method) {
                case 'jwt':
                    if ($this->tryJwt($request, $handler)) {
                        return $handler->handle($request);
                    }
                    break;
                case 'basic':
                    if ($this->tryBasic($request, $handler)) {
                        return $handler->handle($request);
                    }
                    break;
                case 'bearer':
                    if ($this->tryBearer($request, $handler)) {
                        return $handler->handle($request);
                    }
                    break;
                case 'custom':
                    if ($this->tryCustom($request, $handler)) {
                        return $handler->handle($request);
                    }
                    break;
            }
        }
        return $this->unauthorizedResponse();
    }

    private function tryJwt(ServerRequestInterface $request, RequestHandlerInterface $handler): bool
    {
        $header = $request->getHeaderLine('Authorization');
        if (strpos($header, 'Bearer ') === 0) {
            $token = substr($header, 7);
            if (isset($this->config['jwtSecret'])) {
                try {
                    $jwtHelper = new \PivotPHP\Core\Authentication\JWTHelper();
                    $payload = $jwtHelper->decode($token, $this->config['jwtSecret']);
                    if ($payload) {
                        $request = $request->withAttribute('user', $payload);
                        return true;
                    }
                } catch (\Throwable $e) {
                    // Token inválido, não autentica
                    return false;
                }
            }
        }
        return false;
    }

    private function tryBasic(ServerRequestInterface $request, RequestHandlerInterface $handler): bool
    {
        $header = $request->getHeaderLine('Authorization');
        if (strpos($header, 'Basic ') === 0) {
            $decoded = base64_decode(substr($header, 6));
            if ($decoded && strpos($decoded, ':') !== false) {
                [$username, $password] = explode(':', $decoded, 2);
                if (isset($this->config['basicAuthCallback']) && is_callable($this->config['basicAuthCallback'])) {
                    $result = call_user_func($this->config['basicAuthCallback'], $username, $password);
                    if ($result) {
                        $request = $request->withAttribute('user', $result);
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function tryBearer(ServerRequestInterface $request, RequestHandlerInterface $handler): bool
    {
        $header = $request->getHeaderLine('Authorization');
        if (strpos($header, 'Bearer ') === 0) {
            $token = substr($header, 7);
            if (isset($this->config['bearerAuthCallback']) && is_callable($this->config['bearerAuthCallback'])) {
                $result = call_user_func($this->config['bearerAuthCallback'], $token);
                if ($result) {
                    $request = $request->withAttribute('user', $result);
                    return true;
                }
            }
        }
        return false;
    }

    private function tryCustom(ServerRequestInterface $request, RequestHandlerInterface $handler): bool
    {
        if (isset($this->config['customAuthCallback']) && is_callable($this->config['customAuthCallback'])) {
            $result = call_user_func($this->config['customAuthCallback'], $request);
            if ($result) {
                $request = $request->withAttribute('user', $result);
                return true;
            }
        }
        return false;
    }

    private function unauthorizedResponse(): ResponseInterface
    {
        $jsonResponse = json_encode(['error' => 'Unauthorized']);
        if ($jsonResponse === false) {
            $jsonResponse = '{"error": "Unauthorized"}';
        }
        return new \PivotPHP\Core\Http\Psr7\Response(
            401,
            ['Content-Type' => 'application/json'],
            \PivotPHP\Core\Http\Psr7\Stream::createFromString($jsonResponse)
        );
    }

    protected function before(ServerRequestInterface $request): ServerRequestInterface
    {
        $token = $this->extractToken($request);

        if ($token && $this->validateToken($token)) {
            $payload = $this->decodeToken($token);
            $request = $request->withAttribute('user', $payload);
            $request = $request->withAttribute('token', $token);
        }

        return $request;
    }

    private function isAuthenticated(ServerRequestInterface $request): bool
    {
        $token = $this->extractToken($request);
        return $token && $this->validateToken($token);
    }

    private function extractToken(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine($this->config['header']);

        if (!$header) {
            return null;
        }

        if (strpos($header, $this->config['prefix']) === 0) {
            return substr($header, strlen($this->config['prefix']));
        }

        return null;
    }

    private function validateToken(string $token): bool
    {
        try {
            // Simple validation - in production, use a proper JWT library
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }

            $payload = json_decode(base64_decode($parts[1]), true);

            if (!is_array($payload)) {
                return false;
            }

            // Check expiration
            if (isset($payload['exp']) && is_int($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }

            // Validate signature (simplified)
            $expectedSignature = hash_hmac('sha256', $parts[0] . '.' . $parts[1], $this->config['secret'], true);
            $actualSignature = base64_decode(strtr($parts[2], '-_', '+/'));

            return hash_equals($expectedSignature, $actualSignature);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function decodeToken(string $token): ?array
    {
        try {
            $parts = explode('.', $token);
            $decoded = json_decode(base64_decode($parts[1]), true);
            return is_array($decoded) ? $decoded : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
