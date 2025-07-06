# AuthMiddleware

Middleware de autenticação multi-método (JWT, Basic, Bearer, API Key).

## Uso Básico
```php
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => 'sua_chave_secreta'
]));
```

## Configurações Disponíveis
- `authMethods`: Métodos aceitos (`jwt`, `basic`, `bearer`)
- `jwtSecret`: Chave secreta para JWT
- `basicAuthCallback`: Função de validação para Basic Auth
- `bearerAuthCallback`: Função de validação para Bearer/API Key

## Boas Práticas
- Use sempre HTTPS para rotas autenticadas.
