#!/usr/bin/env php
<?php

/**
 * Script to switch between PSR-7 v1.x and v2.x compatibility
 */

if ($argc < 2) {
    echo "Usage: php switch-psr7-version.php [1|2]\n";
    echo "  1 - Switch to PSR-7 v1.x compatibility (no return types)\n";
    echo "  2 - Switch to PSR-7 v2.x compatibility (with return types)\n";
    exit(1);
}

$version = $argv[1];

if (!in_array($version, ['1', '2'])) {
    echo "Invalid version. Use 1 or 2.\n";
    exit(1);
}

$files = [
    __DIR__ . '/../src/Http/Psr7/Message.php',
    __DIR__ . '/../src/Http/Psr7/Request.php',
    __DIR__ . '/../src/Http/Psr7/Response.php',
    __DIR__ . '/../src/Http/Psr7/ServerRequest.php',
    __DIR__ . '/../src/Http/Psr7/Stream.php',
    __DIR__ . '/../src/Http/Psr7/Uri.php',
    __DIR__ . '/../src/Http/Psr7/UploadedFile.php',
];

// PSR-7 method signatures for each interface
$methodSignatures = [
    // MessageInterface
    'getProtocolVersion' => 'string',
    'withProtocolVersion' => 'MessageInterface',
    'getHeaders' => 'array',
    'hasHeader' => 'bool',
    'getHeader' => 'array',
    'getHeaderLine' => 'string',
    'withHeader' => 'MessageInterface',
    'withAddedHeader' => 'MessageInterface',
    'withoutHeader' => 'MessageInterface',
    'getBody' => 'StreamInterface',
    'withBody' => 'MessageInterface',
    
    // RequestInterface
    'getRequestTarget' => 'string',
    'withRequestTarget' => 'RequestInterface',
    'getMethod' => 'string',
    'withMethod' => 'RequestInterface',
    'getUri' => 'UriInterface',
    'withUri' => 'RequestInterface',
    
    // ServerRequestInterface
    'getServerParams' => 'array',
    'getCookieParams' => 'array',
    'withCookieParams' => 'ServerRequestInterface',
    'getQueryParams' => 'array',
    'withQueryParams' => 'ServerRequestInterface',
    'getUploadedFiles' => 'array',
    'withUploadedFiles' => 'ServerRequestInterface',
    'getParsedBody' => 'array|object|null',
    'withParsedBody' => 'ServerRequestInterface',
    'getAttributes' => 'array',
    'getAttribute' => 'mixed',
    'withAttribute' => 'ServerRequestInterface',
    'withoutAttribute' => 'ServerRequestInterface',
    
    // ResponseInterface
    'getStatusCode' => 'int',
    'withStatus' => 'ResponseInterface',
    'getReasonPhrase' => 'string',
    
    // StreamInterface
    '__toString' => 'string',
    'close' => 'void',
    'detach' => 'resource|null',
    'getSize' => '?int',
    'tell' => 'int',
    'eof' => 'bool',
    'isSeekable' => 'bool',
    'seek' => 'void',
    'rewind' => 'void',
    'isWritable' => 'bool',
    'write' => 'int',
    'isReadable' => 'bool',
    'read' => 'string',
    'getContents' => 'string',
    'getMetadata' => 'mixed',
    
    // UriInterface
    'getScheme' => 'string',
    'getAuthority' => 'string',
    'getUserInfo' => 'string',
    'getHost' => 'string',
    'getPort' => '?int',
    'getPath' => 'string',
    'getQuery' => 'string',
    'getFragment' => 'string',
    'withScheme' => 'UriInterface',
    'withUserInfo' => 'UriInterface',
    'withHost' => 'UriInterface',
    'withPort' => 'UriInterface',
    'withPath' => 'UriInterface',
    'withQuery' => 'UriInterface',
    'withFragment' => 'UriInterface',
    '__toString' => 'string',
    
    // UploadedFileInterface
    'getStream' => 'StreamInterface',
    'moveTo' => 'void',
    'getSize' => '?int',
    'getError' => 'int',
    'getClientFilename' => '?string',
    'getClientMediaType' => '?string',
];

$modifiedFiles = 0;

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    $modified = false;
    
    foreach ($methodSignatures as $method => $returnType) {
        if ($version === '1') {
            // Remove return types for v1.x
            $pattern = '/(\s*public\s+function\s+' . preg_quote($method, '/') . '\s*\([^)]*\))\s*:\s*[^\s{]+/';
            $replacement = '$1';
            
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $modified = true;
                
                // Add PHPDoc return type if needed for this specific method
                $content = preg_replace_callback(
                    '/(\s*\/\*\*)((?:(?!\*\/).)*?)(\*\/\s*public\s+function\s+' . preg_quote($method, '/') . '\s*\([^)]*\))/s',
                    function ($matches) use ($returnType) {
                        $docblock = $matches[1] . $matches[2];
                        // Only check for @return in this specific method's docblock
                        if (!preg_match('/@return/', $docblock)) {
                            // Add @return before the closing */
                            $phpDocType = str_replace(['|null', 'mixed'], ['|null', 'mixed'], $returnType);
                            $docblock = $matches[1] . $matches[2] . "\n     * @return " . $phpDocType . "\n     ";
                        }
                        return $docblock . $matches[3];
                    },
                    $content
                );
            }
        } else {
            // Add return types for v2.x
            $pattern = '/(\s*public\s+function\s+' . preg_quote($method, '/') . '\s*\([^)]*\))(?!\s*:)/';
            $replacement = '$1: ' . $returnType;
            
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $modified = true;
            }
        }
    }
    
    if ($modified) {
        file_put_contents($file, $content);
        echo "Modified: $file\n";
        $modifiedFiles++;
    }
}

// Update composer.json
$composerFile = __DIR__ . '/../composer.json';
$composer = json_decode(file_get_contents($composerFile), true);

if ($version === '1') {
    $composer['require']['psr/http-message'] = '^1.1';
    echo "Updated composer.json for PSR-7 v1.x\n";
} else {
    $composer['require']['psr/http-message'] = '^2.0';
    echo "Updated composer.json for PSR-7 v2.x\n";
}

file_put_contents($composerFile, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

echo "\nTotal files modified: $modifiedFiles\n";
echo "Now run: composer update psr/http-message\n";