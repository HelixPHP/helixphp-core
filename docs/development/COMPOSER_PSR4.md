# Composer PSR-4 Autoloading - Express PHP Framework

## 🚀 Visão Geral

O Express PHP Framework utiliza o padrão PSR-4 para autoloading, garantindo carregamento eficiente e organizado das classes.

## 📋 Configuração PSR-4

### composer.json Principal
```json
{
    "name": "express-php/framework",
    "description": "High-performance PHP framework with automatic caching and advanced middleware",
    "type": "library",
    "license": "MIT",
    "authors": [
        {
            "name": "Express PHP Team",
            "email": "team@express-php.com"
        }
    ],
    "require": {
        "php": ">=8.1"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "phpstan/phpstan": "^1.10",
        "squizlabs/php_codesniffer": "^3.7"
    },
    "autoload": {
        "psr-4": {
            "Express\\": "src/"
        },
        "files": [
            "src/Support/helpers.php"
        ]
    },
    "autoload-dev": {
        "psr-4": {
            "Express\\Tests\\": "tests/"
        }
    },
    "config": {
        "optimize-autoloader": true,
        "classmap-authoritative": true,
        "sort-packages": true
    },
    "scripts": {
        "test": "phpunit",
        "test-coverage": "phpunit --coverage-html reports/coverage",
        "analyse": "phpstan analyse",
        "style-check": "phpcs --standard=PSR12 src/",
        "style-fix": "phpcbf --standard=PSR12 src/",
        "validate-project": "./scripts/validate_project.php"
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

## 🏗️ Estrutura de Namespaces

### Mapeamento Completo
```
Express\                          → src/
├── ApiExpress                    → src/ApiExpress.php
├── Core\                         → src/Core/
│   ├── Application               → src/Core/Application.php
│   ├── Container                 → src/Core/Container.php
│   └── Config                    → src/Core/Config.php
├── Routing\                      → src/Routing/
│   ├── Router                    → src/Routing/Router.php
│   └── RouteGroup                → src/Routing/RouteGroup.php
├── Middleware\                   → src/Middleware/
│   ├── Core\                     → src/Middleware/Core/
│   │   ├── BaseMiddleware        → src/Middleware/Core/BaseMiddleware.php
│   │   └── MiddlewareStack       → src/Middleware/Core/MiddlewareStack.php
│   └── Security\                 → src/Middleware/Security/
│       ├── CorsMiddleware        → src/Middleware/Security/CorsMiddleware.php
│       ├── AuthMiddleware        → src/Middleware/Security/AuthMiddleware.php
│       └── RateLimitMiddleware   → src/Middleware/Security/RateLimitMiddleware.php
├── Http\                         → src/Http/
│   ├── Request                   → src/Http/Request.php
│   ├── Response                  → src/Http/Response.php
│   └── HeaderRequest             → src/Http/HeaderRequest.php
├── Authentication\               → src/Authentication/
│   └── JWTHelper                 → src/Authentication/JWTHelper.php
├── Cache\                        → src/Cache/
│   ├── CacheInterface            → src/Cache/CacheInterface.php
│   ├── FileCache                 → src/Cache/FileCache.php
│   └── MemoryCache               → src/Cache/MemoryCache.php
├── Database\                     → src/Database/
│   └── Database                  → src/Database/Database.php
├── Events\                       → src/Events/
│   ├── Event                     → src/Events/Event.php
│   └── EventDispatcher           → src/Events/EventDispatcher.php
├── Exceptions\                   → src/Exceptions/
│   └── HttpException             → src/Exceptions/HttpException.php
├── Logging\                      → src/Logging/
│   └── Logger                    → src/Logging/Logger.php
├── Streaming\                    → src/Streaming/
│   └── StreamingResponse         → src/Streaming/StreamingResponse.php
├── Support\                      → src/Support/
│   ├── Arr                       → src/Support/Arr.php
│   ├── Str                       → src/Support/Str.php
│   └── I18n                      → src/Support/I18n.php
├── Utils\                        → src/Utils/
│   └── Utils                     → src/Utils/Utils.php
└── Validation\                   → src/Validation/
    └── Validator                 → src/Validation/Validator.php
```

## 🔧 Configuração Detalhada

### Autoload Otimizado
```json
{
    "autoload": {
        "psr-4": {
            "Express\\": "src/"
        },
        "files": [
            "src/Support/helpers.php"
        ],
        "exclude-from-classmap": [
            "/tests/",
            "/examples/",
            "/docs/"
        ]
    },
    "config": {
        "optimize-autoloader": true,
        "classmap-authoritative": true,
        "apcu-autoloader": true,
        "sort-packages": true,
        "platform": {
            "php": "8.1"
        }
    }
}
```

### Autoload para Desenvolvimento
```json
{
    "autoload-dev": {
        "psr-4": {
            "Express\\Tests\\": "tests/",
            "Express\\Examples\\": "examples/",
            "Express\\Benchmarks\\": "benchmarks/"
        }
    }
}
```

## 📁 Estrutura de Arquivos

### Convenções de Nomenclatura

#### 1. Classes
```php
<?php
// Arquivo: src/Middleware/Security/CorsMiddleware.php
namespace Express\Middleware\Security;

class CorsMiddleware
{
    // Implementação
}
```

#### 2. Interfaces
```php
<?php
// Arquivo: src/Cache/CacheInterface.php
namespace Express\Cache;

interface CacheInterface
{
    // Definições
}
```

#### 3. Traits
```php
<?php
// Arquivo: src/Support/Traits/Cacheable.php
namespace Express\Support\Traits;

trait Cacheable
{
    // Implementação
}
```

#### 4. Exceptions
```php
<?php
// Arquivo: src/Exceptions/ValidationException.php
namespace Express\Exceptions;

class ValidationException extends \Exception
{
    // Implementação
}
```

## 🚀 Otimizações de Performance

### 1. Classmap Authoritative
```bash
# Gerar autoloader otimizado para produção
composer dump-autoload --optimize --classmap-authoritative

# Verifica se classes estão no classmap antes de usar filesystem
```

### 2. APCu Cache
```json
{
    "config": {
        "apcu-autoloader": true
    }
}
```

```bash
# Habilitar APCu cache
composer dump-autoload --apcu
```

### 3. Preloading (PHP 7.4+)
```php
<?php
// config/preload.php
opcache_compile_file(__DIR__ . '/../vendor/autoload.php');

// Preload classes mais usadas
$preloadClasses = [
    'Express\\ApiExpress',
    'Express\\Routing\\Router',
    'Express\\Http\\Request',
    'Express\\Http\\Response',
    'Express\\Middleware\\Security\\CorsMiddleware'
];

foreach ($preloadClasses as $class) {
    if (class_exists($class)) {
        opcache_compile_file((new ReflectionClass($class))->getFileName());
    }
}
```

## 🔍 Debugging Autoload

### Verificar Mapeamento
```php
<?php
// Script para verificar mapeamento PSR-4
$autoloader = require 'vendor/autoload.php';

// Verificar se namespace está registrado
$prefixes = $autoloader->getPrefixesPsr4();
var_dump($prefixes['Express\\']);

// Testar carregamento de classe
try {
    $reflection = new ReflectionClass('Express\\ApiExpress');
    echo "Arquivo: " . $reflection->getFileName() . PHP_EOL;
} catch (ReflectionException $e) {
    echo "Erro: " . $e->getMessage() . PHP_EOL;
}
```

### Debug de Carregamento
```php
<?php
// Habilitar debug do autoloader
spl_autoload_register(function($class) {
    error_log("Tentando carregar classe: $class");
}, true, true);

// Verificar classes carregadas
$classes = get_declared_classes();
$expressClasses = array_filter($classes, function($class) {
    return strpos($class, 'Express\\') === 0;
});

echo "Classes Express carregadas: " . count($expressClasses) . PHP_EOL;
```

## 🧪 Testing Autoload

### Unit Test para PSR-4
```php
<?php
namespace Express\Tests\Core;

use PHPUnit\Framework\TestCase;

class AutoloadTest extends TestCase
{
    public function testPsr4Mapping()
    {
        $classes = [
            'Express\\ApiExpress',
            'Express\\Routing\\Router',
            'Express\\Http\\Request',
            'Express\\Http\\Response',
            'Express\\Middleware\\Security\\CorsMiddleware'
        ];

        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class), "Classe $class não foi encontrada");
        }
    }

    public function testNamespaceStructure()
    {
        $reflection = new \ReflectionClass('Express\\ApiExpress');
        $expectedPath = realpath(__DIR__ . '/../../src/ApiExpress.php');

        $this->assertEquals($expectedPath, $reflection->getFileName());
    }

    public function testAutoloadPerformance()
    {
        $start = microtime(true);

        // Carregar 100 classes
        for ($i = 0; $i < 100; $i++) {
            class_exists('Express\\ApiExpress');
        }

        $time = microtime(true) - $start;

        // Deve ser muito rápido com autoload otimizado
        $this->assertLessThan(0.01, $time);
    }
}
```

## 📊 Performance Benchmarks

### Benchmark de Autoload
```php
<?php
class AutoloadBenchmark
{
    public static function benchmarkClassLoading(): array
    {
        $classes = [
            'Express\\ApiExpress',
            'Express\\Routing\\Router',
            'Express\\Http\\Request',
            'Express\\Http\\Response',
            'Express\\Middleware\\Security\\CorsMiddleware'
        ];

        $results = [];

        foreach ($classes as $class) {
            $start = microtime(true);

            for ($i = 0; $i < 1000; $i++) {
                class_exists($class);
            }

            $time = microtime(true) - $start;
            $results[$class] = [
                'time' => $time,
                'ops_per_second' => 1000 / $time
            ];
        }

        return $results;
    }

    public static function compareAutoloadMethods(): array
    {
        // Teste sem otimização
        shell_exec('composer dump-autoload');
        $timeNormal = self::timeClassLoading();

        // Teste com otimização
        shell_exec('composer dump-autoload --optimize');
        $timeOptimized = self::timeClassLoading();

        // Teste com classmap authoritative
        shell_exec('composer dump-autoload --optimize --classmap-authoritative');
        $timeAuthoritative = self::timeClassLoading();

        return [
            'normal' => $timeNormal,
            'optimized' => $timeOptimized,
            'authoritative' => $timeAuthoritative,
            'improvement' => [
                'optimized_vs_normal' => ($timeNormal - $timeOptimized) / $timeNormal * 100,
                'authoritative_vs_normal' => ($timeNormal - $timeAuthoritative) / $timeNormal * 100
            ]
        ];
    }

    private static function timeClassLoading(): float
    {
        $start = microtime(true);

        for ($i = 0; $i < 10000; $i++) {
            class_exists('Express\\ApiExpress');
        }

        return microtime(true) - $start;
    }
}
```

## 🔧 Ferramentas e Scripts

### Script de Validação
```php
#!/usr/bin/env php
<?php
// scripts/validate_autoload.php

require_once 'vendor/autoload.php';

class AutoloadValidator
{
    private array $errors = [];

    public function validate(): bool
    {
        $this->validatePsr4Structure();
        $this->validateClassFiles();
        $this->validateNamespaces();

        if (!empty($this->errors)) {
            foreach ($this->errors as $error) {
                echo "❌ $error\n";
            }
            return false;
        }

        echo "✅ Autoload PSR-4 está configurado corretamente\n";
        return true;
    }

    private function validatePsr4Structure(): void
    {
        $expectedDirs = [
            'src/Core',
            'src/Routing',
            'src/Middleware',
            'src/Http',
            'src/Authentication',
            'src/Cache',
            'src/Support'
        ];

        foreach ($expectedDirs as $dir) {
            if (!is_dir($dir)) {
                $this->errors[] = "Diretório obrigatório não encontrado: $dir";
            }
        }
    }

    private function validateClassFiles(): void
    {
        $files = glob('src/**/*.php', GLOB_BRACE);

        foreach ($files as $file) {
            $expectedClass = $this->fileToClass($file);

            if (!class_exists($expectedClass) && !interface_exists($expectedClass)) {
                $this->errors[] = "Classe/Interface não encontrada: $expectedClass (arquivo: $file)";
            }
        }
    }

    private function validateNamespaces(): void
    {
        $classes = get_declared_classes();
        $interfaces = get_declared_interfaces();
        $all = array_merge($classes, $interfaces);

        $expressItems = array_filter($all, function($item) {
            return strpos($item, 'Express\\') === 0;
        });

        foreach ($expressItems as $item) {
            $expectedFile = $this->classToFile($item);

            if (file_exists($expectedFile)) {
                $reflection = new ReflectionClass($item);
                if ($reflection->getFileName() !== realpath($expectedFile)) {
                    $this->errors[] = "Arquivo incorreto para $item";
                }
            }
        }
    }

    private function fileToClass(string $file): string
    {
        $path = str_replace(['src/', '.php'], '', $file);
        return 'Express\\' . str_replace('/', '\\', $path);
    }

    private function classToFile(string $class): string
    {
        $path = str_replace(['Express\\', '\\'], ['src/', '/'], $class);
        return $path . '.php';
    }
}

$validator = new AutoloadValidator();
exit($validator->validate() ? 0 : 1);
```

## 📚 Melhores Práticas

### 1. Estrutura Consistente
```
src/
├── [Namespace]/
│   ├── [SubNamespace]/
│   │   └── ClassName.php
│   ├── InterfaceName.php
│   └── AbstractClassName.php
```

### 2. Nomenclatura
- **Classes:** PascalCase (`CorsMiddleware`)
- **Interfaces:** PascalCase + "Interface" (`CacheInterface`)
- **Traits:** PascalCase (`Cacheable`)
- **Namespaces:** PascalCase (`Express\Middleware\Security`)

### 3. Um Arquivo, Uma Classe
```php
<?php
// ✅ Correto - um arquivo por classe
// src/Http/Request.php
namespace Express\Http;

class Request
{
    // Implementação
}
```

### 4. Otimização para Produção
```bash
# Always optimize autoloader in production
composer dump-autoload --optimize --classmap-authoritative --no-dev
```

## 🚨 Troubleshooting

### Problemas Comuns

#### 1. Class Not Found
```bash
# Regenerar autoloader
composer dump-autoload

# Verificar namespace no arquivo
grep -n "namespace" src/Path/To/File.php
```

#### 2. Performance Issues
```bash
# Otimizar autoloader
composer dump-autoload --optimize --classmap-authoritative

# Verificar APCu
php -m | grep apcu
```

#### 3. Case Sensitivity
```php
// ❌ Inconsistente
namespace Express\middleware\security;
class corsMiddleware {}

// ✅ Correto
namespace Express\Middleware\Security;
class CorsMiddleware {}
```

## 📈 Monitoramento

### Métricas de Autoload
```php
class AutoloadMetrics
{
    public static function getStats(): array
    {
        return [
            'classes_loaded' => count(get_declared_classes()),
            'interfaces_loaded' => count(get_declared_interfaces()),
            'memory_usage' => memory_get_usage(true),
            'included_files' => count(get_included_files()),
            'opcache_enabled' => function_exists('opcache_get_status'),
            'apcu_enabled' => function_exists('apcu_cache_info')
        ];
    }
}
```
