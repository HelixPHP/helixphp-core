#!/usr/bin/env php
<?php

/**
 * Script to adapt PSR-7 implementations for v1.x compatibility
 * Removes return type declarations from PSR-7 interface methods
 */

$files = [
    __DIR__ . '/../src/Http/Psr7/Message.php',
    __DIR__ . '/../src/Http/Psr7/Request.php',
    __DIR__ . '/../src/Http/Psr7/Response.php',
    __DIR__ . '/../src/Http/Psr7/ServerRequest.php',
    __DIR__ . '/../src/Http/Psr7/Stream.php',
    __DIR__ . '/../src/Http/Psr7/Uri.php',
    __DIR__ . '/../src/Http/Psr7/UploadedFile.php',
];

$psr7Methods = [
    // MessageInterface
    'getProtocolVersion',
    'withProtocolVersion',
    'getHeaders',
    'hasHeader',
    'getHeader',
    'getHeaderLine',
    'withHeader',
    'withAddedHeader',
    'withoutHeader',
    'getBody',
    'withBody',
    
    // RequestInterface
    'getRequestTarget',
    'withRequestTarget',
    'getMethod',
    'withMethod',
    'getUri',
    'withUri',
    
    // ServerRequestInterface
    'getServerParams',
    'getCookieParams',
    'withCookieParams',
    'getQueryParams',
    'withQueryParams',
    'getUploadedFiles',
    'withUploadedFiles',
    'getParsedBody',
    'withParsedBody',
    'getAttributes',
    'getAttribute',
    'withAttribute',
    'withoutAttribute',
    
    // ResponseInterface
    'getStatusCode',
    'withStatus',
    'getReasonPhrase',
    
    // StreamInterface
    '__toString',
    'close',
    'detach',
    'getSize',
    'tell',
    'eof',
    'isSeekable',
    'seek',
    'rewind',
    'isWritable',
    'write',
    'isReadable',
    'read',
    'getContents',
    'getMetadata',
    
    // UriInterface
    'getScheme',
    'getAuthority',
    'getUserInfo',
    'getHost',
    'getPort',
    'getPath',
    'getQuery',
    'getFragment',
    'withScheme',
    'withUserInfo',
    'withHost',
    'withPort',
    'withPath',
    'withQuery',
    'withFragment',
    
    // UploadedFileInterface
    'getStream',
    'moveTo',
    'getSize',
    'getError',
    'getClientFilename',
    'getClientMediaType',
];

$modifiedFiles = 0;

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    foreach ($psr7Methods as $method) {
        // Match public function with return type and capture it
        $pattern = '/(\s*public\s+function\s+' . preg_quote($method, '/') . '\s*\([^)]*\))\s*:\s*[^\s{]+/';
        $replacement = '$1';
        
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "Modified: $file\n";
        $modifiedFiles++;
    }
}

echo "\nTotal files modified: $modifiedFiles\n";

// Also create a PHPDoc helper to maintain type information
$docHelperContent = <<<'PHP'
<?php

/**
 * This file contains PHPDoc annotations for PSR-7 v1.x compatibility
 * 
 * When using PSR-7 v1.x, return types are not declared in the interface,
 * but we can still provide type information through PHPDoc for IDE support
 * and static analysis tools.
 */

namespace PivotPHP\Core\Http\Psr7;

/**
 * @method string getProtocolVersion()
 * @method MessageInterface withProtocolVersion(string $version)
 * @method array getHeaders()
 * @method bool hasHeader(string $name)
 * @method array getHeader(string $name)
 * @method string getHeaderLine(string $name)
 * @method MessageInterface withHeader(string $name, $value)
 * @method MessageInterface withAddedHeader(string $name, $value)
 * @method MessageInterface withoutHeader(string $name)
 * @method StreamInterface getBody()
 * @method MessageInterface withBody(StreamInterface $body)
 */
trait Psr7V1CompatibilityTrait {}
PHP;

file_put_contents(__DIR__ . '/../src/Http/Psr7/Psr7V1CompatibilityTrait.php', $docHelperContent);
echo "Created PSR-7 v1.x compatibility trait\n";