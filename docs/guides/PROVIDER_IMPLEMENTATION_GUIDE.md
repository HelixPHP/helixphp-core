# Guia de Implementação de Providers no Express PHP

Este guia ensina como criar, registrar e utilizar um **Provider** no Express PHP Framework para injeção de dependências, serviços e integrações customizadas.

---

## 📦 O que é um Provider?

Um **Provider** é uma classe responsável por registrar serviços, configurações ou integrações no container da aplicação. Ele permite desacoplar dependências e organizar recursos reutilizáveis.

---

## 1. Estrutura Básica de um Provider

```php
namespace App\Providers;

use Express\Providers\ServiceProvider;

class MeuProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registre serviços no container
        $this->app->bind('meu-servico', function() {
            return new \App\Servicos\MeuServico();
        });
    }

    public function boot(): void
    {
        // Inicialização opcional após todos os providers
    }
}
```

- **register()**: Registra serviços, bindings ou singletons.
- **boot()**: Executa lógica após todos os providers serem registrados (opcional).

---

## 2. Registrando o Provider na Aplicação

No arquivo de inicialização da sua aplicação:

```php
use App\Providers\MeuProvider;
use Express\Core\Application;

$app = new Application();
$app->registerProvider(MeuProvider::class);
```

Você pode registrar múltiplos providers:

```php
$app->registerProviders([
    MeuProvider::class,
    OutroProvider::class
]);
```

---

## 3. Utilizando Serviços Registrados

Após o provider ser registrado, acesse o serviço pelo container:

```php
$meuServico = $app->get('meu-servico');
$meuServico->executar();
```

---

## 4. Exemplo Completo

```php
// src/Providers/LoggerProvider.php
namespace App\Providers;

use Express\Providers\ServiceProvider;
use App\Servicos\Logger;

class LoggerProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('logger', function() {
            return new Logger('/var/log/app.log');
        });
    }
}

// index.php
use App\Providers\LoggerProvider;
use Express\Core\Application;

$app = new Application();
$app->registerProvider(LoggerProvider::class);

$logger = $app->get('logger');
$logger->info('Provider funcionando!');
```

---

## 5. Dicas Avançadas
- Use `singleton` para instâncias únicas.
- Providers podem depender de outros serviços já registrados.
- Utilize o método `boot()` para lógica pós-registro (ex: eventos, listeners).

---

## 6. Exemplo Prático: Provider em uma API

### a) Criando um serviço de repositório

```php
// src/Servicos/UserRepository.php
namespace App\Servicos;

class UserRepository
{
    public function all(): array
    {
        // Simulação de dados
        return [
            ['id' => 1, 'nome' => 'Alice'],
            ['id' => 2, 'nome' => 'Bob']
        ];
    }
}
```

### b) Criando o provider do repositório

```php
// src/Providers/UserRepositoryProvider.php
namespace App\Providers;

use Express\Providers\ServiceProvider;
use App\Servicos\UserRepository;

class UserRepositoryProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('user-repo', function() {
            return new UserRepository();
        });
    }
}
```

### c) Usando o provider na aplicação

```php
// public/index.php
use Express\Core\Application;
use App\Providers\UserRepositoryProvider;

$app = new Application();
$app->registerProvider(UserRepositoryProvider::class);

// Rota que utiliza o serviço injetado
$app->get('/api/users', function($req, $res) use ($app) {
    $repo = $app->get('user-repo');
    $users = $repo->all();
    $res->json(['users' => $users]);
});

$app->run();
```

### d) Integrando com middlewares

Você pode acessar serviços registrados em middlewares:

```php
$app->use(function($req, $res, $next) use ($app) {
    $logger = $app->get('logger');
    $logger->info('Nova requisição recebida!');
    return $next($req, $res);
});
```

---

## 7. Exemplo Avançado: Usando o método boot()

O método `boot()` permite executar lógica após todos os providers terem sido registrados. Isso é útil para:
- Registrar eventos/listeners
- Configurar middlewares globais
- Inicializar integrações externas

### a) Provider com evento customizado

```php
// src/Providers/EventProvider.php
namespace App\Providers;

use Express\Providers\ServiceProvider;

class EventProvider extends ServiceProvider
{
    public function register(): void
    {
        // Nenhum serviço registrado neste exemplo
    }

    public function boot(): void
    {
        // Registrar um listener para evento customizado
        $this->app->addAction('user.created', function($context) {
            // Exemplo: enviar e-mail de boas-vindas
            $user = $context['user'];
            // EmailService::sendWelcome($user['email']);
        });
    }
}
```

### b) Provider que registra middleware global no boot()

```php
// src/Providers/MiddlewareProvider.php
namespace App\Providers;

use Express\Providers\ServiceProvider;
use App\Servicos\Logger;

class MiddlewareProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('logger', function() {
            return new Logger('/var/log/app.log');
        });
    }

    public function boot(): void
    {
        // Adiciona middleware global após todos os providers
        $this->app->use(function($req, $res, $next) {
            $logger = $this->app->get('logger');
            $logger->info('Request: ' . $req->getPath());
            return $next($req, $res);
        });
    }
}
```

### c) Provider que inicializa integração externa

```php
// src/Providers/ExternalServiceProvider.php
namespace App\Providers;

use Express\Providers\ServiceProvider;
use App\Servicos\ExternalApiClient;

class ExternalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('external-api', function() {
            return new ExternalApiClient('api-key-123');
        });
    }

    public function boot(): void
    {
        // Inicializa conexão ou faz handshake
        $client = $this->app->get('external-api');
        $client->handshake();
    }
}
```

---

## 📚 Referências
- [Documentação Oficial - Providers](../DOCUMENTATION_INDEX.md)
- [Exemplo de Provider](../../examples/example_advanced_extension.php)

---

Pronto! Agora você pode criar e registrar providers para organizar e escalar sua aplicação Express PHP.
