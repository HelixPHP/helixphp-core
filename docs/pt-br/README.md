# Express PHP Microframework

[![English](https://img.shields.io/badge/Language-English-blue)](../../README.md) [![Português](https://img.shields.io/badge/Language-Português-green)](README.md)

**Express PHP** é um microframework leve, rápido e seguro inspirado no Express.js para construir aplicações web e APIs modernas em PHP.

## 🚀 Novidade: Exemplos Modulares e Aprendizagem Guiada

A partir da versão 2025, o Express PHP traz uma coleção de exemplos modulares para facilitar o aprendizado e a especialização em cada recurso do framework. Veja a pasta `examples/`:

- `example_user.php`: Rotas de usuário e autenticação
- `example_product.php`: Rotas de produto, parâmetros e exemplos OpenAPI
- `example_upload.php`: Upload de arquivos com exemplos práticos
- `example_admin.php`: Rotas administrativas e autenticação
- `example_blog.php`: Rotas de blog
- `example_complete.php`: Integração de todos os recursos e documentação automática
- `example_security.php`: Demonstração dos middlewares de segurança

Cada exemplo utiliza sub-routers especializados, facilitando o estudo isolado de cada contexto. Os arquivos em `examples/snippets/` podem ser reutilizados em qualquer app Express PHP.

## 📚 Documentação Automática OpenAPI/Swagger

- **Agrupamento por tags**: Endpoints organizados por contexto (User, Produto, Upload, Admin, Blog) na interface Swagger
- **Múltiplos servers**: Documentação já inclui ambientes local, produção e homologação
- **Exemplos práticos**: Requests e responses de exemplo para facilitar testes e integração
- **Respostas globais**: Todos os endpoints já documentam respostas 400, 401, 404 e 500
- **BaseUrl dinâmica**: O campo `servers` é ajustado automaticamente conforme o ambiente

Acesse `/docs/index` para a interface interativa.

## 🎯 Como Estudar Cada Recurso

- Para aprender sobre rotas de usuário: rode `php examples/example_user.php`
- Para upload: `php examples/example_upload.php`
- Para produto: `php examples/example_product.php`
- Para admin: `php examples/example_admin.php`
- Para blog: `php examples/example_blog.php`
- Para segurança: `php examples/example_security.php`
- Para ver tudo integrado: `php examples/example_complete.php`

## 📁 Estrutura Recomendada para Projetos

```
examples/           # Exemplos práticos e didáticos
├── snippets/       # Sub-routers prontos para reuso
SRC/               # Framework e middlewares
├── Middlewares/   # Sistema de middlewares organizado
│   ├── Security/  # Middlewares de segurança (CSRF, XSS)
│   └── Core/      # Middlewares principais (CORS, Rate Limiting)
test/              # Testes e experimentos
docs/              # Documentação
├── en/            # Documentação em inglês
└── pt-br/         # Documentação em português
```

## 💡 Início Rápido

Você pode criar seu próprio app Express PHP copiando e adaptando qualquer exemplo da pasta `examples/`.

```php
<?php
require_once 'vendor/autoload.php';

use Express\SRC\ApiExpress;
use Express\SRC\Middlewares\Security\SecurityMiddleware;
use Express\SRC\Middlewares\Core\CorsMiddleware;

$app = new ApiExpress();

// Aplicar middleware de segurança
$app->use(SecurityMiddleware::create());

// Aplicar CORS
$app->use(new CorsMiddleware());

// Rota básica
$app->get('/', function($req, $res) {
    $res->json(['message' => 'Olá Express PHP!']);
});

// Rota protegida
$app->post('/api/users', function($req, $res) {
    $userData = $req->body;
    // Dados do usuário são automaticamente sanitizados pelo middleware de segurança
    $res->json(['message' => 'Usuário criado', 'data' => $userData]);
});

$app->run();
```

## 🛡️ Recursos de Segurança

O Express PHP inclui middlewares robustos de segurança:

- **Proteção CSRF**: Proteção contra Cross-Site Request Forgery
- **Proteção XSS**: Sanitização contra Cross-Site Scripting
- **Cabeçalhos de Segurança**: Cabeçalhos de segurança automáticos
- **Rate Limiting**: Limitação de taxa de requisições
- **Segurança de Sessão**: Configuração segura de sessão

## 📖 Documentação

- [🇺🇸 Documentação em Inglês](../en/README.md)
- [🇧🇷 Documentação em Português](README.md)
- [Documentação de Middlewares](../../SRC/Middlewares/README.md)
- [Objetos da API](objetos.md)

## 🔧 Instalação

1. Clone o repositório:
```bash
git clone https://github.com/your-username/Express-PHP.git
cd Express-PHP
```

2. Instale as dependências (se usando Composer):
```bash
composer install
```

3. Execute um exemplo:
```bash
php examples/example_complete.php
```

4. Abra seu navegador em `http://localhost:8000`

## 🌟 Funcionalidades

- ✅ **Sintaxe similar ao Express.js** para PHP
- ✅ **Roteamento automático** com suporte a parâmetros
- ✅ **Middlewares de segurança** (proteção CSRF, XSS)
- ✅ **Geração de documentação** OpenAPI/Swagger
- ✅ **Tratamento de upload** de arquivos
- ✅ **Suporte a CORS**
- ✅ **Rate limiting**
- ✅ **Validação de requisições**
- ✅ **Tratamento de erros**
- ✅ **Arquitetura modular**
- ✅ **Zero dependências externas** (Composer opcional)

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor, leia nosso [Guia de Contribuição](../../CONTRIBUTING.md) para detalhes.

## 📄 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](../../LICENSE) para detalhes.

## 🙏 Agradecimentos

- Inspirado no Express.js
- Construído para a comunidade PHP
- Projetado para desenvolvimento web moderno

---

**Express PHP** - Construindo aplicações PHP modernas com simplicidade e segurança.
