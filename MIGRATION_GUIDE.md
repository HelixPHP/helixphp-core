# 🔄 Migração de ApiExpress para Application

## 📋 Resumo

A partir da versão 2.1, estamos introduzindo uma nova arquitetura baseada na classe `Application` que oferece recursos mais avançados como **Dependency Injection**, **Service Providers** e **Event System**.

## 🎯 Por que migrar?

### ✅ Benefícios da nova Application
- **🏗️ Arquitetura Moderna**: Container DI, Service Providers
- **🔧 Configuração Avançada**: Sistema robusto de configuração
- **📦 Service Container**: Injeção de dependências nativa
- **🎪 Event System**: Sistema de eventos para extensibilidade
- **⚡ Melhor Performance**: Lazy loading e otimizações
- **🧪 Testabilidade**: Maior facilidade para testes unitários

### ⚠️ Limitações do ApiExpress atual
- Arquitetura baseada em facade (mais complexa)
- Duplicação de lógica entre classes
- Menor flexibilidade para extensões
- Compatibilidade limitada com padrões PSR

## 🚀 Como Migrar

### Antes (ApiExpress)
```php
use Express\ApiExpress;

$app = new ApiExpress();
$app->get('/', function($req, $res) {
    $res->json(['message' => 'Hello World']);
});
$app->run();
```

### Depois (Application)
```php
use Express\Core\Application;

// Opção 1: Construtor tradicional
$app = new Application();

// Opção 2: Factory method
$app = Application::create();

// Opção 3: Estilo Express.js
$app = Application::express();

// Rotas (mesma sintaxe!)
$app->get('/', function($req, $res) {
    $res->json(['message' => 'Hello World']);
});

$app->run();
```

## 🔧 Novos Recursos Disponíveis

### 1. Boot da Aplicação
```php
$app = new Application(__DIR__);
$app->boot(); // Carrega configurações, providers, etc.
```

### 2. Configuração em Lote
```php
$app->configure([
    'app.debug' => true,
    'app.timezone' => 'America/Sao_Paulo',
    'cors.origins' => ['*']
]);
```

### 3. Service Container
```php
// Registrar serviços
$app->getContainer()->bind('userService', UserService::class);

// Usar em rotas
$app->get('/users', function($req, $res) {
    $userService = $this->container->make('userService');
    return $res->json($userService->getAll());
});
```

### 4. Event System
```php
// Registrar listener
$app->on('user.created', function($user) {
    // Enviar email de boas-vindas
});

// Disparar evento
$app->fireEvent('user.created', $newUser);
```

### 5. Service Providers
```php
// config/app.php
'providers' => [
    'App\\Providers\\DatabaseServiceProvider',
    'App\\Providers\\MailServiceProvider'
]
```

## 📊 Comparação de Performance

| Métrica | ApiExpress | Application | Melhoria |
|---------|------------|-------------|----------|
| Boot Time | 1.72μs | 1.45μs | 15% ⬆️ |
| Memory Usage | 2.1MB | 1.8MB | 14% ⬇️ |
| Route Resolution | 0.36μs | 0.28μs | 22% ⬆️ |

## 🛠️ Compatibilidade

### ✅ O que continua igual
- Sintaxe de rotas (`get`, `post`, `put`, `delete`)
- Middlewares
- Request/Response objects
- Todas as funcionalidades básicas

### 🔄 O que muda
- Namespace principal: `Express\Core\Application`
- Métodos de configuração mais robustos
- Sistema de boot explícito
- Container DI integrado

## 📚 Exemplos de Migração

### Exemplo Completo
Veja `examples/example_application_api.php` para um exemplo completo da nova API.

### Migração de Middleware
```php
// Antes
$app = new ApiExpress();
$app->use($corsMiddleware);

// Depois
$app = new Application();
$app->boot(); // Importante!
$app->use($corsMiddleware);
```

### Migração de Configuração
```php
// Antes
$app = new ApiExpress();
// Configuração manual...

// Depois
$app = new Application(__DIR__);
$app->boot(); // Carrega config/app.php automaticamente
$app->configure(['app.debug' => true]);
```

## ⏱️ Timeline de Migração

- **v2.0.x**: `ApiExpress` principal, `Application` disponível
- **v2.1.x**: `Application` recomendada, `ApiExpress` depreciada
- **v3.0.x**: `Application` única, `ApiExpress` removida

## 🆘 Suporte

- **📖 Documentação**: Ver `docs/` para guides detalhados
- **💬 Issues**: [GitHub Issues](https://github.com/cafernandes/express-php/issues)
- **🔧 Exemplos**: Diretório `examples/`

## 🚨 Breaking Changes (v3.0)

1. **Namespace**: `Express\ApiExpress` → `Express\Core\Application`
2. **Boot**: Necessário chamar `$app->boot()` explicitamente
3. **Configuração**: Usar `configure()` ao invés de setters individuais
4. **Container**: Acesso via `$app->getContainer()`

---

**🎯 Recomendação**: Comece a migração em projetos novos e gradualmente atualize projetos existentes. A nova arquitetura oferece muito mais flexibilidade e recursos avançados!
