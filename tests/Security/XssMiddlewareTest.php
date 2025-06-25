<?php

namespace Express\Tests\Security;

use PHPUnit\Framework\TestCase;
use Express\Middlewares\Security\XssMiddleware;

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
        $this->assertStringNotContainsString('alert', $sanitized);
        $this->assertStringContainsString('Safe text', $sanitized);
    }

    public function testSanitizationWithAllowedTags(): void
    {
        $input = '<p>Safe <strong>text</strong></p><script>alert("evil")</script>';
        $sanitized = XssMiddleware::sanitize($input, '<p><strong>');
        
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringContainsString('<p>', $sanitized);
        $this->assertStringContainsString('<strong>', $sanitized);
        $this->assertStringContainsString('Safe', $sanitized);
    }

    public function testUrlCleaning(): void
    {
        $safeUrl = 'https://example.com';
        $jsUrl = 'javascript:alert("evil")';
        $dataUrl = 'data:text/html,<script>alert("xss")</script>';
        
        $this->assertEquals($safeUrl, XssMiddleware::cleanUrl($safeUrl));
        $this->assertEquals('#', XssMiddleware::cleanUrl($jsUrl));
        $this->assertEquals('#', XssMiddleware::cleanUrl($dataUrl));
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
            $this->assertFalse(
                XssMiddleware::containsXss($sanitized),
                "Sanitization failed for: $pattern"
            );
        }
    }
}
