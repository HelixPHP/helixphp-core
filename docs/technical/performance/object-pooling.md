# Object Pooling - Otimização de Performance

O PivotPHP implementa **Object Pooling** para otimizar performance e uso de memória, especialmente em aplicações de alta demanda.

## 🚀 O que é Object Pooling?

Object Pooling é uma técnica de otimização que reutiliza objetos já criados ao invés de criar novos a cada requisição. Isso reduz:
- ✅ **Garbage Collection** desnecessário
- ✅ **Alocação de memória** excessiva
- ✅ **Tempo de criação** de objetos
- ✅ **Pressão sobre o sistema**

## 🔧 Implementação no PivotPHP

### Factory Otimizada

```php
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;

// Configurar pooling
OptimizedHttpFactory::initialize([
    'enable_pooling' => true,
    'warm_up_pools' => true,
    'max_pool_size' => 100,
    'enable_metrics' => true,
]);

// Criar objetos com pooling
$request = OptimizedHttpFactory::createRequest('GET', '/api/users', '/api/users');
$response = OptimizedHttpFactory::createResponse();

// Criar objetos PSR-7 com pooling
$psr7Request = OptimizedHttpFactory::createServerRequest('POST', '/api/data');
$psr7Response = OptimizedHttpFactory::createPsr7Response(200, [], '{"status": "ok"}');
```

### Pools Disponíveis

O sistema mantém pools separados para diferentes tipos de objetos:

```php
// Pool de ServerRequest (PSR-7)
$serverRequest = Psr7Pool::getServerRequest($method, $uri, $body, $headers);

// Pool de Response (PSR-7)
$response = Psr7Pool::getResponse($statusCode, $headers, $body);

// Pool de Uri (PSR-7)
$uri = Psr7Pool::getUri('/api/endpoint');

// Pool de Stream (PSR-7)
$stream = Psr7Pool::getStream('{"data": "content"}');
```

### Gerenciamento Automático

Os objetos são automaticamente retornados ao pool quando não são mais necessários:

```php
class Request {
    public function __destruct() {
        // Retorna automaticamente ao pool
        if ($this->psr7Request !== null) {
            Psr7Pool::returnServerRequest($this->psr7Request);
        }
    }
}
```

## 📊 Configuração

### Configuração Básica

```php
OptimizedHttpFactory::initialize([
    'enable_pooling' => true,        // Habilitar pooling
    'warm_up_pools' => true,         // Pré-aquecer pools
    'max_pool_size' => 100,          // Tamanho máximo do pool
    'enable_metrics' => true,        // Habilitar métricas
]);
```

### Configuração Avançada

```php
OptimizedHttpFactory::initialize([
    'enable_pooling' => true,
    'pool_config' => [
        'initial_size' => 50,        // Tamanho inicial
        'max_size' => 200,           // Tamanho máximo
        'expansion_factor' => 1.5,   // Fator de expansão
        'cleanup_interval' => 300,   // Intervalo de limpeza (segundos)
    ],
    'stress_handling' => [
        'enable_priority' => true,   // Sistema de prioridades
        'enable_rate_limiting' => true, // Rate limiting
        'emergency_limit' => 500,    // Limite de emergência
    ],
]);
```

### Configuração por Ambiente

```php
// Desenvolvimento
OptimizedHttpFactory::initialize([
    'enable_pooling' => true,
    'max_pool_size' => 20,
    'enable_metrics' => true,
]);

// Produção
OptimizedHttpFactory::initialize([
    'enable_pooling' => true,
    'max_pool_size' => 200,
    'warm_up_pools' => true,
    'enable_metrics' => true,
]);

// Alta demanda
OptimizedHttpFactory::initialize([
    'enable_pooling' => true,
    'max_pool_size' => 500,
    'emergency_limit' => 1000,
    'warm_up_pools' => true,
]);
```

## 📈 Monitoramento

### Métricas em Tempo Real

```php
// Obter estatísticas do pool
$stats = OptimizedHttpFactory::getPoolStats();

/*
Array:
[
    'pool_sizes' => [
        'requests' => 45,
        'responses' => 38,
        'uris' => 12,
        'streams' => 67,
    ],
    'efficiency' => [
        'request_reuse_rate' => 78.5,
        'response_reuse_rate' => 82.1,
        'uri_reuse_rate' => 65.3,
        'stream_reuse_rate' => 91.2,
    ],
    'usage' => [
        'requests_created' => 1250,
        'requests_reused' => 4890,
        'responses_created' => 1180,
        'responses_reused' => 4920,
    ],
]
*/
```

### Métricas de Performance

```php
$metrics = OptimizedHttpFactory::getPerformanceMetrics();

/*
Array:
[
    'memory_usage' => [
        'current' => 12582912,  // 12MB
        'peak' => 15728640,     // 15MB
    ],
    'pool_efficiency' => [
        'request_reuse_rate' => 78.5,
        'response_reuse_rate' => 82.1,
    ],
    'recommendations' => [
        'Pool performance is within acceptable ranges',
        'Consider increasing pool size for better efficiency',
    ],
]
*/
```

### Monitoramento Programático

```php
// Obter estatísticas do pool
$stats = Psr7Pool::getStats();
echo "Pool sizes: " . json_encode($stats['pool_sizes']);

// Limpar todos os pools
Psr7Pool::clearAll();

// Pré-aquecer pools
Psr7Pool::warmUp();
```

## 🎯 Benefícios de Performance

### Antes do Pooling

```php
// Cada requisição cria novos objetos
for ($i = 0; $i < 1000; $i++) {
    $request = new Request('GET', '/api/users', '/api/users');
    $response = new Response();
    // ... processar
    unset($request, $response); // Garbage collection
}
// Resultado: 1000 objetos criados + 1000 GC calls
```

### Depois do Pooling

```php
// Objetos são reutilizados
for ($i = 0; $i < 1000; $i++) {
    $request = OptimizedHttpFactory::createRequest('GET', '/api/users', '/api/users');
    $response = OptimizedHttpFactory::createResponse();
    // ... processar
    unset($request, $response); // Retorna ao pool automaticamente
}
// Resultado: ~50 objetos criados + reutilização eficiente
```

### Benchmarks Típicos

```
Cenário: 1000 requisições simultâneas

Sem Pooling:
- Objetos criados: 1000
- Tempo: 0.850s
- Memória pico: 45MB
- GC calls: 1000

Com Pooling:
- Objetos criados: 52
- Tempo: 0.420s ⚡ (50% mais rápido)
- Memória pico: 18MB 💾 (60% menos)
- GC calls: 52 ♻️ (95% menos)
```

## 🔄 Compatibilidade PSR-7

O pooling mantém total compatibilidade com PSR-7:

```php
// Imutabilidade preservada
$response1 = OptimizedHttpFactory::createPsr7Response(200);
$response2 = $response1->withStatus(404); // Nova instância do pool
$response3 = $response2->withHeader('X-Custom', 'value'); // Nova instância do pool

// Cada with* retorna nova instância independente
// Pooling funciona transparentemente
```

## 🛠️ Configuração Avançada

### Configuração Personalizada

```php
// Configurar pools específicos
OptimizedHttpFactory::updateConfig([
    'pool_config' => [
        'server_request_pool_size' => 100,
        'response_pool_size' => 150,
        'uri_pool_size' => 50,
        'stream_pool_size' => 200,
    ],
]);

// Verificar se pooling está ativo
if (OptimizedHttpFactory::isPoolingEnabled()) {
    echo "Pooling ativo! 🚀";
}
```

### Desabilitar Pooling

```php
// Desabilitar temporariamente
OptimizedHttpFactory::setPoolingEnabled(false);

// Limpar todos os pools
OptimizedHttpFactory::clearPools();

// Reabilitar
OptimizedHttpFactory::setPoolingEnabled(true);
```

### Warm-up Personalizado

```php
// Aquecer pools com dados específicos
OptimizedHttpFactory::warmUpPools();

// Ou manualmente
for ($i = 0; $i < 20; $i++) {
    $request = OptimizedHttpFactory::createRequest('GET', '/warmup', '/warmup');
    OptimizedHttpFactory::returnToPool($request);
}
```

## 🎪 Casos de Uso

### API REST de Alta Demanda

```php
// Configuração para alta demanda
OptimizedHttpFactory::initialize([
    'enable_pooling' => true,
    'max_pool_size' => 500,
    'warm_up_pools' => true,
    'pool_config' => [
        'expansion_factor' => 2.0,
        'emergency_limit' => 1000,
    ],
]);

// Endpoints com pooling otimizado
$app->get('/api/users', function($req, $res) {
    // Objetos vêm do pool automaticamente
    return $res->json($userService->getAllUsers());
});
```

### Microserviços

```php
// Configuração para microserviços
OptimizedHttpFactory::initialize([
    'enable_pooling' => true,
    'max_pool_size' => 100,
    'enable_metrics' => true,
    'stress_handling' => [
        'enable_priority' => true,
        'enable_rate_limiting' => true,
    ],
]);
```

### Aplicações Real-time

```php
// Configuração para tempo real
OptimizedHttpFactory::initialize([
    'enable_pooling' => true,
    'max_pool_size' => 300,
    'warm_up_pools' => true,
    'pool_config' => [
        'cleanup_interval' => 60, // Limpeza mais frequente
    ],
]);
```

## 🔍 Debugging

### Logs de Pool

```php
// Habilitar logs detalhados
OptimizedHttpFactory::initialize([
    'enable_pooling' => true,
    'debug_config' => [
        'log_pool_operations' => true,
        'log_reuse_rate' => true,
        'log_memory_usage' => true,
    ],
]);
```

### Análise de Performance

```php
// Analisar performance do pool
$metrics = OptimizedHttpFactory::getPerformanceMetrics();

foreach ($metrics['recommendations'] as $rec) {
    echo "💡 {$rec}\n";
}

// Verificar eficiência
foreach ($metrics['pool_efficiency'] as $type => $rate) {
    if ($rate < 50) {
        echo "⚠️ Low efficiency for {$type}: {$rate}%\n";
    }
}
```

## 🎉 Conclusão

O Object Pooling no PivotPHP oferece:

- ✅ **Performance otimizada** com reutilização inteligente
- ✅ **Menor uso de memória** em aplicações de alta demanda
- ✅ **Compatibilidade total** com PSR-7 e Express.js
- ✅ **Monitoramento detalhado** com métricas em tempo real
- ✅ **Configuração flexível** para diferentes cenários
- ✅ **Gerenciamento automático** sem intervenção manual

Ideal para APIs REST, microserviços e aplicações real-time que precisam de máxima performance e eficiência de recursos.