# 📚 Documentação Automática com OpenAPI/Swagger

O PivotPHP inclui um poderoso sistema de geração automática de documentação usando **OpenAPI 3.0.0** (Swagger). Com poucos comentários no seu código, você pode gerar documentação interativa profissional.

## 🚀 Introdução Rápida

### **Exemplo Básico**
```php
<?php
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Utils\OpenApiExporter;

$app = new Application();

/**
 * @api GET /users/:id
 * @summary Buscar usuário por ID
 * @description Retorna os dados completos de um usuário específico
 * @param {integer} id.path.required - ID do usuário
 * @produces application/json
 * @response 200 {User} Dados do usuário
 * @response 404 {Error} Usuário não encontrado
 */
$app->get('/users/:id', function($req, $res) {
    $userId = $req->params('id');
    $user = $userService->findById($userId);

    if (!$user) {
        return $res->status(404)->json(['error' => 'Usuário não encontrado']);
    }

    return $res->json($user);
});

// Gerar documentação OpenAPI
$docs = OpenApiExporter::export($app);

// Servir documentação Swagger UI
$app->get('/docs', function($req, $res) use ($docs) {
    return $res->json($docs);
});

$app->run();
```

## 📖 Anotações OpenAPI Suportadas

### **@api** - Definir Rota
```php
/**
 * @api POST /api/products
 * @summary Criar novo produto
 * @description Cria um novo produto no sistema
 */
```

### **@param** - Parâmetros
```php
/**
 * @param {string} name.body.required - Nome do produto
 * @param {number} price.body.required - Preço do produto
 * @param {integer} categoryId.path.required - ID da categoria
 * @param {string} filter.query.optional - Filtro opcional
 */
```

**Tipos de parâmetros:**
- `path` - Parâmetros na URL (`/users/:id`)
- `query` - Query strings (`?filter=value`)
- `body` - Corpo da requisição
- `header` - Headers HTTP

### **@produces** - Tipos de Resposta
```php
/**
 * @produces application/json
 * @produces text/plain
 * @produces multipart/form-data
 */
```

### **@response** - Respostas
```php
/**
 * @response 200 {Product} Produto criado com sucesso
 * @response 400 {ValidationError} Dados inválidos
 * @response 401 {AuthError} Não autorizado
 * @response 500 {Error} Erro interno do servidor
 */
```

### **@security** - Autenticação
```php
/**
 * @security bearerAuth
 * @security apiKey
 * @security basicAuth
 */
```

## 🏗️ Exemplo Completo com CRUD

```php
<?php
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Utils\OpenApiExporter;

$app = new Application();

/**
 * @api GET /api/products
 * @summary Listar produtos
 * @description Retorna lista paginada de produtos com filtros
 * @param {integer} page.query.optional - Página (padrão: 1)
 * @param {integer} limit.query.optional - Limite por página (padrão: 10)
 * @param {string} category.query.optional - Filtrar por categoria
 * @param {string} search.query.optional - Buscar por nome
 * @produces application/json
 * @response 200 {ProductList} Lista de produtos
 * @response 400 {Error} Parâmetros inválidos
 */
$app->get('/api/products', function($req, $res) {
    $page = $req->query('page', 1);
    $limit = $req->query('limit', 10);
    $category = $req->query('category');
    $search = $req->query('search');

    $products = $productService->list([
        'page' => $page,
        'limit' => $limit,
        'category' => $category,
        'search' => $search
    ]);

    return $res->json($products);
});

/**
 * @api GET /api/products/:id
 * @summary Buscar produto específico
 * @description Retorna dados completos de um produto
 * @param {integer} id.path.required - ID do produto
 * @produces application/json
 * @response 200 {Product} Dados do produto
 * @response 404 {Error} Produto não encontrado
 */
$app->get('/api/products/:id', function($req, $res) {
    $product = $productService->findById($req->params('id'));

    if (!$product) {
        return $res->status(404)->json(['error' => 'Produto não encontrado']);
    }

    return $res->json($product);
});

/**
 * @api POST /api/products
 * @summary Criar novo produto
 * @description Cria um novo produto no sistema
 * @param {string} name.body.required - Nome do produto
 * @param {string} description.body.optional - Descrição
 * @param {number} price.body.required - Preço (maior que 0)
 * @param {integer} categoryId.body.required - ID da categoria
 * @param {array} tags.body.optional - Tags do produto
 * @produces application/json
 * @security bearerAuth
 * @response 201 {Product} Produto criado
 * @response 400 {ValidationError} Dados inválidos
 * @response 401 {AuthError} Não autorizado
 */
$app->post('/api/products', function($req, $res) {
    $data = $req->json();

    // Validação
    if (!$data['name'] || !$data['price'] || !$data['categoryId']) {
        return $res->status(400)->json([
            'error' => 'Campos obrigatórios: name, price, categoryId'
        ]);
    }

    $product = $productService->create($data);

    return $res->status(201)->json($product);
});

/**
 * @api PUT /api/products/:id
 * @summary Atualizar produto
 * @description Atualiza dados de um produto existente
 * @param {integer} id.path.required - ID do produto
 * @param {string} name.body.optional - Nome do produto
 * @param {string} description.body.optional - Descrição
 * @param {number} price.body.optional - Preço
 * @param {integer} categoryId.body.optional - ID da categoria
 * @produces application/json
 * @security bearerAuth
 * @response 200 {Product} Produto atualizado
 * @response 404 {Error} Produto não encontrado
 * @response 401 {AuthError} Não autorizado
 */
$app->put('/api/products/:id', function($req, $res) {
    $id = $req->params('id');
    $data = $req->json();

    if (!$productService->exists($id)) {
        return $res->status(404)->json(['error' => 'Produto não encontrado']);
    }

    $product = $productService->update($id, $data);

    return $res->json($product);
});

/**
 * @api DELETE /api/products/:id
 * @summary Deletar produto
 * @description Remove um produto do sistema
 * @param {integer} id.path.required - ID do produto
 * @produces application/json
 * @security bearerAuth
 * @response 204 Produto deletado com sucesso
 * @response 404 {Error} Produto não encontrado
 * @response 401 {AuthError} Não autorizado
 */
$app->delete('/api/products/:id', function($req, $res) {
    $id = $req->params('id');

    if (!$productService->exists($id)) {
        return $res->status(404)->json(['error' => 'Produto não encontrado']);
    }

    $productService->delete($id);

    return $res->status(204)->send();
});

// Gerar e servir documentação
$docs = OpenApiExporter::export($app);

$app->get('/api/docs', function($req, $res) use ($docs) {
    return $res->json($docs);
});

$app->get('/api/docs/ui', function($req, $res) {
    $swaggerUi = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>API Documentation</title>
        <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@3.52.5/swagger-ui.css" />
    </head>
    <body>
        <div id="swagger-ui"></div>
        <script src="https://unpkg.com/swagger-ui-dist@3.52.5/swagger-ui-bundle.js"></script>
        <script>
            SwaggerUIBundle({
                url: "/api/docs",
                dom_id: "#swagger-ui",
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.presets.standalone
                ]
            });
        </script>
    </body>
    </html>';

    return $res->html($swaggerUi);
});

$app->run();
```

## 🔧 Configuração Avançada

### **Configurar Informações da API**
```php
$app = new Application([
    'openapi' => [
        'info' => [
            'title' => 'Minha API',
            'version' => '1.0.0',
            'description' => 'API de produtos e categorias',
            'contact' => [
                'name' => 'Suporte',
                'email' => 'suporte@exemplo.com'
            ],
            'license' => [
                'name' => 'MIT',
                'url' => 'https://opensource.org/licenses/MIT'
            ]
        ],
        'servers' => [
            [
                'url' => 'https://api.exemplo.com',
                'description' => 'Servidor de produção'
            ],
            [
                'url' => 'https://staging.exemplo.com',
                'description' => 'Servidor de teste'
            ]
        ]
    ]
]);
```

### **Definir Esquemas de Dados**
```php
/**
 * @schema Product
 * @property {integer} id - ID único do produto
 * @property {string} name - Nome do produto
 * @property {string} description - Descrição detalhada
 * @property {number} price - Preço em reais
 * @property {integer} categoryId - ID da categoria
 * @property {string} createdAt - Data de criação (ISO 8601)
 * @property {string} updatedAt - Data da última atualização
 */

/**
 * @schema ProductList
 * @property {array<Product>} data - Lista de produtos
 * @property {integer} total - Total de produtos
 * @property {integer} page - Página atual
 * @property {integer} limit - Limite por página
 */

/**
 * @schema Error
 * @property {string} error - Mensagem de erro
 * @property {integer} code - Código do erro
 */
```

### **Configurar Autenticação**
```php
$docs = OpenApiExporter::export($app, [
    'security' => [
        'bearerAuth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT'
        ],
        'apiKey' => [
            'type' => 'apiKey',
            'in' => 'header',
            'name' => 'X-API-Key'
        ],
        'basicAuth' => [
            'type' => 'http',
            'scheme' => 'basic'
        ]
    ]
]);
```

## 🎯 Exemplos Práticos

### **API com Upload de Arquivo**
```php
/**
 * @api POST /api/products/:id/image
 * @summary Upload de imagem do produto
 * @description Faz upload da imagem principal do produto
 * @param {integer} id.path.required - ID do produto
 * @param {file} image.formData.required - Arquivo de imagem (JPG, PNG)
 * @produces application/json
 * @consumes multipart/form-data
 * @security bearerAuth
 * @response 200 {ImageUpload} Upload realizado com sucesso
 * @response 400 {Error} Arquivo inválido
 * @response 404 {Error} Produto não encontrado
 */
$app->post('/api/products/:id/image', function($req, $res) {
    // Lógica de upload...
});
```

### **API com Validação Complexa**
```php
/**
 * @api POST /api/orders
 * @summary Criar pedido
 * @description Cria um novo pedido com validação de estoque
 * @param {integer} customerId.body.required - ID do cliente
 * @param {array<OrderItem>} items.body.required - Itens do pedido
 * @param {string} shippingAddress.body.required - Endereço de entrega
 * @param {string} paymentMethod.body.required - Método de pagamento (credit_card|pix|boleto)
 * @produces application/json
 * @security bearerAuth
 * @response 201 {Order} Pedido criado
 * @response 400 {ValidationError} Dados inválidos
 * @response 409 {StockError} Produto fora de estoque
 */
$app->post('/api/orders', function($req, $res) {
    // Lógica de criação de pedido...
});

/**
 * @schema OrderItem
 * @property {integer} productId - ID do produto
 * @property {integer} quantity - Quantidade (mín: 1)
 * @property {number} price - Preço unitário
 */
```

## 📱 Interface Swagger UI Customizada

### **Swagger UI com Tema Customizado**
```php
$app->get('/docs', function($req, $res) {
    $customSwaggerUi = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Minha API - Documentação</title>
        <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@3.52.5/swagger-ui.css" />
        <style>
            .swagger-ui .topbar { background-color: #2d3748; }
            .swagger-ui .info .title { color: #2d3748; }
        </style>
    </head>
    <body>
        <div id="swagger-ui"></div>
        <script src="https://unpkg.com/swagger-ui-dist@3.52.5/swagger-ui-bundle.js"></script>
        <script>
            SwaggerUIBundle({
                url: "/api/docs.json",
                dom_id: "#swagger-ui",
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.presets.standalone
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        </script>
    </body>
    </html>';

    return $res->html($customSwaggerUi);
});
```

## 🔍 Dicas e Boas Práticas

### **1. Organização das Anotações**
```php
/**
 * @api POST /api/users
 * @summary Criar usuário
 * @description Cria um novo usuário no sistema com validação de email único
 * @tags Users
 * @param {string} name.body.required - Nome completo (min: 2, max: 100)
 * @param {string} email.body.required - Email válido e único
 * @param {string} password.body.required - Senha (min: 8 caracteres)
 * @produces application/json
 * @consumes application/json
 * @response 201 {User} Usuário criado com sucesso
 * @response 400 {ValidationError} Dados inválidos
 * @response 409 {ConflictError} Email já existe
 * @example request
 * {
 *   "name": "João Silva",
 *   "email": "joao@exemplo.com",
 *   "password": "senha123456"
 * }
 * @example response 201
 * {
 *   "id": 1,
 *   "name": "João Silva",
 *   "email": "joao@exemplo.com",
 *   "createdAt": "2023-12-07T10:30:00Z"
 * }
 */
```

### **2. Agrupamento por Tags**
```php
/**
 * @api GET /api/users
 * @tags Users
 * @summary Listar usuários
 */

/**
 * @api GET /api/products
 * @tags Products
 * @summary Listar produtos
 */

/**
 * @api GET /api/orders
 * @tags Orders
 * @summary Listar pedidos
 */
```

### **3. Versionamento da API**
```php
/**
 * @api GET /api/v1/users
 * @summary Listar usuários (v1)
 * @deprecated true
 */

/**
 * @api GET /api/v2/users
 * @summary Listar usuários (v2)
 * @description Nova versão com paginação melhorada
 */
```

### **4. Respostas de Erro Consistentes**
```php
/**
 * @schema ApiError
 * @property {string} error - Mensagem de erro
 * @property {string} code - Código do erro
 * @property {string} timestamp - Timestamp do erro
 * @property {string} path - Caminho da requisição
 */

/**
 * @response 400 {ApiError} Requisição inválida
 * @response 401 {ApiError} Não autorizado
 * @response 403 {ApiError} Acesso negado
 * @response 404 {ApiError} Recurso não encontrado
 * @response 500 {ApiError} Erro interno do servidor
 */
```

## 🚀 Geração e Deploy da Documentação

### **Exportar para Arquivo**
```php
// Gerar e salvar documentação
$docs = OpenApiExporter::export($app);
file_put_contents('docs/api-spec.json', json_encode($docs, JSON_PRETTY_PRINT));

// Gerar HTML estático
$html = OpenApiExporter::generateHtml($docs);
file_put_contents('docs/api-docs.html', $html);
```

### **Integração com CI/CD**
```bash
# No seu script de deploy
php generate-docs.php
cp docs/api-spec.json public/
cp docs/api-docs.html public/docs.html
```

## 📋 Checklist de Qualidade

### **✅ Documentação Completa**
- [ ] Todas as rotas documentadas
- [ ] Parâmetros com tipos e validações
- [ ] Respostas de sucesso e erro
- [ ] Exemplos de requisição/resposta
- [ ] Esquemas de dados definidos
- [ ] Autenticação configurada

### **✅ Boas Práticas**
- [ ] Tags para agrupamento
- [ ] Descrições claras e úteis
- [ ] Códigos de status HTTP corretos
- [ ] Versionamento da API
- [ ] Testes da documentação
- [ ] Deploy automatizado

---

**💡 Dica:** A documentação OpenAPI do PivotPHP é gerada automaticamente a partir dos comentários do seu código. Mantenha-os sempre atualizados para ter uma documentação precisa!

---

*📖 Documentação atualizada em: 2 de julho de 2025*
