#!/bin/bash

# Script de Release Automatizado para PivotPHP
# Versão: 2.0.1
# Data: 26 de junho de 2025

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Funções de output
info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
    exit 1
}

title() {
    echo -e "${PURPLE}🚀 $1${NC}"
}

# Verificar se estamos na raiz do projeto
if [ ! -f "composer.json" ]; then
    error "Este script deve ser executado na raiz do projeto PivotPHP"
fi

# Verificar argumentos
if [ $# -eq 0 ]; then
    error "Uso: $0 <versao> [tipo-release]

Exemplos:
  $0 2.0.1 patch     # Patch release
  $0 2.1.0 minor     # Minor release
  $0 3.0.0 major     # Major release
  $0 2.0.0-rc.1 rc   # Release candidate"
fi

VERSION=$1
RELEASE_TYPE=${2:-"release"}
CURRENT_BRANCH=$(git branch --show-current)

title "PivotPHP Release Manager v1.0.0"
echo ""

info "Versão a ser criada: $VERSION"
info "Tipo de release: $RELEASE_TYPE"
info "Branch atual: $CURRENT_BRANCH"
echo ""

# Verificar se o branch está limpo
if [ -n "$(git status --porcelain)" ]; then
    error "Há mudanças não commitadas. Commit ou stash suas mudanças antes de continuar."
fi

# Verificar se estamos no branch correto
if [ "$CURRENT_BRANCH" != "main" ] && [ "$CURRENT_BRANCH" != "master" ] && [ "$CURRENT_BRANCH" != "modularization" ]; then
    read -p "Você está no branch '$CURRENT_BRANCH'. Continuar? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

echo ""
title "Executando verificações pré-release..."

# 1. Executar validação completa usando validate_all.sh
info "Executando validação completa do projeto..."
if [ -f "scripts/validate_all.sh" ]; then
    if scripts/validate_all.sh; then
        success "Todas as validações passaram"
    else
        error "Algumas validações falharam. Corrija os problemas antes de continuar."
    fi
else
    # Fallback para validações individuais se validate_all.sh não existir
    info "Executando testes..."
    if ./vendor/bin/phpunit --exclude-group streaming --stop-on-failure > /dev/null 2>&1; then
        success "Todos os testes passaram"
    else
        error "Alguns testes falharam. Corrija os problemas antes de continuar."
    fi

    # 2. Verificar PHPStan
    if [ -f "vendor/bin/phpstan" ]; then
        info "Executando PHPStan..."
        if ./vendor/bin/phpstan analyse --no-progress > /dev/null 2>&1; then
            success "PHPStan passou"
        else
            error "PHPStan encontrou problemas. Corrija antes de continuar."
        fi
    fi
fi

# 3. Validar composer.json
info "Validando composer.json..."
if composer validate --no-check-all --no-check-lock > /dev/null 2>&1; then
    success "composer.json válido"
else
    error "composer.json inválido"
fi

echo ""
title "Atualizando arquivos de versão..."

# 4. Atualizar versão no composer.json
info "Atualizando composer.json..."
sed -i.bak "s/\"version\": \".*\"/\"version\": \"$VERSION\"/" composer.json && rm composer.json.bak
success "Versão atualizada no composer.json"

# 5. Atualizar versão no README principal
if [ -f "README.md" ]; then
    info "Atualizando README.md..."
    sed -i.bak "s/v[0-9]\+\.[0-9]\+\.[0-9]\+/v$VERSION/g" README.md && rm README.md.bak
    success "Versão atualizada no README.md"
fi

# 6. Atualizar versão no README_v2.md
if [ -f "README_v2.md" ]; then
    info "Atualizando README_v2.md..."
    sed -i.bak "s/v[0-9]\+\.[0-9]\+\.[0-9]\+/v$VERSION/g" README_v2.md && rm README_v2.md.bak
    success "Versão atualizada no README_v2.md"
fi

# 7. Criar/Atualizar CHANGELOG
echo ""
info "Atualizando CHANGELOG..."

if [ ! -f "CHANGELOG.md" ]; then
    cat > CHANGELOG.md << EOF
# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [$VERSION] - $(date +%Y-%m-%d)

### Adicionado
- Release $VERSION do PivotPHP Framework

EOF
else
    # Adicionar nova versão no topo do changelog
    temp_file=$(mktemp)
    {
        head -n 7 CHANGELOG.md
        echo ""
        echo "## [$VERSION] - $(date +%Y-%m-%d)"
        echo ""
        echo "### Adicionado"
        echo "- Release $VERSION do PivotPHP Framework"
        echo ""
        tail -n +8 CHANGELOG.md
    } > "$temp_file"
    mv "$temp_file" CHANGELOG.md
fi

success "CHANGELOG.md atualizado"

# 8. Criar tag de release
echo ""
title "Criando release..."

info "Adicionando arquivos modificados..."
git add .

info "Criando commit de release..."
git commit -m "chore: release v$VERSION

- Update version to $VERSION in composer.json
- Update documentation with new version
- Update CHANGELOG.md with release notes

Release Type: $RELEASE_TYPE"

info "Criando tag v$VERSION..."
git tag -a "v$VERSION" -m "Release v$VERSION

PivotPHP Framework $VERSION - Modular Edition

$(date +%Y-%m-%d)

Release Type: $RELEASE_TYPE"

success "Tag v$VERSION criada"

# 9. Push para repositório
echo ""
read -p "Fazer push do release para o repositório remoto? (Y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Nn]$ ]]; then
    warning "Release criado localmente. Execute 'git push origin --tags' quando estiver pronto."
else
    info "Fazendo push do commit e tag..."
    git push origin "$CURRENT_BRANCH"
    git push origin "v$VERSION"
    success "Release enviado para o repositório remoto"
fi

echo ""
title "🎉 Release v$VERSION criado com sucesso!"
echo ""
info "Resumo do release:"
echo "  • Versão: $VERSION"
echo "  • Tipo: $RELEASE_TYPE"
echo "  • Branch: $CURRENT_BRANCH"
echo "  • Tag: v$VERSION"
echo "  • Data: $(date +%Y-%m-%d)"
echo ""
success "PivotPHP Framework está pronto para distribuição!"
echo ""
