# PivotPHP Core v1.0.1 - Framework Overview

**Versão:** 1.0.1  
**Data de Release:** 9 de Julho, 2025  
**Status:** Production Release  

## 📋 Visão Geral

PivotPHP Core v1.0.1 introduz **suporte híbrido PSR-7** e **otimizações avançadas de performance**. Esta versão mantém 100% de compatibilidade com a API Express.js enquanto adiciona suporte completo para PSR-7, permitindo interoperabilidade total com o ecossistema PHP moderno.

## 🎯 Objetivos da Versão

- **PSR-7 Hybrid Support:** Implementação completa de PSR-7 mantendo Express.js API
- **Object Pooling System:** Sistema avançado de pooling para alta performance
- **Zero Breaking Changes:** 100% compatibilidade com código v1.0.0
- **Performance Optimizations:** Até 60% redução no uso de memória
- **Enhanced Developer Experience:** Melhorias na experiência de desenvolvimento

## 📊 Métricas da Versão

### Performance Improvements
- **Memory Reduction:** Até 60% redução no uso de memória em cenários de alto tráfego
- **Object Pooling:** Redução significativa na criação de objetos e garbage collection
- **Lazy Loading:** PSR-7 objects criados apenas quando necessário
- **Optimized Factory:** Reuso inteligente de objetos para melhor performance

### PSR-7 Compatibility
- **Request Class:** Implementa `ServerRequestInterface` completo
- **Response Class:** Implementa `ResponseInterface` completo
- **Lazy Loading:** Objetos PSR-7 criados apenas quando acessados
- **Full Compatibility:** Suporte completo a middleware PSR-15

### Quality Metrics
- **PHPStan:** Level 9, zero erros
- **PSR-12:** 100% compliance
- **Type Safety:** Resolução completa de problemas de tipo
- **Test Coverage:** Suite de testes atualizada para implementação híbrida

## 🆕 Novos Recursos v1.0.1

### 🔄 PSR-7 Hybrid Implementation

**Request Class Enhancement:**
```php
// Express.js API (inalterada)
$app->get('/users/:id', function($req, $res) {
    $id = $req->param('id');
    return $res->json(['user' => $userService->find($id)]);
});

// PSR-7 API (agora suportada)
$app->use(function(ServerRequestInterface $request, ResponseInterface $response, $next) {
    $method = $request->getMethod();
    $newRequest = $request->withAttribute('processed', true);
    return $next($newRequest, $response);
});
```

**Key Changes:**
- `getBody()` renomeado para `getBodyAsStdClass()` para compatibilidade legacy
- `getHeaders()` renomeado para `getHeadersObject()` para estilo Express.js
- Métodos PSR-7 adicionados: `getMethod()`, `getUri()`, `getHeaders()`, `getBody()`
- Métodos imutáveis `with*()` para compliance PSR-7

### ⚡ Object Pooling System

**Advanced Memory Optimization:**
```php
// Configuração de object pooling
OptimizedHttpFactory::initialize([
    'enable_pooling' => true,
    'warm_up_pools' => true,
    'max_pool_size' => 100,
]);
```

**Components:**
- **Psr7Pool**: Pools para ServerRequest, Response, Uri, e Stream objects
- **OptimizedHttpFactory**: Factory com configurações de pooling
- **Automatic Reuse**: Reuso automático de objetos para reduzir GC pressure
- **Performance Metrics**: Ferramentas de monitoramento e métricas

### 🏗️ Enhanced Architecture

**Distributed Pooling:**
- Redis support movido para extensão `pivotphp/redis-pool`
- Built-in `NoOpCoordinator` para deployments single-instance
- Fallback automático quando extensões não estão disponíveis

**Factory System:**
- `OptimizedHttpFactory` substitui criação básica de objetos HTTP
- Pooling configurável para melhor gerenciamento de memória
- Gerenciamento automático do ciclo de vida de objetos

## 🔧 Melhorias Técnicas

### Response Class Enhancement
- Implementa `ResponseInterface` PSR-7 mantendo métodos Express.js
- Métodos PSR-7: `getStatusCode()`, `getHeaders()`, `getBody()`
- Métodos imutáveis `with*()` para compliance PSR-7
- Lazy loading para performance

### Type Safety & Fixes
- **PHPStan Level 9**: Resolução de problemas de tipo com implementação PSR-7
- **Method Conflicts**: Fix do conflito do método `getBody()` entre legacy e PSR-7
- **File Handling**: Melhor handling de upload de arquivos com integração PSR-7 stream
- **Immutability**: Imutabilidade adequada nos métodos PSR-7 `with*()`

### Test Compatibility
- Suite de testes atualizada para funcionar com implementação híbrida
- Novos testes para funcionalidade PSR-7
- Testes de performance para object pooling
- Validação de compatibilidade backward

## 📋 API Compatibility

### Express.js API (Inalterada)
```php
// Continua funcionando exatamente igual
$req->param('id');
$req->query('search');
$req->body();
$res->json(['data' => $data]);
$res->status(201)->send('Created');
```

### PSR-7 API (Nova)
```php
// Agora totalmente suportada
$method = $request->getMethod();
$uri = $request->getUri();
$body = $request->getBody();
$response = $response->withStatus(200)->withHeader('Content-Type', 'application/json');
```

### Middleware PSR-15
```php
// Suporte completo a type hints PSR-15
function(ServerRequestInterface $request, ResponseInterface $response, $next) {
    // Middleware code
    return $next($request, $response);
}
```

## 🚀 Performance Benefits

### Memory Efficiency
- **Object Pooling**: Até 60% redução no uso de memória
- **Lazy Loading**: PSR-7 objects criados apenas quando necessário
- **Reduced GC Pressure**: Menos pressão no garbage collector
- **Intelligent Reuse**: Reuso inteligente de objetos frequentemente usados

### Throughput Improvements
- **Optimized Factory**: Factory otimizada para criação de objetos
- **Pool Management**: Gerenciamento eficiente de pools de objetos
- **Warm-up Capabilities**: Capacidade de pré-aquecimento de pools
- **Configurable Sizing**: Tamanhos de pool configuráveis

## 🎯 Use Cases

### Interoperability
- **PSR-15 Middleware**: Uso de middleware de terceiros compatível com PSR-15
- **Framework Integration**: Integração com outros frameworks PHP modernos
- **Library Compatibility**: Compatibilidade com bibliotecas que esperam PSR-7
- **Standard Compliance**: Compliance total com padrões PSR

### High-Performance Applications
- **Object Pooling**: Para aplicações com alto volume de requests
- **Memory Optimization**: Para ambientes com restrições de memória
- **Lazy Loading**: Para aplicações com uso variável de recursos
- **Configurable Performance**: Tuning fino para cenários específicos

## 📚 Documentation Enhancements

### New Guides
- **PSR-7 Hybrid Usage**: Guia completo de uso das APIs híbridas
- **Object Pooling Configuration**: Configuração e otimização de pooling
- **Performance Optimization**: Técnicas de otimização de performance
- **Debug Mode Guide**: Guia abrangente para debugging de aplicações

### Updated Documentation
- **Request/Response**: Documentação atualizada com exemplos PSR-7
- **Middleware**: Exemplos de middleware PSR-15
- **Factory System**: Documentação do sistema de factory aprimorado
- **Performance Monitoring**: Ferramentas de monitoramento e profiling

## 🎯 Migration Path

### Zero Breaking Changes
- **Existing Code**: Todo código v1.0.0 continua funcionando
- **API Compatibility**: APIs Express.js mantidas integralmente
- **Method Names**: Métodos renomeados mantêm aliases para compatibilidade
- **Gradual Adoption**: Adoção gradual de recursos PSR-7

### Optional Enhancements
```php
// Ativar object pooling (opcional)
OptimizedHttpFactory::initialize(['enable_pooling' => true]);

// Usar PSR-7 em novos middleware (opcional)
function(ServerRequestInterface $req, ResponseInterface $res, $next) {
    // New PSR-7 middleware
}
```

## 📈 Ecosystem Impact

v1.0.1 estabelece PivotPHP como:
- **Framework Híbrido**: Melhor dos dois mundos - Express.js + PSR-7
- **Performance Leader**: Otimizações avançadas de memória e throughput
- **Standard Compliant**: Compliance total com padrões PHP modernos
- **Developer Friendly**: Experiência familiar com recursos modernos

---

**PivotPHP Core v1.0.1** - Híbrido PSR-7, performance otimizada, experiência moderna.