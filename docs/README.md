# Documentação Express PHP

## Índice

- [Introdução](#introducao)
- [Instalação](#instalacao)
- [Exemplo de Uso](#exemplo-de-uso)
- [Referência de Componentes](#referencia-de-componentes)
  - [ApiExpress](#apiexpress)
  - [Route](#route)
  - [Request e Response](#request-e-response)
- [Testes e Exemplos](#testes-e-exemplos)
- [Pontos Fortes e Fracos](#pontos-fortes-e-fracos)
- [Objetos e Funcionalidades](docs/objetos.md)
- [Exportação de Documentação OpenAPI](#exportacao-de-documentacao-openapi)

## Introdução

Express PHP é um microframework para criação de APIs RESTful inspirado no Express.js do Node.js, com sintaxe simples e modular.

## Instalação

Veja o README principal para instruções de instalação.

## Exemplo de Uso

Consulte o arquivo `test/app.php` para exemplos práticos de rotas e respostas.

## Referência de Componentes

### ApiExpress
Classe principal para inicialização da aplicação e definição de rotas.

### Route
Gerencia as rotas e seus métodos (GET, POST, etc).

### Request e Response
Facilitam o acesso aos dados da requisição e a construção das respostas.

## Testes e Exemplos

- `test/app.php`: Exemplo de aplicação
- `test/router.php`: Exemplos de rotas
- `test/layout.php`: Layout de resposta

## Pontos Fortes e Fracos

Veja o README principal para uma análise dos pontos fortes e fracos do projeto.

## Exportação de Documentação OpenAPI

A exportação da documentação OpenAPI é feita por um serviço dedicado: `OpenApiExporter`.

### Como gerar a documentação OpenAPI

Utilize o serviço `OpenApiExporter` para gerar o JSON OpenAPI a partir das rotas registradas no Router:

```php
use Express\SRC\Services\OpenApiExporter;
use Express\SRC\Controller\Router;

$openapi = OpenApiExporter::export(Router::class);
```

No exemplo do framework, a rota `/docs/openapi.json` já utiliza esse serviço para expor a documentação automática da API.

### Vantagens
- Separação de responsabilidades: o Router cuida apenas do roteamento, enquanto o serviço gera a documentação.
- Fácil integração com Swagger UI e outras ferramentas OpenAPI.

Veja exemplos práticos em `test/app.php` e no README principal.
