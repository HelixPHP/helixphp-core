# 📚 Express PHP Framework - Documentação

## 🚀 Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use Express\ApiExpress;

$app = new ApiExpress();

// Rotas básicas
$app->get('/', function() {
    return ['message' => 'Express PHP Framework'];
});

// Rotas organizadas por grupos (com otimizações automáticas)
$app->group('/api/v1', function() use ($app) {
    $app->get('/users', function() {
        return ['users' => []];
    });
    $app->post('/users', function() {
        return ['created' => true];
    });
});

$app->listen(3000);
```

## 📊 Performance & Benchmarks

### 1. **[Resultados Completos](../OPTIMIZATION_RESULTS.md)** - Análise detalhada de performance
### 2. **[Benchmark de Grupos](../benchmarks/benchmark_group_features.sh)** - Teste específico de funcionalidades de grupo
### 3. **[Benchmark Geral](../benchmarks/run_benchmark.sh)** - Suite completa de testes

**Performance Highlights:**
- **CORS Processing:** 32M+ ops/s
- **Route Pattern Matching:** 1.6M+ ops/s
- **Cache Hit Ratio:** 99.6%
- **Memory per instance:** 1.43 KB

## 🎯 Funcionalidades Principais

### ✅ **Roteamento Otimizado**
- Cache automático de rotas (O(1) access)
- Organização por grupos
- Pattern matching pré-compilado
- Estatísticas em tempo real

### ✅ **Middleware Pipeline**
- Pipeline pré-compilado (1.5M+ ops/s)
- Cache de middlewares
- Detecção de redundâncias

### ✅ **CORS Integrado**
- Performance excepcional (32M+ ops/s)
- Cache de headers
- Configuração flexível

### ✅ **Security Built-in**
- XSS Protection (3.5M+ ops/s)
- JWT Token support
- Sanitização automática

## 📈 Exemplos de Uso

### Básico
```bash
cd examples && php example_basic.php
```

### Com Grupos (Recomendado)
```bash
cd examples && php example_optimized_groups.php
```

### Middleware Completo
```bash
cd examples && php example_complete_optimizations.php
```

## 🔧 Benchmark & Teste

```bash
# Benchmark rápido
./benchmarks/run_benchmark.sh -q

# Benchmark completo
./benchmarks/run_benchmark.sh -a

# Teste específico de grupos
./benchmarks/benchmark_group_features.sh
```

## 📋 API Reference

### Router Methods
- `$app->get($path, $handler)`
- `$app->post($path, $handler)`
- `$app->put($path, $handler)`
- `$app->delete($path, $handler)`
- `$app->group($prefix, $callback, $middlewares)`

### Utilities
- `$app->router->getGroupStats()` - Estatísticas de grupos
- `$app->router->warmupGroups()` - Aquecimento de cache
- `$app->middlewareStack->getStats()` - Stats de middleware

---

**Express PHP Framework** - Performance, simplicidade e flexibilidade. 🚀
