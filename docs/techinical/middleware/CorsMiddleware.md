# CorsMiddleware

Middleware para habilitar e configurar CORS (Cross-Origin Resource Sharing) na sua API.

## Uso
```php
$app->use(new CorsMiddleware());
```

## Configurações Disponíveis
- Permite customização de origens, métodos e headers.

## Boas Práticas
- Configure as origens permitidas conforme o ambiente.
