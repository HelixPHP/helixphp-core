# Implementação de Providers PSR

Este documento descreve a implementação completa do sistema de providers seguindo as PSRs 11, 14 e 3 no Express-PHP v2.1.1.

## Resumo da Implementação

### 1. PSR-11 (Container Interface)
- **Arquivo**: `src/Providers/Container.php`
- **Funcionalidades**:
  - Container PSR-11 compliant
  - Suporte para singletons e instâncias transitórias
  - Sistema de aliases
  - Binding de serviços concretos e factories
  - Tratamento de exceções PSR-11

### 2. PSR-14 (Event Dispatcher)
- **Arquivos**:
  - `src/Providers/EventDispatcher.php`
  - `src/Providers/ListenerProvider.php`
- **Funcionalidades**:
  - Event Dispatcher PSR-14 compliant
  - Listener Provider com suporte a herança de classes
  - Sistema de eventos stoppable
  - Registro dinâmico de listeners

### 3. PSR-3 (Logger Interface)
- **Arquivo**: `src/Providers/Logger.php`
- **Funcionalidades**:
  - Logger PSR-3 compliant
  - Suporte a todos os níveis de log
  - Interpolação de contexto em mensagens
  - Escrita segura em arquivo com fallback

## Service Providers

### Base ServiceProvider
- **Arquivo**: `src/Providers/ServiceProvider.php`
- Classe abstrata base para todos os providers
- Métodos: `register()`, `boot()`, `provides()`, `isDeferred()`

### Core Providers Implementados

#### 1. ContainerServiceProvider
- Registra o container PSR-11 no próprio container
- Cria alias 'container' para fácil acesso

#### 2. EventServiceProvider
- Registra EventDispatcher PSR-14
- Registra ListenerProvider
- Cria aliases 'events' e 'listeners'

#### 3. LoggingServiceProvider
- Registra Logger PSR-3
- Configura path automático de logs
- Cria aliases 'log' e 'logger'

## Eventos do Sistema

### Eventos Implementados
1. **ApplicationStarted**: Disparado quando a aplicação termina o boot
2. **RequestReceived**: Disparado quando uma requisição é recebida
3. **ResponseSent**: Disparado quando uma resposta é enviada

### Uso dos Eventos
```php
$app->addEventListener(ApplicationStarted::class, function(ApplicationStarted $event) {
    echo "App started at: " . $event->startTime->format('Y-m-d H:i:s');
});
```

## Integração com Application

### Mudanças na Classe Application
- Container agora é PSR-11 compliant
- Sistema de providers com lifecycle completo
- Métodos helper para acessar serviços PSR
- Compatibilidade com API anterior mantida

### Novos Métodos
- `addEventListener()`: Registrar listeners de eventos
- `dispatchEvent()`: Disparar eventos PSR-14
- `getLogger()`: Obter logger PSR-3
- `getEventDispatcher()`: Obter dispatcher PSR-14
- `bind()`, `singleton()`, `instance()`: Binding no container
- `make()`, `resolve()`: Resolução de serviços

## Qualidade de Código

### PHPStan Level 9
- Todos os arquivos PSR passam no PHPStan nível 9
- Tipagem rigorosa implementada
- Configuração específica para ignorar limitações do PSR-3

### Testes
- Suite completa de testes PSR (`PSRProvidersTest.php`)
- 10 testes cobrindo todas as funcionalidades
- Todos os testes existentes mantêm compatibilidade

### Exemplo Prático
- **Arquivo**: `examples/example_psr_providers.php`
- Demonstra uso completo dos providers PSR
- Inclui eventos, logging e container
- Endpoints para testar cada funcionalidade

## Benefícios da Implementação

1. **Padrões Industriais**: Segue PSRs amplamente adotadas
2. **Interoperabilidade**: Compatível com bibliotecas PSR
3. **Testabilidade**: Injeção de dependência facilita testes
4. **Extensibilidade**: Sistema de providers permite extensões
5. **Performance**: Container otimizado com cache
6. **Observabilidade**: Sistema de eventos para monitoramento

## Uso Prático

### Registrando um Provider Customizado
```php
class CustomServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('custom.service', function() {
            return new CustomService();
        });
    }
}

$app->register(CustomServiceProvider::class);
```

### Usando Serviços PSR
```php
// Logger PSR-3
$logger = $app->getLogger();
$logger->info('Message', ['context' => 'value']);

// Events PSR-14
$app->addEventListener(MyEvent::class, function($event) {
    // Handle event
});

// Container PSR-11
$service = $app->resolve('service.name');
```

## Conclusão

A implementação dos providers PSR no Express-PHP v2.1.1 estabelece uma base sólida e moderna para o framework, seguindo padrões industriais reconhecidos e mantendo total compatibilidade com código existente.
