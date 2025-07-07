# 🎭 Mocks e Stubs

Guia prático para usar mocks e stubs nos testes do PivotPHP.

## 🤔 Conceitos Básicos

### Mock vs Stub vs Fake

- **Mock**: Objeto que verifica se métodos foram chamados corretamente
- **Stub**: Objeto que retorna valores pré-definidos
- **Fake**: Implementação simplificada que funciona (ex: banco em memória)

## 🛠️ Mockando Dependências Externas

### 1. Mock de Banco de Dados

```php
<?php
// tests/Mocks/MockPDO.php
class MockPDO extends PDO
{
    private array $mockData = [];
    private array $queries = [];

    public function __construct()
    {
        // Não chamar parent::__construct()
    }

    public function prepare($statement, $driver_options = [])
    {
        $this->queries[] = $statement;
        return new MockPDOStatement($this->mockData);
    }

    public function setMockData(array $data): void
    {
        $this->mockData = $data;
    }

    public function getExecutedQueries(): array
    {
        return $this->queries;
    }
}

class MockPDOStatement extends PDOStatement
{
    private array $data;
    private array $params = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function execute($input_parameters = null): bool
    {
        if ($input_parameters) {
            $this->params = $input_parameters;
        }
        return true;
    }

    public function fetch($fetch_style = PDO::FETCH_BOTH, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {
        return $this->data[0] ?? false;
    }

    public function fetchAll($fetch_style = PDO::FETCH_BOTH, $fetch_argument = null, $ctor_args = null): array
    {
        return $this->data;
    }
}
```

### 2. Usando Mock de Banco

```php
<?php
class UserServiceTest extends TestCase
{
    private MockPDO $mockPdo;
    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = new MockPDO();
        $this->userService = new UserService($this->mockPdo);
    }

    public function test_busca_usuario_por_id(): void
    {
        // Configurar dados mock
        $this->mockPdo->setMockData([
            [
                'id' => 1,
                'name' => 'João Silva',
                'email' => 'joao@email.com',
                'role' => 'user'
            ]
        ]);

        // Executar método
        $user = $this->userService->findById(1);

        // Verificar resultado
        $this->assertNotNull($user);
        $this->assertEquals('João Silva', $user['name']);
        $this->assertEquals('joao@email.com', $user['email']);

        // Verificar se query foi executada
        $queries = $this->mockPdo->getExecutedQueries();
        $this->assertCount(1, $queries);
        $this->assertStringContains('SELECT', $queries[0]);
    }

    public function test_cria_novo_usuario(): void
    {
        $userData = [
            'name' => 'Maria Santos',
            'email' => 'maria@email.com',
            'password' => 'senha123'
        ];

        $user = $this->userService->create($userData);

        $this->assertArrayHasKey('id', $user);
        $this->assertEquals('Maria Santos', $user['name']);

        // Verificar se INSERT foi executado
        $queries = $this->mockPdo->getExecutedQueries();
        $this->assertStringContains('INSERT', $queries[0]);
    }
}
```

## 📧 Mock de Serviços Externos

### Mock de Serviço de Email

```php
<?php
// tests/Mocks/MockEmailService.php
class MockEmailService implements EmailServiceInterface
{
    private array $sentEmails = [];
    private bool $shouldFail = false;

    public function send(string $to, string $subject, string $body): bool
    {
        if ($this->shouldFail) {
            throw new \Exception('Email service unavailable');
        }

        $this->sentEmails[] = [
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'sent_at' => time()
        ];

        return true;
    }

    public function getSentEmails(): array
    {
        return $this->sentEmails;
    }

    public function simulateFailure(): void
    {
        $this->shouldFail = true;
    }

    public function reset(): void
    {
        $this->sentEmails = [];
        $this->shouldFail = false;
    }
}

// Teste usando o mock
class NotificationServiceTest extends TestCase
{
    private MockEmailService $mockEmailService;
    private NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockEmailService = new MockEmailService();
        $this->notificationService = new NotificationService($this->mockEmailService);
    }

    public function test_envia_notificacao_por_email(): void
    {
        $user = ['email' => 'user@email.com', 'name' => 'João'];
        $message = 'Bem-vindo ao sistema!';

        $result = $this->notificationService->sendWelcomeEmail($user, $message);

        $this->assertTrue($result);

        // Verificar se email foi enviado
        $sentEmails = $this->mockEmailService->getSentEmails();
        $this->assertCount(1, $sentEmails);
        $this->assertEquals('user@email.com', $sentEmails[0]['to']);
        $this->assertStringContains('Bem-vindo', $sentEmails[0]['subject']);
        $this->assertStringContains('João', $sentEmails[0]['body']);
    }

    public function test_trata_falha_no_servico_de_email(): void
    {
        $this->mockEmailService->simulateFailure();

        $user = ['email' => 'user@email.com', 'name' => 'João'];
        $message = 'Teste';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email service unavailable');

        $this->notificationService->sendWelcomeEmail($user, $message);
    }
}
```

## 🌐 Mock de APIs Externas

### Mock de Cliente HTTP

```php
<?php
// tests/Mocks/MockHttpClient.php
class MockHttpClient implements HttpClientInterface
{
    private array $responses = [];
    private array $requests = [];

    public function get(string $url, array $headers = []): array
    {
        $this->requests[] = ['method' => 'GET', 'url' => $url, 'headers' => $headers];

        return $this->responses[$url] ?? ['status' => 404, 'body' => 'Not found'];
    }

    public function post(string $url, array $data, array $headers = []): array
    {
        $this->requests[] = [
            'method' => 'POST',
            'url' => $url,
            'data' => $data,
            'headers' => $headers
        ];

        return $this->responses[$url] ?? ['status' => 200, 'body' => 'Success'];
    }

    public function setResponse(string $url, array $response): void
    {
        $this->responses[$url] = $response;
    }

    public function getRequests(): array
    {
        return $this->requests;
    }
}

// Teste de integração com API externa
class PaymentServiceTest extends TestCase
{
    private MockHttpClient $mockHttpClient;
    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockHttpClient = new MockHttpClient();
        $this->paymentService = new PaymentService($this->mockHttpClient);
    }

    public function test_processa_pagamento_com_sucesso(): void
    {
        // Configurar resposta da API mock
        $this->mockHttpClient->setResponse('https://api.payment.com/charge', [
            'status' => 200,
            'body' => json_encode([
                'id' => 'pay_123',
                'status' => 'succeeded',
                'amount' => 1000
            ])
        ]);

        $result = $this->paymentService->processPayment([
            'amount' => 1000,
            'currency' => 'BRL',
            'source' => 'card_token'
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('pay_123', $result['payment_id']);

        // Verificar se requisição foi feita corretamente
        $requests = $this->mockHttpClient->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('POST', $requests[0]['method']);
        $this->assertEquals('https://api.payment.com/charge', $requests[0]['url']);
    }

    public function test_trata_falha_no_pagamento(): void
    {
        $this->mockHttpClient->setResponse('https://api.payment.com/charge', [
            'status' => 400,
            'body' => json_encode([
                'error' => 'card_declined',
                'message' => 'Your card was declined'
            ])
        ]);

        $result = $this->paymentService->processPayment([
            'amount' => 1000,
            'currency' => 'BRL',
            'source' => 'invalid_card'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('card_declined', $result['error']);
    }
}
```

## 🎭 Usando PHPUnit Mocks

### Mock com PHPUnit

```php
<?php
class UserControllerTest extends TestCase
{
    public function test_lista_usuarios_com_mock_phpunit(): void
    {
        // Criar mock do serviço
        $mockUserService = $this->createMock(UserService::class);

        // Configurar expectativas
        $mockUserService->expects($this->once())
                       ->method('getAllUsers')
                       ->willReturn([
                           ['id' => 1, 'name' => 'João'],
                           ['id' => 2, 'name' => 'Maria']
                       ]);

        // Criar controller com mock
        $controller = new UserController($mockUserService);

        // Executar método
        $request = $this->createMockRequest('GET', '/users');
        $response = $this->createMockResponse();

        $controller->index($request, $response);

        // Verificar resposta
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertCount(2, $body['users']);
    }

    public function test_cria_usuario_com_stub(): void
    {
        // Criar stub que sempre retorna sucesso
        $stubUserService = $this->createStub(UserService::class);
        $stubUserService->method('create')
                       ->willReturn(['id' => 1, 'name' => 'Novo User']);

        $controller = new UserController($stubUserService);

        $request = $this->createMockRequest('POST', '/users', [
            'name' => 'Novo User',
            'email' => 'novo@email.com'
        ]);
        $response = $this->createMockResponse();

        $controller->create($request, $response);

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Novo User', $body['user']['name']);
    }
}
```

## 🏪 Fake de Cache

### Implementação de Cache Fake

```php
<?php
// tests/Fakes/FakeCache.php
class FakeCache implements CacheInterface
{
    private array $data = [];

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $this->data[$key] = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        return true;
    }

    public function has(string $key): bool
    {
        if (!isset($this->data[$key])) {
            return false;
        }

        if ($this->data[$key]['expires'] < time()) {
            unset($this->data[$key]);
            return false;
        }

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->data[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->data = [];
        return true;
    }

    public function getAll(): array
    {
        return $this->data;
    }
}

// Teste usando cache fake
class CachedUserServiceTest extends TestCase
{
    private FakeCache $fakeCache;
    private MockPDO $mockPdo;
    private CachedUserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeCache = new FakeCache();
        $this->mockPdo = new MockPDO();
        $this->userService = new CachedUserService($this->mockPdo, $this->fakeCache);
    }

    public function test_busca_usuario_usa_cache(): void
    {
        // Primeiro acesso - deve buscar no banco
        $this->mockPdo->setMockData([
            ['id' => 1, 'name' => 'João', 'email' => 'joao@email.com']
        ]);

        $user1 = $this->userService->findById(1);
        $this->assertEquals('João', $user1['name']);

        // Segundo acesso - deve usar cache
        $this->mockPdo->setMockData([]); // Limpar dados mock

        $user2 = $this->userService->findById(1);
        $this->assertEquals('João', $user2['name']); // Ainda funciona por causa do cache

        // Verificar se dados estão no cache
        $this->assertTrue($this->fakeCache->has('user:1'));
    }

    public function test_cache_expira_corretamente(): void
    {
        // Adicionar no cache com TTL baixo
        $this->fakeCache->set('user:1', ['name' => 'João'], 1);

        $this->assertTrue($this->fakeCache->has('user:1'));

        // Aguardar expiração
        sleep(2);

        $this->assertFalse($this->fakeCache->has('user:1'));
    }
}
```

## 🧪 Stub de Request/Response

### Request Stub

```php
<?php
// tests/Stubs/RequestStub.php
class RequestStub implements ServerRequestInterface
{
    private array $attributes = [];
    private array $queryParams = [];
    private array $parsedBody = [];
    private array $headers = [];
    private string $method = 'GET';
    private UriInterface $uri;

    public function __construct(string $method = 'GET', string $uri = '/')
    {
        $this->method = $method;
        $this->uri = new Uri($uri);
    }

    public function withAttribute($name, $value): self
    {
        $new = clone $this;
        $new->attributes[$name] = $value;
        return $new;
    }

    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withQueryParams(array $query): self
    {
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withParsedBody($data): self
    {
        $new = clone $this;
        $new->parsedBody = $data;
        return $new;
    }

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withHeader($name, $value): self
    {
        $new = clone $this;
        $new->headers[$name] = [$value];
        return $new;
    }

    public function getHeaderLine($name): string
    {
        return $this->headers[$name][0] ?? '';
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    // Implementar outros métodos conforme necessário...
}
```

## 💡 Dicas de Boas Práticas

### ✅ O que Fazer
- **Use mocks para verificar comportamento** (se métodos foram chamados)
- **Use stubs para controlar retornos** (dados específicos)
- **Use fakes para sistemas complexos** (banco em memória)
- **Reset mocks entre testes** para evitar interferências
- **Mock apenas interfaces**, não implementações concretas

### ❌ O que Evitar
- **Não mocke valor objects** simples (strings, arrays)
- **Não over-mock** - mocke apenas o que precisa
- **Não mocke código próprio** desnecessariamente
- **Não faça testes dependentes** do estado dos mocks

---

*🎭 Mocks e stubs são fundamentais para testes rápidos, confiáveis e isolados!*
