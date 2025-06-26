# 🚀 Publishing Guide - Express PHP

Este guia contém as instruções completas para publicar o Express PHP no Packagist e GitHub.

## 📋 Pré-requisitos

Antes de publicar, certifique-se de que:

- ✅ Todos os testes estão passando
- ✅ PHPStan nível 8 sem erros
- ✅ Código segue PSR-12
- ✅ Documentação está atualizada
- ✅ Changelog foi atualizado

## 🔍 Verificação Pré-Publicação

### 1. Execute o Script de Preparação

```bash
./scripts/prepare_release.sh
```

### 2. Validações Manuais

```bash
# Verificar PHPStan
./vendor/bin/phpstan analyse --no-progress

# Verificar testes
./vendor/bin/phpunit --no-coverage

# Verificar style
./vendor/bin/phpcs --standard=PSR12 SRC/ --report=summary

# Validar composer.json
composer validate --strict
```

### 3. Verificar Compatibilidade PHP

```bash
# Testar com diferentes versões do PHP
php7.4 -l SRC/**/*.php
php8.0 -l SRC/**/*.php
php8.1 -l SRC/**/*.php
```

## 🏷️ Criando uma Release

### 1. Preparar a Branch Main

```bash
# Certifique-se de estar na branch main
git checkout main
git pull origin main

# Verificar status
git status
```

### 2. Atualizar Versão

Atualize a versão nos seguintes locais se necessário:
- `composer.json` (version field - opcional para Packagist)
- `README.md` (badges e exemplos)
- Documentação

### 3. Criar Tag de Versão

```bash
# Formato de versionamento semântico: MAJOR.MINOR.PATCH
git tag -a v1.0.0 -m "Release v1.0.0

## Changes
- Initial stable release
- Complete Express.js-like API
- Security middlewares (CSRF, XSS, Auth)
- JWT authentication support
- OpenAPI documentation
- PHP 7.4+ compatibility
"

# Verificar tag
git tag -l
```

### 4. Push da Tag

```bash
# Push da tag irá automaticamente disparar o workflow de release
git push origin v1.0.0
```

## 🤖 Processo Automatizado

Após o push da tag, o GitHub Actions automaticamente:

1. **Validação**: Executa todos os testes e verificações
2. **Build**: Cria arquivos de distribuição (.tar.gz e .zip)
3. **Release**: Cria release no GitHub com changelog automático
4. **Packagist**: Notifica o Packagist para atualizar o pacote

## 📦 Publicação no Packagist

### Primeira Publicação

1. Acesse [Packagist.org](https://packagist.org)
2. Faça login com sua conta GitHub
3. Clique em "Submit"
4. Cole a URL do repositório: `https://github.com/CAFernandes/express-php`
5. Clique em "Check"
6. Confirme a submissão

### Configurar Auto-Update

Para atualizações automáticas futuras:

1. Acesse seu perfil no Packagist
2. Vá em "Your packages"
3. Clique no pacote "express-php/microframework"
4. Configure o "GitHub Service Hook" ou use o webhook

## 🔧 Configuração de Secrets (Maintainer)

Para que os workflows funcionem, configure os seguintes secrets no GitHub:

- `PACKAGIST_TOKEN`: Token de API do Packagist para auto-update

## 📊 Verificação Pós-Publicação

Após a publicação, verifique:

### No GitHub
- ✅ Release foi criada corretamente
- ✅ Arquivos de distribuição estão anexados
- ✅ Changelog está correto

### No Packagist
- ✅ Pacote aparece nos resultados de busca
- ✅ Versão mais recente está listada
- ✅ Downloads estão funcionando

### Teste de Instalação
```bash
# Teste em projeto limpo
mkdir test-install
cd test-install
composer init
composer require express-php/microframework
```

## 🚨 Troubleshooting

### Workflow de Release Falhou
- Verifique os logs no GitHub Actions
- Certifique-se que todos os testes passam
- Verifique se não há conflitos de dependências

### Packagist Não Atualizou
- Verifique o webhook/token de API
- Force uma atualização manual no Packagist
- Confirme que a tag foi criada corretamente

### Problemas de Compatibilidade
- Teste em todas as versões do PHP suportadas
- Verifique dependências conflitantes
- Confirme que o autoload está funcionando

## 📋 Checklist de Publicação

- [ ] Todos os testes passando
- [ ] PHPStan nível 8 sem erros
- [ ] Código segue PSR-12
- [ ] Documentação atualizada
- [ ] Changelog atualizado
- [ ] Versão bumped (se necessário)
- [ ] Tag criada no formato vX.Y.Z
- [ ] Tag pushed para origin
- [ ] Workflow de release executado com sucesso
- [ ] GitHub release criada
- [ ] Packagist atualizado
- [ ] Instalação testada

## 🔗 Links Úteis

- [Packagist.org](https://packagist.org)
- [Semantic Versioning](https://semver.org)
- [GitHub Releases](https://docs.github.com/en/repositories/releasing-projects-on-github)
- [Composer Documentation](https://getcomposer.org/doc/)

---

Desenvolvido com ❤️ para a comunidade PHP
