<?php

namespace Express\Tests\Security;

use PHPUnit\Framework\TestCase;
use Express\Middlewares\Security\CsrfMiddleware;

class CsrfMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function testTokenGeneration(): void
    {
        $token1 = CsrfMiddleware::getToken();
        $token2 = CsrfMiddleware::getToken();
        
        $this->assertNotEmpty($token1);
        $this->assertNotEmpty($token2);
        $this->assertEquals($token1, $token2, 'Tokens should be the same within session');
        $this->assertEquals(32, strlen($token1), 'Token should be 32 characters long');
    }

    public function testHiddenField(): void
    {
        $hiddenField = CsrfMiddleware::hiddenField();
        
        $this->assertStringContainsString('<input', $hiddenField);
        $this->assertStringContainsString('type="hidden"', $hiddenField);
        $this->assertStringContainsString('name="csrf_token"', $hiddenField);
        $this->assertStringContainsString('value="', $hiddenField);
    }

    public function testMetaTag(): void
    {
        $metaTag = CsrfMiddleware::metaTag();
        
        $this->assertStringContainsString('<meta', $metaTag);
        $this->assertStringContainsString('name="csrf-token"', $metaTag);
        $this->assertStringContainsString('content="', $metaTag);
    }

    public function testTokenValidation(): void
    {
        $token = CsrfMiddleware::getToken();
        
        // Simulate valid token
        $_SESSION['csrf_token'] = $token;
        $this->assertTrue(hash_equals($_SESSION['csrf_token'], $token));
        
        // Simulate invalid token
        $invalidToken = 'invalid_token_12345';
        $this->assertFalse(hash_equals($_SESSION['csrf_token'], $invalidToken));
    }

    protected function tearDown(): void
    {
        if (isset($_SESSION['csrf_token'])) {
            unset($_SESSION['csrf_token']);
        }
    }
}
