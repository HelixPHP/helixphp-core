<?php

declare(strict_types=1);

namespace Express\Http\Psr15\Middleware;

use Express\Http\Psr15\AbstractMiddleware;
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

    public function __construct(array $config = [], array $publicPaths = [])
    {
        $this->config = array_merge([
            'header' => 'Authorization',
            'prefix' => 'Bearer ',
            'secret' => '',
            'algorithm' => 'HS256',
        ], $config);

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
        return new \Express\Http\Psr7\Response(
            401,
            ['Content-Type' => 'application/json'],
            \Express\Http\Psr7\Stream::createFromString(
                $jsonResponse !== false ? $jsonResponse : '{"error": "Authentication required"}'
            )
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
