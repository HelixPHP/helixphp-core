#!/bin/bash

# Script de Geração de Documentação para Express-PHP
# Gera documentação automática, atualiza versões e organiza arquivos

set -e

# Cores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m'

info() { echo -e "${BLUE}📖 $1${NC}"; }
success() { echo -e "${GREEN}✅ $1${NC}"; }
warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
error() { echo -e "${RED}❌ $1${NC}"; exit 1; }
title() { echo -e "${PURPLE}📚 $1${NC}"; }

# Verificar se estamos na raiz do projeto
if [ ! -f "composer.json" ]; then
    error "Execute este script na raiz do projeto Express-PHP"
fi

title "Express-PHP Documentation Generator"
echo ""

# Obter versão atual
VERSION=$(grep '"version"' composer.json | sed 's/.*"version": "\([^"]*\)".*/\1/' || echo "2.0.0")
DATE=$(date +%Y-%m-%d)

info "Versão: $VERSION"
info "Data: $DATE"
echo ""

# 1. Atualizar README principal
info "Atualizando README.md..."

if [ ! -f "README.md" ]; then
    cat > README.md << EOF
# Express-PHP Framework

[![Version](https://img.shields.io/badge/version-v$VERSION-blue.svg)](https://github.com/CAFernandes/express-php/releases)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.1-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-219%20passing-brightgreen.svg)](tests/)

Express-PHP é um microframework moderno, rápido e seguro para PHP, inspirado no Express.js. Construído com arquitetura modular e PSR-4, oferece todas as ferramentas necessárias para desenvolvimento de APIs e aplicações web modernas.

## 🚀 Versão $VERSION - Modular Edition

Esta versão representa uma **completa modernização** do framework com:

- ✅ **Arquitetura Modular** com PSR-4
- ✅ **6 Middlewares de Segurança** (CORS, Auth, XSS, CSRF, Security, RateLimit)
- ✅ **6 Módulos Avançados** (Validation, Cache, Events, Logging, Support, Database)
- ✅ **219 Testes** com 92.4% de taxa de sucesso
- ✅ **Documentação Completa** em português
- ✅ **Compatibilidade Backward** mantida

## 📦 Instalação

\`\`\`bash
composer require express-php/microframework
\`\`\`

## 🏃‍♂️ Início Rápido

\`\`\`php
<?php
require_once 'vendor/autoload.php';

use Express\\ApiExpress;

\$app = new ApiExpress();

\$app->get('/', function() {
    return ['message' => 'Hello, Express-PHP v$VERSION!'];
});

\$app->listen(8080);
\`\`\`

## 📖 Documentação

- **[📘 Documentação Completa](README_v2.md)** - Guia completo em português
- **[🚀 Como Usar](examples/COMO_USAR.md)** - Tutorial prático
- **[📋 CHANGELOG](CHANGELOG.md)** - Histórico de versões
- **[🔧 Exemplos](examples/)** - Códigos de exemplo

## 🛡️ Recursos de Segurança

- **CORS** configurável para APIs
- **Autenticação** JWT, Basic Auth, Bearer Token
- **Proteção XSS** automática
- **CSRF** com tokens
- **Headers de Segurança** (HSTS, CSP, etc.)
- **Rate Limiting** para controle de tráfego

## 🧩 Módulos Avançados

- **Validation** - Sistema robusto de validação
- **Cache** - Cache em arquivo/memória com TTL
- **Events** - Sistema de eventos com prioridades
- **Logging** - Logger estruturado
- **Support** - Helpers utilitários (Str, Arr)
- **Database** - Conexão PDO simplificada

## 🧪 Testes

\`\`\`bash
# Executar todos os testes
./vendor/bin/phpunit

# Testes específicos (excluindo streaming)
./vendor/bin/phpunit --exclude-group streaming
\`\`\`

## 📊 Status dos Testes

- **219 testes passando** (92.4% sucesso)
- **Cobertura completa** de middlewares
- **Módulos avançados** validados
- **Integração** testada

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

## 📄 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 🔗 Links

- [GitHub](https://github.com/CAFernandes/express-php)
- [Packagist](https://packagist.org/packages/express-php/microframework)
- [Issues](https://github.com/CAFernandes/express-php/issues)
- [Releases](https://github.com/CAFernandes/express-php/releases)

---

**Express-PHP v$VERSION** - Construído com ❤️ para a comunidade PHP brasileira
EOF
else
    # Atualizar versão no README existente
    sed -i.bak "s/v[0-9]\+\.[0-9]\+\.[0-9]\+/v$VERSION/g" README.md && rm README.md.bak
fi

success "README.md atualizado"

# 2. Atualizar badge de versão
info "Atualizando badges de versão..."
for file in README.md README_v2.md; do
    if [ -f "$file" ]; then
        sed -i.bak "s/version-v[0-9]\+\.[0-9]\+\.[0-9]\+/version-v$VERSION/g" "$file" && rm "${file}.bak"
    fi
done

success "Badges atualizados"

# 3. Gerar índice de documentação
info "Gerando índice de documentação..."

cat > docs/INDEX.md << 'EOF'
# Express-PHP Framework - Índice de Documentação

## 📚 Documentação Principal

### Guias Iniciais
- [README Principal](../README.md) - Visão geral e início rápido
- [README v2](../README_v2.md) - Documentação completa em português
- [Como Usar](../examples/COMO_USAR.md) - Tutorial prático passo a passo
- [CHANGELOG](../CHANGELOG.md) - Histórico de versões e mudanças

### Relatórios de Status
- [Status Final](../FINAL_STATUS_REPORT.md) - Relatório final do projeto
- [Missão Cumprida](../MISSION_ACCOMPLISHED.md) - Resumo das conquistas
- [Relatório de Modularização](../MODULARIZATION_FINAL_REPORT.md) - Detalhes técnicos

## 🏗️ Documentação Técnica

### Arquitetura
- [Organização do Projeto](implementation/PROJECT_ORGANIZATION.md)
- [Resumo da Implementação](implementation/AUTH_IMPLEMENTATION_SUMMARY.md)
- [Conclusão do Projeto](implementation/PROJECT_COMPLETION.md)

### Desenvolvimento
- [Guia de Desenvolvimento](development/DEVELOPMENT.md)
- [Composer PSR-4](development/COMPOSER_PSR4.md)
- [Internacionalização](development/INTERNATIONALIZATION.md)
- [Migração de Middlewares](development/MIDDLEWARE_MIGRATION.md)

### Segurança
- [Implementação de Segurança](guides/SECURITY_IMPLEMENTATION.md)
- [Middleware de Autenticação](pt-br/AUTH_MIDDLEWARE.md)

## 🧩 Documentação dos Módulos

### Módulos Core
- [ApiExpress](../src/ApiExpress.php) - Facade principal
- [Router](../src/Routing/Router.php) - Sistema de roteamento
- [Request/Response](../src/Http/) - Objetos HTTP
- [Middleware Stack](../src/Middleware/) - Sistema de middleware

### Módulos Avançados
- [Validation](../src/Validation/) - Sistema de validação
- [Cache](../src/Cache/) - Sistema de cache
- [Events](../src/Events/) - Sistema de eventos
- [Logging](../src/Logging/) - Sistema de logging
- [Support](../src/Support/) - Utilitários
- [Database](../src/Database/) - Conexão de banco

## 🛡️ Middlewares de Segurança

- [CORS Middleware](../src/Middleware/Security/CorsMiddleware.php)
- [Auth Middleware](../src/Middleware/Security/AuthMiddleware.php)
- [Security Middleware](../src/Middleware/Security/SecurityMiddleware.php)
- [XSS Middleware](../src/Middleware/Security/XssMiddleware.php)
- [CSRF Middleware](../src/Middleware/Security/CsrfMiddleware.php)
- [Rate Limit Middleware](../src/Middleware/Core/RateLimitMiddleware.php)

## 📝 Exemplos Práticos

### Exemplos Básicos
- [App Completo](../examples/app.php) - Aplicação completa
- [App Limpo](../examples/app_clean.php) - Versão simplificada
- [Exemplo Modular](../examples/example_modular.php) - Arquitetura modular

### Exemplos Avançados
- [Módulos Avançados](../examples/example_advanced.php) - Todos os recursos
- [Segurança](../examples/example_security_new.php) - Middlewares de segurança
- [Streaming](../examples/example_streaming_new.php) - Resposta streaming
- [Autenticação](../examples/example_auth.php) - Sistema de auth

### Snippets
- [Rotas de Usuário](../examples/snippets/user_routes.php)
- [Rotas de Admin](../examples/snippets/admin_routes.php)
- [Utilitários de Segurança](../examples/snippets/utils_seguranca.php)
- [Validação](../examples/snippets/utils_validacao.php)

## 🧪 Testes

### Estrutura de Testes
- [Tests Directory](../tests/) - Todos os testes
- [Core Tests](../tests/Core/) - Testes dos módulos core
- [Security Tests](../tests/Security/) - Testes de segurança
- [Services Tests](../tests/Services/) - Testes de serviços

### Relatórios
- [Coverage Report](TEST_COVERAGE_REPORT.md) - Relatório de cobertura

## 🚀 Scripts de Desenvolvimento

- [Release Script](../scripts/release.sh) - Script de release
- [Version Bump](../scripts/version-bump.sh) - Versionamento semântico
- [Validation Script](../scripts/validate_project.php) - Validação do projeto

## 🌐 Idiomas

### Português (pt-br)
- [README](pt-br/README.md)
- [Objetos](pt-br/objetos.md)
- [Auth Middleware](pt-br/AUTH_MIDDLEWARE.md)

### English (en)
- [README](en/README.md)
- [Objects](en/objects.md)

## 📦 Informações do Projeto

- **Versão Atual**: EOF

# Adicionar versão dinamicamente
echo "$VERSION" >> docs/INDEX.md

cat >> docs/INDEX.md << 'EOF'
- **PHP Mínimo**: 8.1+
- **Licença**: MIT
- **Testes**: 219 passando (92.4%)
- **Arquitetura**: PSR-4 Modular

---

*Documentação gerada automaticamente em EOF

echo "$DATE*" >> docs/INDEX.md

success "Índice de documentação gerado"

# 4. Validar links da documentação
info "Validando estrutura da documentação..."

# Verificar se arquivos principais existem
required_files=(
    "README.md"
    "README_v2.md"
    "CHANGELOG.md"
    "examples/COMO_USAR.md"
)

missing_files=()
for file in "${required_files[@]}"; do
    if [ ! -f "$file" ]; then
        missing_files+=("$file")
    fi
done

if [ ${#missing_files[@]} -gt 0 ]; then
    warning "Arquivos de documentação ausentes:"
    for file in "${missing_files[@]}"; do
        echo "  - $file"
    done
else
    success "Todos os arquivos de documentação estão presentes"
fi

# 5. Gerar sumário de arquivos de exemplo
info "Atualizando sumário de exemplos..."

cat > examples/README.md << EOF
# Exemplos do Express-PHP Framework

Esta pasta contém exemplos práticos demonstrando todas as funcionalidades do Express-PHP v$VERSION.

## 📚 Guias e Documentação

- **[COMO_USAR.md](COMO_USAR.md)** - Tutorial completo em português

## 🚀 Exemplos Principais

### Aplicações Completas
- **[app.php](app.php)** - Aplicação completa com todos os recursos

### Funcionalidades Específicas
- **[example_basic.php](example_basic.php)** - API REST básica e conceitos fundamentais
- **[example_auth.php](example_auth.php)** - Sistema completo de autenticação
- **[example_auth_simple.php](example_auth_simple.php)** - Implementação básica de JWT
- **[example_middleware.php](example_middleware.php)** - Middlewares avançados e API de produtos
- **[example_middleware.php](example_middleware.php)** - Middlewares avançados e API de produtos

## 🧩 Snippets Reutilizáveis

A pasta **[snippets/](snippets/)** contém códigos reutilizáveis:

- **[user_routes.php](snippets/user_routes.php)** - Rotas de usuário
- **[admin_routes.php](snippets/admin_routes.php)** - Rotas administrativas
- **[auth_snippets.php](snippets/auth_snippets.php)** - Autenticação
- **[utils_seguranca.php](snippets/utils_seguranca.php)** - Utilitários de segurança
- **[utils_validacao.php](snippets/utils_validacao.php)** - Validação de dados

## 🏃‍♂️ Como Executar

### Servidor PHP Built-in
\`\`\`bash
# Executar um exemplo específico
php -S localhost:8080 examples/app.php

# Ou usar o script helper
cd examples && ./start-server.sh
\`\`\`

### Docker (se disponível)
\`\`\`bash
docker run -p 8080:8080 -v \$(pwd):/app php:8.1-cli php -S 0.0.0.0:8080 /app/examples/app.php
\`\`\`

## 📖 Estrutura dos Exemplos

Cada exemplo segue esta estrutura:

1. **Configuração** - Autoload e configurações iniciais
2. **Middlewares** - Configuração de middlewares de segurança
3. **Rotas** - Definição das rotas da aplicação
4. **Handlers** - Lógica de negócio
5. **Inicialização** - Start do servidor

## 🔧 Requisitos

- PHP 8.1 ou superior
- Composer (para autoload)
- Extensões: json, mbstring, openssl (para JWT)

## 📝 Notas

- Todos os exemplos são auto-contidos
- Use CORS development apenas em desenvolvimento
- Configure adequadamente em produção
- Veja [COMO_USAR.md](COMO_USAR.md) para guia detalhado

---

**Express-PHP v$VERSION** - Exemplos atualizados em $DATE
EOF

success "Sumário de exemplos atualizado"

echo ""
success "🎉 Documentação gerada com sucesso!"
echo ""
info "Arquivos atualizados:"
echo "  • README.md"
echo "  • docs/INDEX.md"
echo "  • examples/README.md"
echo "  • Badges de versão"
echo ""
info "Versão: $VERSION"
info "Data: $DATE"
echo ""
