<?php

namespace Express\Tests\Core;

use PHPUnit\Framework\TestCase;
use Express\Middleware\Security\CorsMiddleware;
use Express\Http\Response;

/**
 * Teste específico para verificar se o CorsMiddleware funciona
 * corretamente com objetos Response reais do Express.
 */
class CorsMiddlewareRealResponseTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset global state
        $_SERVER = [];
    }

    public function testCorsWithRealResponseObject(): void
    {
        $middleware = new CorsMiddleware([
            'origins' => ['https://example.com'],
            'methods' => ['GET', 'POST'],
            'credentials' => true
        ]);

        $request = (object) ['method' => 'GET'];
        $response = new Response();
        $nextCalled = false;

        // Define Origin header para teste
        $_SERVER['HTTP_ORIGIN'] = 'https://example.com';

        $result = $middleware($request, $response, function ($req, $res) use (&$nextCalled) {
            $nextCalled = true;
            return $res;
        });

        $this->assertTrue($nextCalled);
        $this->assertInstanceOf(Response::class, $result);

        // Verificar se os headers foram definidos corretamente
        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
        $this->assertEquals('https://example.com', $headers['Access-Control-Allow-Origin']);
        $this->assertArrayHasKey('Access-Control-Allow-Methods', $headers);
        $this->assertEquals('GET,POST', $headers['Access-Control-Allow-Methods']);
        $this->assertArrayHasKey('Access-Control-Allow-Credentials', $headers);
        $this->assertEquals('true', $headers['Access-Control-Allow-Credentials']);
    }

    public function testCorsWithRealResponseObjectOptionsRequest(): void
    {
        $middleware = new CorsMiddleware([
            'origins' => ['*'],
            'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'headers' => ['Content-Type', 'Authorization']
        ]);

        $request = (object) ['method' => 'OPTIONS'];
        $response = new Response();
        $nextCalled = false;

        $result = $middleware($request, $response, function ($req, $res) use (&$nextCalled) {
            $nextCalled = true;
            return $res;
        });

        // Para OPTIONS, next() não deve ser chamado
        $this->assertFalse($nextCalled);
        $this->assertInstanceOf(Response::class, $result);

        // Verificar se os headers CORS foram definidos
        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
        $this->assertEquals('*', $headers['Access-Control-Allow-Origin']);
        $this->assertArrayHasKey('Access-Control-Allow-Methods', $headers);
        $this->assertEquals('GET,POST,PUT,DELETE', $headers['Access-Control-Allow-Methods']);
        $this->assertArrayHasKey('Access-Control-Allow-Headers', $headers);
        $this->assertEquals('Content-Type,Authorization', $headers['Access-Control-Allow-Headers']);
    }

    public function testCorsPreventInfiniteRecursion(): void
    {
        // Este teste verifica especificamente que não há recursão infinita
        $middleware = new CorsMiddleware();
        $request = (object) ['method' => 'GET'];
        $response = new Response();

        // Se houver recursão infinita, este teste travará ou dará timeout
        $startTime = microtime(true);

        $middleware($request, $response, function ($req, $res) {
            return $res;
        });

        $executionTime = microtime(true) - $startTime;

        // Deve executar rapidamente (menos de 1 segundo)
        $this->assertLessThan(1.0, $executionTime, 'Middleware execution took too long, possible infinite recursion');

        // Verificar se pelo menos um header foi definido
        $headers = $response->getHeaders();
        $this->assertNotEmpty($headers, 'No headers were set');
        $this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
    }
}
