# RateLimitMiddleware

Middleware para controle de taxa de requisições (Rate Limiting).

## Uso
```php
$app->use(new RateLimitMiddleware([
    'limit' => 100,
    'window' => 60
]));
```

## Configurações Disponíveis
- `limit`: Número máximo de requisições por janela
- `window`: Janela de tempo em segundos

## Boas Práticas
- Ajuste os limites conforme o perfil da aplicação.
