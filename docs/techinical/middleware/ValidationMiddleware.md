# ValidationMiddleware

Middleware para validação de dados de entrada nas rotas.

## Uso
```php
$app->use(new ValidationMiddleware([
    'rules' => [
        'email' => 'required|email',
        'password' => 'required|min:8'
    ]
]));
```

## Configurações Disponíveis
- `rules`: Array de regras de validação

## Boas Práticas
- Valide sempre os dados de entrada do usuário.
