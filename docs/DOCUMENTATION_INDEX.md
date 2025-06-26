# 📋 Índice da Documentação - Express PHP

Este arquivo serve como um mapa completo de toda a documentação disponível no projeto Express PHP.

## 📁 Estrutura da Documentação

```
docs/
├── README.md                          # 📚 Índice principal da documentação
├── DOCUMENTATION_INDEX.md             # 📋 Este arquivo - mapa completo
├── TEST_COVERAGE_REPORT.md            # 📊 Relatório de cobertura de testes
│
├── pt-br/                             # 🇧🇷 Documentação em Português
│   ├── README.md                      # Documentação principal PT-BR
│   ├── AUTH_MIDDLEWARE.md             # Sistema de autenticação completo
│   ├── MODULARIZATION.md              # Arquitetura modular do framework
│   ├── STREAMING.md                   # Streaming e Server-Sent Events
│   └── objetos.md                     # Referência de API e objetos
│
├── en/                                # 🇺🇸 Documentação em Inglês
│   ├── README.md                      # Complete English documentation
│   └── objects.md                     # API reference and objects
│
├── guides/                            # 📖 Guias de Usuário
│   ├── PUBLISHING_GUIDE.md            # Como publicar no Packagist
│   ├── READY_FOR_PUBLICATION.md       # Checklist de publicação
│   ├── SECURITY_IMPLEMENTATION.md     # Implementação de segurança
│   └── starter/                       # 🚀 Guia de início
│       └── README.md                  # Tutorial completo para iniciantes
│
├── development/                       # 🔧 Guias para Desenvolvedores
│   ├── DEVELOPMENT.md                 # Setup de ambiente de desenvolvimento
│   ├── COMPOSER_PSR4.md               # Configuração Composer e PSR-4
│   ├── INTERNATIONALIZATION.md        # Sistema de internacionalização
│   └── MIDDLEWARE_MIGRATION.md        # Migração e criação de middlewares
│
└── implementation/                    # 🛠️ Documentação Técnica
    └── AUTH_IMPLEMENTATION_SUMMARY.md # Sumário técnico da implementação
```

## 🎯 Guias por Objetivo

### 👶 Iniciante - Primeiros Passos
1. **[Guia de Início](guides/starter/README.md)** - Comece aqui!
2. **[README Principal PT-BR](pt-br/README.md)** - Visão geral completa
3. **[Exemplos Básicos](../examples/README.md)** - Códigos práticos

### 🔐 Implementando Autenticação
1. **[Sistema de Autenticação](pt-br/AUTH_MIDDLEWARE.md)** - Guia completo
2. **[Exemplo de Auth](../examples/example_auth.php)** - Código prático
3. **[Implementação Técnica](implementation/AUTH_IMPLEMENTATION_SUMMARY.md)** - Detalhes internos

### 🛡️ Segurança e Middlewares
1. **[Middlewares de Segurança](guides/SECURITY_IMPLEMENTATION.md)** - Guia completo
2. **[Exemplo de Segurança](../examples/example_security.php)** - Demonstração prática
3. **[Migração de Middlewares](development/MIDDLEWARE_MIGRATION.md)** - Desenvolvimento

### 📡 Streaming e Tempo Real
1. **[Guia de Streaming](pt-br/STREAMING.md)** - Documentação completa
2. **[Exemplo de Streaming](../examples/example_streaming.php)** - Código prático

### 🌍 Documentação Internacional
- **[Português](pt-br/README.md)** - Documentação completa em PT-BR
- **[English](en/README.md)** - Complete documentation in English

### 🚀 Publicação e Deploy
1. **[Guia de Publicação](guides/PUBLISHING_GUIDE.md)** - Como publicar
2. **[Checklist de Publicação](guides/READY_FOR_PUBLICATION.md)** - Status

### 🔧 Desenvolvimento Avançado
1. **[Setup de Desenvolvimento](development/DEVELOPMENT.md)** - Ambiente
2. **[Arquitetura Modular](pt-br/MODULARIZATION.md)** - Estrutura interna
3. **[Internacionalização](development/INTERNATIONALIZATION.md)** - Multi-idioma

## 📚 Referências Rápidas

### API e Objetos
- **[Referência PT-BR](pt-br/objetos.md)** - Todos os objetos e métodos
- **[English Reference](en/objects.md)** - Complete API reference

### Exemplos por Categoria
- **[Básicos](../examples/example_user.php)** - Usuários, rotas, JSON
- **[CRUD](../examples/example_product.php)** - API REST completa
- **[Upload](../examples/example_upload.php)** - Manipulação de arquivos
- **[Admin](../examples/example_admin.php)** - Área administrativa
- **[Blog](../examples/example_blog.php)** - Sistema de blog
- **[Streaming](../examples/example_streaming.php)** - Tempo real
- **[Completo](../examples/example_complete.php)** - Integração total

### Testes e Qualidade
- **[Relatório de Testes](TEST_COVERAGE_REPORT.md)** - Cobertura e estatísticas

## 🗺️ Mapas de Navegação

### Por Experiência

#### 🟢 Iniciante
```
1. guides/starter/README.md → Aprenda o básico
2. ../examples/example_user.php → Primeiro código
3. pt-br/README.md → Visão completa
```

#### 🟡 Intermediário
```
1. pt-br/AUTH_MIDDLEWARE.md → Sistema de auth
2. guides/SECURITY_IMPLEMENTATION.md → Segurança
3. ../examples/example_complete.php → App completo
```

#### 🔴 Avançado
```
1. pt-br/STREAMING.md → Streaming/SSE
2. development/MIDDLEWARE_MIGRATION.md → Middlewares customizados
3. pt-br/MODULARIZATION.md → Arquitetura interna
```

### Por Funcionalidade

#### 🔐 Autenticação
```
AUTH_MIDDLEWARE.md → ../examples/example_auth.php → implementation/AUTH_IMPLEMENTATION_SUMMARY.md
```

#### 🛡️ Segurança
```
SECURITY_IMPLEMENTATION.md → ../examples/example_security.php
```

#### 📡 Streaming
```
STREAMING.md → ../examples/example_streaming.php
```

#### 📚 Documentação
```
README.md → pt-br/README.md → en/README.md
```

## 🔍 Como Buscar Informações

### Por Palavra-chave
- **JWT**: `pt-br/AUTH_MIDDLEWARE.md`
- **CORS**: `guides/SECURITY_IMPLEMENTATION.md`
- **SSE**: `pt-br/STREAMING.md`
- **Upload**: `../examples/example_upload.php`
- **Middleware**: `development/MIDDLEWARE_MIGRATION.md`
- **API**: `pt-br/objetos.md`

### Por Caso de Uso
- **"Como fazer login?"** → `pt-br/AUTH_MIDDLEWARE.md`
- **"Como proteger rotas?"** → `guides/SECURITY_IMPLEMENTATION.md`
- **"Como fazer streaming?"** → `pt-br/STREAMING.md`
- **"Como começar?"** → `guides/starter/README.md`
- **"Como contribuir?"** → `../CONTRIBUTING.md`

## 📞 Precisa de Ajuda?

1. **Consulte primeiro**: [Guia de Início](guides/starter/README.md)
2. **Busque exemplos**: [Pasta Examples](../examples/)
3. **Documentação completa**: [README PT-BR](pt-br/README.md)
4. **Issues no GitHub**: [Express PHP Issues](https://github.com/CAFernandes/express-php/issues)
5. **Discussões**: [GitHub Discussions](https://github.com/CAFernandes/express-php/discussions)

---

**💡 Dica**: Este índice é mantido atualizado com a estrutura da documentação. Se encontrar alguma inconsistência, por favor, reporte!
