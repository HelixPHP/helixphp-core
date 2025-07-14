# Migration Guide v1.1.4+

## 🎯 Overview

Este guia ajuda na migração para PivotPHP v1.1.4+ que introduz **Array Callable Support nativo**, **JsonBufferPool com threshold inteligente** e **Enhanced Error Diagnostics**.

## 🚀 Principais Mudanças

### ✅ Breaking Changes: NENHUMA
- **100% Backward Compatible** - Código existente continua funcionando
- **Opt-in Features** - Novos recursos são opcionais
- **Seamless Upgrade** - Migração sem downtime

### ✨ Novas Features
- **Array Callables Nativos** - `[Controller::class, 'method']` 
- **JsonBufferPool Threshold** - Sistema inteligente (≥256 bytes)
- **ContextualException** - Diagnósticos detalhados
- **CallableResolver** - Validação robusta

## 🔄 Migração Passo a Passo

### 1. Update Dependencies
```bash
# Atualizar para v1.1.4+
composer update pivotphp/core

# Verificar versão instalada
composer show pivotphp/core
```

### 2. Array Callables Migration

#### ANTES v1.1.3 (Workaround)
```php
// Closure wrapper necessário
$app->get('/users', function($req, $res) {
    $controller = new UserController();
    return $controller->index($req, $res);
});

$app->post('/users', function($req, $res) {
    $controller = new UserController();
    return $controller->store($req, $res);
});
```

#### DEPOIS v1.1.4+ (Nativo)
```php
// ✅ Array callable direto
$app->get('/users', [UserController::class, 'index']);
$app->post('/users', [UserController::class, 'store']);

// ✅ Com instância específica
$controller = new UserController($dependencies);
$app->get('/users', [$controller, 'index']);
```

#### Script de Migração Automática
```php
<?php
// migrate_to_array_callables.php

function migrateRouteToArrayCallable($routeCode) {
    // Pattern: function($req, $res) { $controller = new Class(); return $controller->method($req, $res); }
    $pattern = '/function\s*\(\s*\$req\s*,\s*\$res\s*\)\s*{\s*\$controller\s*=\s*new\s+(\w+)\(\)\s*;\s*return\s+\$controller\s*->\s*(\w+)\s*\(\s*\$req\s*,\s*\$res\s*\)\s*;\s*}/';
    
    return preg_replace_callback($pattern, function($matches) {
        $class = $matches[1];
        $method = $matches[2];
        return "[{$class}::class, '{$method}']";
    }, $routeCode);
}

// Exemplo de uso
$oldRoute = '$app->get(\'/users\', function($req, $res) { $controller = new UserController(); return $controller->index($req, $res); });';
$newRoute = migrateRouteToArrayCallable($oldRoute);

echo "ANTES: {$oldRoute}\n";
echo "DEPOIS: " . str_replace("[{$class}::class, '{$method}']", "[UserController::class, 'index']", $newRoute) . "\n";
```

### 3. JsonBufferPool Optimization

#### ANTES v1.1.3 (Manual)
```php
// Configuração manual necessária
JsonBufferPool::configure([
    'enable_pooling' => true, // Sempre ativo
    'threshold' => 0          // Sem threshold
]);
```

#### DEPOIS v1.1.4+ (Automático)
```php
// ✅ Zero configuração - funciona automaticamente
// Sistema usa threshold inteligente (256 bytes)

// ✅ Configuração opcional apenas se necessário
JsonBufferPool::configure([
    'threshold_bytes' => 512 // Personalizar threshold
]);
```

#### Verificar Performance
```php
// Script para testar ganhos de performance
function testJsonPerformance() {
    $smallData = ['status' => 'ok']; // <256 bytes
    $largeData = array_fill(0, 100, ['id' => 1, 'data' => str_repeat('x', 50)]);
    
    echo "=== TESTE JSON PERFORMANCE v1.1.4+ ===\n";
    
    // Teste dados pequenos
    $start = microtime(true);
    for ($i = 0; $i < 10000; $i++) {
        JsonBufferPool::encodeWithPool($smallData);
    }
    $smallTime = microtime(true) - $start;
    echo "Dados pequenos: {$smallTime}s (deve usar json_encode direto)\n";
    
    // Teste dados grandes
    $start = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        JsonBufferPool::encodeWithPool($largeData);
    }
    $largeTime = microtime(true) - $start;
    echo "Dados grandes: {$largeTime}s (deve usar pooling)\n";
    
    $stats = JsonBufferPool::getStatistics();
    echo "Eficiência do pool: {$stats['efficiency']}%\n";
}

testJsonPerformance();
```

### 4. Error Handling Enhancement

#### ANTES v1.1.3 (Generic)
```php
// Erros genéricos pouco informativos
try {
    $app->get('/route', [NonExistentController::class, 'method']);
} catch (Exception $e) {
    echo $e->getMessage(); // "Route handler validation failed"
}
```

#### DEPOIS v1.1.4+ (Contextual)
```php
// ✅ Usar ContextualException para erros detalhados
use PivotPHP\Core\Exceptions\Enhanced\ContextualException;

try {
    $app->get('/route', [NonExistentController::class, 'method']);
} catch (ContextualException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Categoria: " . $e->getCategory() . "\n";
    echo "Contexto: " . json_encode($e->getContext()) . "\n";
    echo "Sugestões:\n";
    foreach ($e->getSuggestions() as $suggestion) {
        echo "  - {$suggestion}\n";
    }
}
```

#### Implementar Error Handler Global
```php
// Middleware global para ContextualException
$app->use(function($req, $res, $next) {
    try {
        return $next($req, $res);
    } catch (ContextualException $e) {
        // Log detalhado
        error_log("ContextualException: " . $e->getMessage());
        error_log("Context: " . json_encode($e->getContext()));
        
        // Response com diagnósticos (apenas em desenvolvimento)
        $isDev = ($_ENV['APP_ENV'] ?? 'production') === 'development';
        
        return $res->status($e->getStatusCode())->json([
            'error' => true,
            'message' => $e->getMessage(),
            'category' => $e->getCategory(),
            'suggestions' => $isDev ? $e->getSuggestions() : [],
            'debug' => $isDev ? $e->getDebugInfo() : null
        ]);
    } catch (Exception $e) {
        // Fallback para outras exceções
        error_log("General Exception: " . $e->getMessage());
        
        return $res->status(500)->json([
            'error' => true,
            'message' => 'Internal Server Error'
        ]);
    }
});
```

## 🧪 Testing Migration

### Unit Tests para Array Callables
```php
<?php
// tests/Migration/ArrayCallableTest.php

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;

class ArrayCallableTest extends TestCase 
{
    public function testArrayCallableMigration() 
    {
        $app = new Application();
        
        // Teste array callables funcionam
        $this->assertNoException(function() use ($app) {
            $app->get('/test', [TestController::class, 'method']);
        });
    }
    
    public function testCallableValidation() 
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route handler validation failed');
        
        $app = new Application();
        $app->get('/invalid', [NonExistentController::class, 'method']);
    }
    
    private function assertNoException(callable $callback) 
    {
        try {
            $callback();
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail("Exception was thrown: " . $e->getMessage());
        }
    }
}

class TestController 
{
    public function method($req, $res) 
    {
        return $res->json(['test' => 'ok']);
    }
}
```

### Integration Tests para JsonBufferPool
```php
<?php
// tests/Migration/JsonBufferPoolTest.php

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

class JsonBufferPoolTest extends TestCase 
{
    public function testThresholdIntelligent() 
    {
        // Dados pequenos - deve usar json_encode() direto
        $smallData = ['test' => 'small'];
        $this->assertFalse(JsonBufferPool::shouldUsePooling($smallData));
        
        // Dados grandes - deve usar pooling
        $largeData = array_fill(0, 100, ['test' => str_repeat('x', 100)]);
        $this->assertTrue(JsonBufferPool::shouldUsePooling($largeData));
    }
    
    public function testPerformanceImprovement() 
    {
        $largeData = array_fill(0, 1000, ['id' => 1, 'data' => str_repeat('x', 50)]);
        
        // Teste sem pool
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            json_encode($largeData);
        }
        $withoutPool = microtime(true) - $start;
        
        // Teste com pool
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            JsonBufferPool::encodeWithPool($largeData);
        }
        $withPool = microtime(true) - $start;
        
        // Pool deve ser mais rápido para dados grandes
        $this->assertLessThan($withoutPool, $withPool);
    }
}
```

## 🔧 Production Deployment

### Configuração de Produção
```php
// config/production.php

use PivotPHP\Core\Json\Pool\JsonBufferPool;

// Otimizar JsonBufferPool para produção
JsonBufferPool::configure([
    'threshold_bytes' => 256,       // Threshold padrão otimizado
    'max_pool_size' => 1000,        // Pool maior para alta carga
    'enable_statistics' => false,   // Desabilitar stats em produção (performance)
    'warm_up_pool' => true         // Pre-aquecer pool
]);

// Configurar error handling para produção
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED);
```

### Health Check para Validação
```php
// Endpoint para validar migração
$app->get('/health/migration', function($req, $res) {
    $health = [
        'array_callables' => class_exists('PivotPHP\\Core\\Utils\\CallableResolver'),
        'json_threshold' => method_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool', 'shouldUsePooling'),
        'contextual_exceptions' => class_exists('PivotPHP\\Core\\Exceptions\\Enhanced\\ContextualException'),
        'version' => \PivotPHP\Core\Core\Application::VERSION
    ];
    
    $allOk = array_reduce($health, fn($carry, $item) => $carry && $item, true);
    
    return $res->status($allOk ? 200 : 500)->json([
        'migration_status' => $allOk ? 'success' : 'incomplete',
        'features' => $health,
        'timestamp' => time()
    ]);
});
```

## 🎯 Validation Checklist

### Pre-Migration
- [ ] Backup do código atual
- [ ] Testes passando em ambiente atual
- [ ] Documentação das rotas existentes
- [ ] Identificação de closures que podem ser migradas

### Post-Migration
- [ ] `composer update pivotphp/core` executado
- [ ] Array callables registrando sem erro
- [ ] JsonBufferPool performance melhorada
- [ ] Error handling mais detalhado
- [ ] Testes atualizados e passando
- [ ] Health check da migração OK

### Performance Validation
- [ ] Benchmark antes vs depois da migração
- [ ] JsonBufferPool eficiência >80%
- [ ] Memory usage estável ou melhorado
- [ ] Response times iguais ou melhores
- [ ] Error rates não aumentaram

## 🚨 Troubleshooting

### Problema: Array callables não funcionam
```php
// Debug: verificar se classe e método existem
function debugArrayCallable($class, $method) {
    if (!class_exists($class)) {
        echo "❌ Classe {$class} não encontrada\n";
        return false;
    }
    
    if (!method_exists($class, $method)) {
        echo "❌ Método {$method} não existe\n";
        return false;
    }
    
    $reflection = new ReflectionMethod($class, $method);
    if (!$reflection->isPublic()) {
        echo "❌ Método {$method} não é público\n";
        return false;
    }
    
    echo "✅ Array callable [{$class}, {$method}] válido\n";
    return true;
}
```

### Problema: JsonBufferPool não melhora performance
```php
// Verificar threshold e dados
$data = ['test' => 'data'];
$size = JsonBufferPool::estimateDataSize($data);

if ($size < 256) {
    echo "Dados muito pequenos ({$size} bytes) - threshold não atingido\n";
    echo "Isso é esperado e otimizado!\n";
}

$stats = JsonBufferPool::getStatistics();
if ($stats['efficiency'] < 30) {
    echo "Pool ineficiente - considere ajustar threshold\n";
}
```

## 🔗 Recursos

- [Array Callable Guide](technical/routing/ARRAY_CALLABLE_GUIDE.md)
- [JsonBufferPool Optimization](technical/json/BUFFER_POOL_OPTIMIZATION.md)
- [Troubleshooting Guide](troubleshooting/COMMON_ISSUES.md)
- [Getting Started v1.1.4+](quick-start/GETTING_STARTED_v114.md)

## ✅ Conclusion

A migração para PivotPHP v1.1.4+ é **segura e sem breaking changes**. Os novos recursos são **opt-in** e melhoram significativamente a **developer experience** e **performance**.

**Principais benefícios:**
- ✅ **Array callables nativos** - Código mais limpo
- ✅ **JSON optimization inteligente** - Performance automática  
- ✅ **Error diagnostics avançados** - Debug mais fácil
- ✅ **100% backward compatible** - Migração sem riscos

🎉 **Bem-vindo ao PivotPHP v1.1.4+!**