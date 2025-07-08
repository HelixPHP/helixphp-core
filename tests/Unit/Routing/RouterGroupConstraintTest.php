<?php

namespace PivotPHP\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Routing\Router;

class RouterGroupConstraintTest extends TestCase
{
    protected function setUp(): void
    {
        Router::clear();
    }

    protected function tearDown(): void
    {
        Router::clear();
    }

    /**
     * @test
     */
    public function testGroupRoutesWithConstraints(): void
    {
        // Registra rotas no grupo /api
        Router::group(
            '/api',
            function () {
                Router::get(
                    '/users/:id<\d+>',
                    function () {
                        return 'user by id';
                    }
                );

                Router::get(
                    '/posts/:year<\d{4}>/:month<\d{2}>/:slug<[a-z0-9-]+>',
                    function () {
                        return 'post by date and slug';
                    }
                );

                Router::get(
                    '/products/:sku<[A-Z]{3}-\d{4}>',
                    function () {
                        return 'product by sku';
                    }
                );
            }
        );

        // Testa rota com constraint de dígitos
        $route1 = Router::identifyByGroup('GET', '/api/users/123');
        $this->assertNotNull($route1);
        $this->assertEquals('/api/users/:id<\d+>', $route1['path']);
        $this->assertArrayHasKey('matched_params', $route1);
        $this->assertEquals('123', $route1['matched_params']['id']);

        // Testa que não faz match com string
        $route2 = Router::identifyByGroup('GET', '/api/users/abc');
        $this->assertNull($route2);

        // Testa rota com múltiplos parâmetros e constraints
        $route3 = Router::identifyByGroup('GET', '/api/posts/2025/07/hello-world');
        $this->assertNotNull($route3);
        $this->assertEquals('/api/posts/:year<\d{4}>/:month<\d{2}>/:slug<[a-z0-9-]+>', $route3['path']);
        $this->assertArrayHasKey('matched_params', $route3);
        $this->assertEquals('2025', $route3['matched_params']['year']);
        $this->assertEquals('07', $route3['matched_params']['month']);
        $this->assertEquals('hello-world', $route3['matched_params']['slug']);

        // Testa rota com pattern de SKU
        $route4 = Router::identifyByGroup('GET', '/api/products/ABC-1234');
        $this->assertNotNull($route4);
        $this->assertEquals('/api/products/:sku<[A-Z]{3}-\d{4}>', $route4['path']);
        $this->assertArrayHasKey('matched_params', $route4);
        $this->assertEquals('ABC-1234', $route4['matched_params']['sku']);

        // Testa que não faz match com formato inválido
        $route5 = Router::identifyByGroup('GET', '/api/products/abc-1234');
        $this->assertNull($route5);
    }

    /**
     * @test
     */
    public function testNestedGroupsWithConstraints(): void
    {
        // Grupos aninhados
        Router::group(
            '/v1',
            function () {
                Router::group(
                    '/v1/admin',
                    function () {
                        Router::get(
                            '/v1/admin/users/:id<\d+>/edit',
                            function () {
                                return 'edit user';
                            }
                        );
                    }
                );
            }
        );

        $route = Router::identifyByGroup('GET', '/v1/admin/users/456/edit');
        $this->assertNotNull($route);
        $this->assertEquals('/v1/admin/users/:id<\d+>/edit', $route['path']);
        $this->assertArrayHasKey('matched_params', $route);
        $this->assertEquals('456', $route['matched_params']['id']);
    }

    /**
     * @test
     */
    public function testGroupIdentificationUsesCompiledPatterns(): void
    {
        Router::group(
            '/test',
            function () {
                Router::get(
                    '/test/item/:id<\d+>',
                    function () {
                        return 'item';
                    }
                );
            }
        );

        // Verifica que a rota tem os campos compilados
        $routes = Router::getRoutes();
        $lastRoute = end($routes);

        $this->assertArrayHasKey('pattern', $lastRoute);
        $this->assertArrayHasKey('parameters', $lastRoute);
        $this->assertArrayHasKey('has_parameters', $lastRoute);
        $this->assertTrue($lastRoute['has_parameters']);

        // Verifica que identifyByGroup funciona
        $identified = Router::identifyByGroup('GET', '/test/item/999');
        $this->assertNotNull($identified);
        $this->assertEquals('999', $identified['matched_params']['id']);
    }
}
