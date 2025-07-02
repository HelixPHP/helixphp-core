<?php

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use Express\Http\Psr15\Middleware\XssMiddleware;

class XssMiddlewareTest extends TestCase
{
    public function testXssDetection(): void
    {
        $cleanText = 'Hello world!';
        $xssScript = '<script>alert("xss")</script>';
        $xssOnclick = '<p onclick="alert(\'xss\')">Click me</p>';

        $this->assertFalse(XssMiddleware::containsXss($cleanText));
        $this->assertTrue(XssMiddleware::containsXss($xssScript));
        $this->assertTrue(XssMiddleware::containsXss($xssOnclick));
    }

    public function testSanitization(): void
    {
        $input = '<p>Safe text</p><script>alert("evil")</script>';
        $sanitized = XssMiddleware::sanitize($input);

        $this->assertStringNotContainsString('<script>', $sanitized);
        // Verificar se o conteúdo perigoso foi removido ou escapado
        $this->assertTrue(
            strpos($sanitized, 'alert("evil")') === false,
            'Conteúdo perigoso não foi removido corretamente.'
        );
        $this->assertStringContainsString('Safe text', $sanitized);
    }

    public function testSanitizationWithAllowedTags(): void
    {
        $input = '<p>Safe <strong>text</strong></p><script>alert("evil")</script>';
        $sanitized = XssMiddleware::sanitize($input, '<p><strong>');

        $this->assertStringNotContainsString('<script>', $sanitized);
        // Verificar se as tags permitidas foram preservadas (se a implementação suportar)
        $this->assertTrue(
            strpos($sanitized, '<p>') !== false ||
            strpos($sanitized, '&lt;p&gt;') !== false
        );
        $this->assertStringContainsString('Safe', $sanitized);
    }

    public function testUrlCleaning(): void
    {
        $safeUrl = 'https://example.com';
        $jsUrl = 'javascript:alert("evil")';
        $dataUrl = 'data:text/html,<script>alert("xss")</script>';

        $this->assertEquals($safeUrl, XssMiddleware::cleanUrl($safeUrl));

        // Verificar se URLs perigosas são limpos
        $cleanedJs = XssMiddleware::cleanUrl($jsUrl);
        $this->assertNotEquals($jsUrl, $cleanedJs, 'JavaScript URL should be cleaned');

        $cleanedData = XssMiddleware::cleanUrl($dataUrl);
        $this->assertNotEquals($dataUrl, $cleanedData, 'Data URL should be cleaned');
    }

    public function testComplexXssPatterns(): void
    {
        $patterns = [
            '<img src="x" onerror="alert(1)">',
            '<svg onload="alert(1)">',
            '<iframe src="javascript:alert(1)">',
            '"><script>alert(1)</script>',
            'javascript:alert(String.fromCharCode(88,83,83))'
        ];

        foreach ($patterns as $pattern) {
            $this->assertTrue(
                XssMiddleware::containsXss($pattern),
                "Failed to detect XSS in: $pattern"
            );

            $sanitized = XssMiddleware::sanitize($pattern);
            // Verificar se foi sanitizado (pode não detectar XSS em conteúdo escapado)
            $this->assertTrue(
                (
                    !XssMiddleware::containsXss($sanitized) ||
                    strpos($sanitized, '<') === false ||
                    strpos($sanitized, '&lt;') !== false
                ),
                "Sanitization failed for: $pattern"
            );
        }
    }
}
