#!/bin/bash

# Script de Versionamento Semântico para PivotPHP
# Automatiza bump de versões seguindo Semantic Versioning

set -e

# Cores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
success() { echo -e "${GREEN}✅ $1${NC}"; }
warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
error() { echo -e "${RED}❌ $1${NC}"; exit 1; }

# Função para extrair versão do composer.json
get_current_version() {
    if [ -f "composer.json" ]; then
        grep '"version"' composer.json | sed 's/.*"version": "\([^"]*\)".*/\1/'
    else
        echo "0.0.0"
    fi
}

# Função para incrementar versão
bump_version() {
    local version=$1
    local type=$2

    IFS='.' read -ra PARTS <<< "$version"
    local major=${PARTS[0]}
    local minor=${PARTS[1]}
    local patch=${PARTS[2]}

    case $type in
        "major")
            major=$((major + 1))
            minor=0
            patch=0
            ;;
        "minor")
            minor=$((minor + 1))
            patch=0
            ;;
        "patch")
            patch=$((patch + 1))
            ;;
        *)
            error "Tipo de bump inválido: $type. Use: major, minor, ou patch"
            ;;
    esac

    echo "$major.$minor.$patch"
}

# Verificar argumentos
if [ $# -eq 0 ]; then
    echo "Uso: $0 <tipo> [opções]"
    echo ""
    echo "Tipos de bump:"
    echo "  patch   - 1.0.0 → 1.0.1 (bug fixes)"
    echo "  minor   - 1.0.0 → 1.1.0 (new features)"
    echo "  major   - 1.0.0 → 2.0.0 (breaking changes)"
    echo ""
    echo "Opções:"
    echo "  --dry-run    Apenas mostra a nova versão sem aplicar"
    echo "  --no-commit  Não cria commit automaticamente"
    echo "  --no-tag     Não cria tag automaticamente"
    echo ""
    echo "Exemplos:"
    echo "  $0 patch              # Bump patch version"
    echo "  $0 minor --dry-run    # Ver nova versão minor"
    echo "  $0 major --no-commit  # Bump major sem commit"
    exit 1
fi

BUMP_TYPE=$1
DRY_RUN=false
NO_COMMIT=false
NO_TAG=false

# Parse options
shift
while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --no-commit)
            NO_COMMIT=true
            shift
            ;;
        --no-tag)
            NO_TAG=true
            shift
            ;;
        *)
            error "Opção desconhecida: $1"
            ;;
    esac
done

# Verificar se estamos na raiz do projeto
if [ ! -f "composer.json" ]; then
    error "composer.json não encontrado. Execute na raiz do projeto."
fi

# Obter versão atual
CURRENT_VERSION=$(get_current_version)
if [ "$CURRENT_VERSION" = "0.0.0" ]; then
    warning "Versão não encontrada no composer.json. Assumindo 0.0.0"
fi

# Calcular nova versão
NEW_VERSION=$(bump_version "$CURRENT_VERSION" "$BUMP_TYPE")

info "Versão atual: $CURRENT_VERSION"
info "Nova versão: $NEW_VERSION"
info "Tipo de bump: $BUMP_TYPE"

if [ "$DRY_RUN" = true ]; then
    success "Dry run: Nova versão seria $NEW_VERSION"
    exit 0
fi

# Confirmar com usuário
echo ""
read -p "Confirma o bump de $CURRENT_VERSION para $NEW_VERSION? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    warning "Operação cancelada"
    exit 0
fi

# Atualizar composer.json
info "Atualizando composer.json..."
if [ "$CURRENT_VERSION" = "0.0.0" ]; then
    # Adicionar versão se não existir
    sed -i.bak '2i\
    "version": "'$NEW_VERSION'",
' composer.json && rm composer.json.bak
else
    # Atualizar versão existente
    sed -i.bak "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEW_VERSION\"/" composer.json && rm composer.json.bak
fi

success "Versão atualizada para $NEW_VERSION"

# Criar commit se solicitado
if [ "$NO_COMMIT" = false ]; then
    info "Criando commit..."
    git add composer.json
    git commit -m "chore: bump version to $NEW_VERSION

Version bump: $CURRENT_VERSION → $NEW_VERSION
Type: $BUMP_TYPE"
    success "Commit criado"

    # Criar tag se solicitado
    if [ "$NO_TAG" = false ]; then
        info "Criando tag v$NEW_VERSION..."
        git tag -a "v$NEW_VERSION" -m "Version $NEW_VERSION

Bump type: $BUMP_TYPE
Previous version: $CURRENT_VERSION"
        success "Tag v$NEW_VERSION criada"
    fi
fi

echo ""
success "🎉 Versão bumped com sucesso!"
echo "  • $CURRENT_VERSION → $NEW_VERSION"
echo "  • Tipo: $BUMP_TYPE"
if [ "$NO_COMMIT" = false ]; then
    echo "  • Commit criado: ✅"
    if [ "$NO_TAG" = false ]; then
        echo "  • Tag criada: ✅"
    fi
fi
echo ""
info "Para publicar: git push origin --tags"
