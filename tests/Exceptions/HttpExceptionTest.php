<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Exceptions;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Exceptions\HttpException;
use Exception;
use Throwable;

/**
 * Comprehensive test suite for HttpException class
 *
 * Tests HTTP error handling, status codes, headers, message generation,
 * serialization, and all exception functionality for HTTP contexts.
 */
class HttpExceptionTest extends TestCase
{
    // =========================================================================
    // BASIC INSTANTIATION TESTS
    // =========================================================================

    public function testBasicInstantiation(): void
    {
        $exception = new HttpException();

        $this->assertInstanceOf(HttpException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertInstanceOf(Throwable::class, $exception);
    }

    public function testDefaultValues(): void
    {
        $exception = new HttpException();

        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertEquals('Internal Server Error', $exception->getMessage());
        $this->assertEquals([], $exception->getHeaders());
        $this->assertEquals(500, $exception->getCode());
    }

    public function testCustomStatusCode(): void
    {
        $exception = new HttpException(404);

        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertEquals('Not Found', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    public function testCustomMessage(): void
    {
        $customMessage = 'Custom error message';
        $exception = new HttpException(400, $customMessage);

        $this->assertEquals(400, $exception->getStatusCode());
        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
    }

    public function testCustomHeaders(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Custom-Header' => 'custom-value'
        ];
        $exception = new HttpException(403, 'Forbidden', $headers);

        $this->assertEquals(403, $exception->getStatusCode());
        $this->assertEquals($headers, $exception->getHeaders());
    }

    public function testPreviousException(): void
    {
        $previous = new Exception('Previous exception');
        $exception = new HttpException(500, 'Server error', [], $previous);

        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertEquals('Server error', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    // =========================================================================
    // HTTP STATUS CODE TESTS
    // =========================================================================

    public function testCommonClientErrorCodes(): void
    {
        $clientErrors = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            409 => 'Conflict',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
        ];

        foreach ($clientErrors as $code => $expectedMessage) {
            $exception = new HttpException($code);
            $this->assertEquals($code, $exception->getStatusCode());
            $this->assertEquals($expectedMessage, $exception->getMessage());
            $this->assertEquals($code, $exception->getCode());
        }
    }

    public function testCommonServerErrorCodes(): void
    {
        $serverErrors = [
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
        ];

        foreach ($serverErrors as $code => $expectedMessage) {
            $exception = new HttpException($code);
            $this->assertEquals($code, $exception->getStatusCode());
            $this->assertEquals($expectedMessage, $exception->getMessage());
            $this->assertEquals($code, $exception->getCode());
        }
    }

    public function testSpecialStatusCodes(): void
    {
        $specialCodes = [
            418 => "I'm a teapot",
            426 => 'Upgrade Required',
            451 => 'Unavailable For Legal Reasons',
            507 => 'Insufficient Storage',
            511 => 'Network Authentication Required',
        ];

        foreach ($specialCodes as $code => $expectedMessage) {
            $exception = new HttpException($code);
            $this->assertEquals($code, $exception->getStatusCode());
            $this->assertEquals($expectedMessage, $exception->getMessage());
        }
    }

    public function testUnknownStatusCode(): void
    {
        $exception = new HttpException(999);

        $this->assertEquals(999, $exception->getStatusCode());
        $this->assertEquals('HTTP Error', $exception->getMessage());
    }

    public function testNegativeStatusCode(): void
    {
        $exception = new HttpException(-1);

        $this->assertEquals(-1, $exception->getStatusCode());
        $this->assertEquals('HTTP Error', $exception->getMessage());
    }

    // =========================================================================
    // HEADER MANAGEMENT TESTS
    // =========================================================================

    public function testEmptyHeaders(): void
    {
        $exception = new HttpException(404);
        $this->assertEquals([], $exception->getHeaders());
    }

    public function testMultipleHeaders(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache',
            'X-Rate-Limit' => '100',
            'X-Request-ID' => 'abc123',
        ];

        $exception = new HttpException(429, 'Too Many Requests', $headers);
        $this->assertEquals($headers, $exception->getHeaders());
    }

    public function testSetHeaders(): void
    {
        $exception = new HttpException(400);

        $newHeaders = [
            'Content-Type' => 'text/plain',
            'X-Error-Code' => 'VALIDATION_FAILED',
        ];

        $result = $exception->setHeaders($newHeaders);

        $this->assertSame($exception, $result); // Fluent interface
        $this->assertEquals($newHeaders, $exception->getHeaders());
    }

    public function testAddHeader(): void
    {
        $exception = new HttpException(401);

        $result = $exception->addHeader('WWW-Authenticate', 'Bearer');

        $this->assertSame($exception, $result); // Fluent interface
        $this->assertEquals(['WWW-Authenticate' => 'Bearer'], $exception->getHeaders());
    }

    public function testAddMultipleHeaders(): void
    {
        $exception = new HttpException(403);

        $exception->addHeader('X-Error', 'Forbidden')
                  ->addHeader('X-Retry-After', '60')
                  ->addHeader('Content-Type', 'application/json');

        $expected = [
            'X-Error' => 'Forbidden',
            'X-Retry-After' => '60',
            'Content-Type' => 'application/json',
        ];

        $this->assertEquals($expected, $exception->getHeaders());
    }

    public function testOverwriteHeaders(): void
    {
        $initialHeaders = ['X-Initial' => 'value1'];
        $exception = new HttpException(400, 'Bad Request', $initialHeaders);

        $newHeaders = ['X-New' => 'value2'];
        $exception->setHeaders($newHeaders);

        $this->assertEquals($newHeaders, $exception->getHeaders());
    }

    // =========================================================================
    // MESSAGE HANDLING TESTS
    // =========================================================================

    public function testEmptyMessageUsesDefault(): void
    {
        $exception = new HttpException(404, '');
        $this->assertEquals('Not Found', $exception->getMessage());
    }

    public function testCustomMessageOverridesDefault(): void
    {
        $customMessage = 'Resource not found in database';
        $exception = new HttpException(404, $customMessage);
        $this->assertEquals($customMessage, $exception->getMessage());
    }

    public function testLongCustomMessage(): void
    {
        $longMessage = str_repeat('This is a very long error message. ', 20);
        $exception = new HttpException(500, $longMessage);
        $this->assertEquals($longMessage, $exception->getMessage());
    }

    public function testUnicodeMessage(): void
    {
        $unicodeMessage = 'Erreur: Données invalides ñáéíóú 🚫';
        $exception = new HttpException(400, $unicodeMessage);
        $this->assertEquals($unicodeMessage, $exception->getMessage());
    }

    // =========================================================================
    // SERIALIZATION TESTS
    // =========================================================================

    public function testToArray(): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $exception = new HttpException(422, 'Validation failed', $headers);

        $array = $exception->toArray();

        $expected = [
            'error' => true,
            'status' => 422,
            'message' => 'Validation failed',
            'headers' => $headers,
        ];

        $this->assertEquals($expected, $array);
    }

    public function testToArrayWithEmptyHeaders(): void
    {
        $exception = new HttpException(500, 'Internal error');

        $array = $exception->toArray();

        $expected = [
            'error' => true,
            'status' => 500,
            'message' => 'Internal error',
            'headers' => [],
        ];

        $this->assertEquals($expected, $array);
    }

    public function testToJson(): void
    {
        $exception = new HttpException(404, 'Not found');

        $json = $exception->toJson();
        $decoded = json_decode($json, true);

        $expected = [
            'error' => true,
            'status' => 404,
            'message' => 'Not found',
            'headers' => [],
        ];

        $this->assertEquals($expected, $decoded);
        $this->assertJson($json);
    }

    public function testToJsonWithComplexData(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Error-Details' => 'Validation failed on field "email"',
            'X-Request-ID' => 'req_123abc',
        ];

        $exception = new HttpException(422, 'Validation error', $headers);

        $json = $exception->toJson();
        $decoded = json_decode($json, true);

        $this->assertEquals(true, $decoded['error']);
        $this->assertEquals(422, $decoded['status']);
        $this->assertEquals('Validation error', $decoded['message']);
        $this->assertEquals($headers, $decoded['headers']);
    }

    public function testToJsonFallback(): void
    {
        // Create an exception that might cause JSON encoding issues
        $headers = ['X-Binary-Data' => "\x00\x01\x02"]; // Binary data
        $exception = new HttpException(500, 'Error with binary', $headers);

        $json = $exception->toJson();

        // Should at least return a basic error JSON, even if encoding fails
        $this->assertIsString($json);
        $this->assertStringContainsString('error', $json);
    }

    // =========================================================================
    // EXCEPTION CHAINING TESTS
    // =========================================================================

    public function testExceptionChaining(): void
    {
        $rootCause = new Exception('Database connection failed');
        $middlewareCause = new Exception('Authentication failed', 0, $rootCause);
        $httpException = new HttpException(503, 'Service unavailable', [], $middlewareCause);

        $this->assertEquals('Service unavailable', $httpException->getMessage());
        $this->assertEquals(503, $httpException->getStatusCode());
        $this->assertSame($middlewareCause, $httpException->getPrevious());
        $this->assertSame($rootCause, $httpException->getPrevious()->getPrevious());
    }

    public function testExceptionChainingWithHttpException(): void
    {
        $previous = new HttpException(400, 'Bad request');
        $current = new HttpException(500, 'Internal error', [], $previous);

        $this->assertEquals(500, $current->getStatusCode());
        $this->assertEquals('Internal error', $current->getMessage());
        $this->assertInstanceOf(HttpException::class, $current->getPrevious());
        $this->assertEquals(400, $current->getPrevious()->getStatusCode());
    }

    // =========================================================================
    // REAL-WORLD SCENARIO TESTS
    // =========================================================================

    public function testAuthenticationError(): void
    {
        $headers = ['WWW-Authenticate' => 'Bearer realm="api"'];
        $exception = new HttpException(401, 'Authentication required', $headers);

        $this->assertEquals(401, $exception->getStatusCode());
        $this->assertEquals('Authentication required', $exception->getMessage());
        $this->assertEquals('Bearer realm="api"', $exception->getHeaders()['WWW-Authenticate']);
    }

    public function testRateLimitingError(): void
    {
        $headers = [
            'X-RateLimit-Limit' => '100',
            'X-RateLimit-Remaining' => '0',
            'X-RateLimit-Reset' => '1234567890',
            'Retry-After' => '60',
        ];

        $exception = new HttpException(429, 'Rate limit exceeded', $headers);

        $this->assertEquals(429, $exception->getStatusCode());
        $this->assertEquals('Rate limit exceeded', $exception->getMessage());
        $this->assertEquals('100', $exception->getHeaders()['X-RateLimit-Limit']);
        $this->assertEquals('60', $exception->getHeaders()['Retry-After']);
    }

    public function testValidationError(): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $exception = new HttpException(422, 'Validation failed: email is required', $headers);

        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertStringContainsString('Validation failed', $exception->getMessage());
        $this->assertEquals('application/json', $exception->getHeaders()['Content-Type']);
    }

    public function testMaintenanceMode(): void
    {
        $headers = [
            'Retry-After' => '3600',
            'Content-Type' => 'text/html',
        ];

        $exception = new HttpException(503, 'Service temporarily unavailable for maintenance', $headers);

        $this->assertEquals(503, $exception->getStatusCode());
        $this->assertStringContainsString('maintenance', $exception->getMessage());
        $this->assertEquals('3600', $exception->getHeaders()['Retry-After']);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    public function testZeroStatusCode(): void
    {
        $exception = new HttpException(0);

        $this->assertEquals(0, $exception->getStatusCode());
        $this->assertEquals('HTTP Error', $exception->getMessage());
    }

    public function testVeryLargeStatusCode(): void
    {
        $exception = new HttpException(99999);

        $this->assertEquals(99999, $exception->getStatusCode());
        $this->assertEquals('HTTP Error', $exception->getMessage());
    }

    public function testNullMessage(): void
    {
        // Test with empty string (null would cause TypeError)
        $exception = new HttpException(404, '');

        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertEquals('Not Found', $exception->getMessage()); // Should use default
    }

    public function testEmptyHeaderValue(): void
    {
        $headers = ['X-Empty' => ''];
        $exception = new HttpException(400, 'Bad request', $headers);

        $this->assertEquals('', $exception->getHeaders()['X-Empty']);
    }

    public function testHeaderCaseSensitivity(): void
    {
        $headers = [
            'content-type' => 'application/json',
            'Content-Type' => 'text/html', // This should overwrite the previous one
        ];

        $exception = new HttpException(400, 'Bad request', $headers);

        // PHP arrays are case-sensitive, so both keys will exist
        $this->assertArrayHasKey('content-type', $exception->getHeaders());
        $this->assertArrayHasKey('Content-Type', $exception->getHeaders());
    }

    // =========================================================================
    // PERFORMANCE AND MEMORY TESTS
    // =========================================================================

    public function testManyExceptions(): void
    {
        $exceptions = [];

        // Create many exceptions to test memory usage
        for ($i = 0; $i < 1000; $i++) {
            $statusCode = 400 + ($i % 100); // Vary status codes
            $message = "Error number {$i}";
            $headers = ['X-Error-ID' => (string)$i];

            $exceptions[] = new HttpException($statusCode, $message, $headers);
        }

        $this->assertCount(1000, $exceptions);

        // Test that they all work correctly
        $this->assertEquals(400, $exceptions[0]->getStatusCode());
        $this->assertEquals('Error number 999', $exceptions[999]->getMessage());
        $this->assertEquals('999', $exceptions[999]->getHeaders()['X-Error-ID']);
    }

    public function testLargeHeaders(): void
    {
        $largeHeaders = [];

        // Create many headers
        for ($i = 0; $i < 100; $i++) {
            $largeHeaders["X-Header-{$i}"] = str_repeat('value', 100); // Large header values
        }

        $exception = new HttpException(400, 'Large headers test', $largeHeaders);

        $this->assertEquals(100, count($exception->getHeaders()));
        $this->assertEquals(str_repeat('value', 100), $exception->getHeaders()['X-Header-0']);
    }

    // =========================================================================
    // INTEGRATION TESTS
    // =========================================================================

    public function testCompleteErrorHandlingWorkflow(): void
    {
        // Simulate a complete error handling workflow

        // 1. Create a root exception (e.g., database error)
        $dbError = new Exception('Connection timeout to database server');

        // 2. Create a service layer exception
        $serviceError = new Exception('Failed to fetch user data', 0, $dbError);

        // 3. Create HTTP exception for API response
        $headers = [
            'Content-Type' => 'application/json',
            'X-Error-Type' => 'DATABASE_ERROR',
            'X-Request-ID' => 'req_' . uniqid(),
            'Retry-After' => '30',
        ];

        $httpException = new HttpException(503, 'Service temporarily unavailable', $headers, $serviceError);

        // 4. Verify the complete chain
        $this->assertEquals(503, $httpException->getStatusCode());
        $this->assertEquals('Service temporarily unavailable', $httpException->getMessage());
        $this->assertEquals('application/json', $httpException->getHeaders()['Content-Type']);
        $this->assertEquals('DATABASE_ERROR', $httpException->getHeaders()['X-Error-Type']);

        // 5. Verify exception chain
        $this->assertSame($serviceError, $httpException->getPrevious());
        $this->assertSame($dbError, $httpException->getPrevious()->getPrevious());

        // 6. Test serialization
        $array = $httpException->toArray();
        $this->assertTrue($array['error']);
        $this->assertEquals(503, $array['status']);
        $this->assertEquals('Service temporarily unavailable', $array['message']);
        $this->assertArrayHasKey('X-Error-Type', $array['headers']);

        // 7. Test JSON output
        $json = $httpException->toJson();
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals(503, $decoded['status']);
        $this->assertTrue($decoded['error']);
    }

    public function testExceptionInExceptionHandling(): void
    {
        // Test exception safety when toJson() might fail

        // Create an exception with potentially problematic data
        $headers = ['X-Debug' => ['nested' => 'data']]; // This might cause JSON issues
        $exception = new HttpException(500, 'Complex error');

        // Set headers manually since constructor expects simple array
        $exception->setHeaders($headers);

        // toJson should handle any encoding issues gracefully
        $json = $exception->toJson();
        $this->assertIsString($json);

        // Even if JSON encoding fails, we should get a fallback
        $this->assertStringContainsString('error', $json);
    }
}
