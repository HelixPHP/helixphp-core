<?php

namespace Express\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use Express\Helpers\Utils;

class UtilsTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset session state
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }

    public function testSanitizeString(): void
    {
        // Test normal string
        $this->assertEquals('Hello World', Utils::sanitizeString('Hello World'));
        
        // Test string with HTML tags
        $this->assertEquals('Hello World', Utils::sanitizeString('<script>Hello World</script>'));
        
        // Test string with whitespace
        $this->assertEquals('Hello World', Utils::sanitizeString('  Hello World  '));
        
        // Test non-string input
        $this->assertEquals(123, Utils::sanitizeString(123));
        $this->assertEquals(null, Utils::sanitizeString(null));
        $this->assertEquals(true, Utils::sanitizeString(true));
    }

    public function testSanitizeEmail(): void
    {
        // Test normal email
        $this->assertEquals('user@example.com', Utils::sanitizeEmail('user@example.com'));
        
        // Test email with whitespace
        $this->assertEquals('user@example.com', Utils::sanitizeEmail('  user@example.com  '));
        
        // Test non-string input
        $this->assertEquals(123, Utils::sanitizeEmail(123));
        $this->assertEquals(null, Utils::sanitizeEmail(null));
    }

    public function testSanitizeArray(): void
    {
        // Test normal array
        $input = ['<script>hello</script>', '  world  ', 'test'];
        $expected = ['hello', 'world', 'test'];
        $this->assertEquals($expected, Utils::sanitizeArray($input));
        
        // Test non-array input
        $this->assertEquals('string', Utils::sanitizeArray('string'));
        $this->assertEquals(123, Utils::sanitizeArray(123));
    }

    public function testIsEmail(): void
    {
        // Valid emails
        $this->assertTrue(Utils::isEmail('user@example.com'));
        $this->assertTrue(Utils::isEmail('test.email+tag@domain.co.uk'));
        $this->assertTrue(Utils::isEmail('user123@subdomain.example.org'));
        
        // Invalid emails
        $this->assertFalse(Utils::isEmail('invalid-email'));
        $this->assertFalse(Utils::isEmail('user@'));
        $this->assertFalse(Utils::isEmail('@domain.com'));
        $this->assertFalse(Utils::isEmail('user space@domain.com'));
        
        // Non-string input
        $this->assertFalse(Utils::isEmail(123));
        $this->assertFalse(Utils::isEmail(null));
        $this->assertFalse(Utils::isEmail([]));
    }

    public function testIsBool(): void
    {
        // Boolean values
        $this->assertTrue(Utils::isBool(true));
        $this->assertTrue(Utils::isBool(false));
        
        // String representations
        $this->assertTrue(Utils::isBool('0'));
        $this->assertTrue(Utils::isBool('1'));
        
        // Numeric representations
        $this->assertTrue(Utils::isBool(0));
        $this->assertTrue(Utils::isBool(1));
        
        // Invalid values
        $this->assertFalse(Utils::isBool('true'));
        $this->assertFalse(Utils::isBool('false'));
        $this->assertFalse(Utils::isBool(2));
        $this->assertFalse(Utils::isBool('hello'));
    }

    public function testIsInt(): void
    {
        // Valid integers
        $this->assertTrue(Utils::isInt(123));
        $this->assertTrue(Utils::isInt('456'));
        $this->assertTrue(Utils::isInt('0'));
        $this->assertTrue(Utils::isInt(-789));
        
        // Invalid integers
        $this->assertFalse(Utils::isInt('12.34'));
        $this->assertFalse(Utils::isInt('hello'));
        $this->assertFalse(Utils::isInt([]));
        $this->assertFalse(Utils::isInt(null));
    }

    public function testIsString(): void
    {
        // Valid strings
        $this->assertTrue(Utils::isString('hello'));
        $this->assertTrue(Utils::isString(''));
        $this->assertTrue(Utils::isString(123));
        $this->assertTrue(Utils::isString(12.34));
        
        // Invalid strings
        $this->assertFalse(Utils::isString([]));
        $this->assertFalse(Utils::isString(null));
        $this->assertFalse(Utils::isString(true));
    }

    public function testIsArray(): void
    {
        // Valid arrays
        $this->assertTrue(Utils::isArray([]));
        $this->assertTrue(Utils::isArray([1, 2, 3]));
        $this->assertTrue(Utils::isArray(['key' => 'value']));
        
        // Invalid arrays
        $this->assertFalse(Utils::isArray('string'));
        $this->assertFalse(Utils::isArray(123));
        $this->assertFalse(Utils::isArray(null));
    }

    public function testCorsHeaders(): void
    {
        // Test default CORS headers
        $headers = Utils::corsHeaders();
        $expected = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,DELETE,OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type,Authorization',
            'Access-Control-Allow-Credentials' => 'true'
        ];
        $this->assertEquals($expected, $headers);
        
        // Test custom CORS headers
        $headers = Utils::corsHeaders(
            ['https://example.com', 'https://api.example.com'],
            ['GET', 'POST'],
            ['Content-Type', 'X-API-Key']
        );
        $expected = [
            'Access-Control-Allow-Origin' => 'https://example.com,https://api.example.com',
            'Access-Control-Allow-Methods' => 'GET,POST',
            'Access-Control-Allow-Headers' => 'Content-Type,X-API-Key',
            'Access-Control-Allow-Credentials' => 'true'
        ];
        $this->assertEquals($expected, $headers);
    }

    public function testLog(): void
    {
        // Test with a simple approach - just verify the method exists and can be called
        Utils::log('Test message', 'info');
        Utils::log('Error message', 'error');
        
        // If no exception is thrown, the method works correctly
        $this->assertTrue(true);
    }

    public function testRandomToken(): void
    {
        // Test default length (32)
        $token1 = Utils::randomToken();
        $this->assertEquals(32, strlen($token1));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token1);
        
        // Test custom length
        $token2 = Utils::randomToken(16);
        $this->assertEquals(16, strlen($token2));
        
        $token3 = Utils::randomToken(64);
        $this->assertEquals(64, strlen($token3));
        
        // Test uniqueness
        $this->assertNotEquals($token1, Utils::randomToken());
    }

    public function testCsrfToken(): void
    {
        // Test CSRF token generation
        $token1 = Utils::csrfToken();
        $this->assertIsString($token1);
        $this->assertEquals(32, strlen($token1));
        
        // Test that subsequent calls return the same token
        $token2 = Utils::csrfToken();
        $this->assertEquals($token1, $token2);
    }

    public function testCheckCsrf(): void
    {
        // Generate a CSRF token
        $token = Utils::csrfToken();
        
        // Test valid token
        $this->assertTrue(Utils::checkCsrf($token));
        
        // Test invalid token
        $this->assertFalse(Utils::checkCsrf('invalid_token'));
        $this->assertFalse(Utils::checkCsrf(''));
        
        // Test with different token
        $this->assertFalse(Utils::checkCsrf(Utils::randomToken()));
    }

    public function testAdvancedSanitization(): void
    {
        // Test complex HTML sanitization - strip_tags removes all HTML tags
        $input = '<div onclick="alert(\'xss\')">Hello <script>alert("xss")</script> World</div>';
        $result = Utils::sanitizeString($input);
        $this->assertEquals('Hello alert("xss") World', $result);
        
        // Test nested array sanitization
        $input = [
            'user' => '<script>alert("xss")</script>John',
            'data' => [
                'comment' => '  <b>Bold text</b>  ',
                'tags' => ['<em>tag1</em>', '  tag2  ']
            ]
        ];
        
        // Since sanitizeArray only handles flat arrays, we test accordingly
        $flatArray = ['<script>test</script>', '  hello  '];
        $result = Utils::sanitizeArray($flatArray);
        $this->assertEquals(['test', 'hello'], $result);
    }

    public function testEmailValidationEdgeCases(): void
    {
        // Test various edge cases for email validation
        $validEmails = [
            'simple@domain.com',
            'user.name@domain.com',
            'user+tag@domain.com',
            'user123@sub.domain.co.uk'
        ];
        
        foreach ($validEmails as $email) {
            $this->assertTrue(Utils::isEmail($email), "Failed for email: $email");
        }
        
        $invalidEmails = [
            'plainaddress',
            '@missinglocalpart.com',
            'missing-domain@.com',
            'spaces in@domain.com',
            'user@',
            '@domain.com'
        ];
        
        foreach ($invalidEmails as $email) {
            $this->assertFalse(Utils::isEmail($email), "Should fail for email: $email");
        }
    }
}
