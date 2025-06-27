#!/bin/bash

# Express PHP - Instalador de Pre-commit Hook
# Configura as validações de qualidade de código para pre-commit

set -e

echo "🛠️  Configurando pre-commit hooks para Express PHP..."

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[⚠]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

# Verifica se estamos em um repositório git
if [ ! -d ".git" ]; then
    print_error "Este não é um repositório Git!"
    exit 1
fi

# Verifica se as dependências estão instaladas
if [ ! -d "vendor" ]; then
    print_warning "Dependências não encontradas. Instalando..."
    composer install
fi

# Método 1: Usando pre-commit framework (recomendado)
if command -v pre-commit >/dev/null 2>&1; then
    print_status "Framework pre-commit detectado. Configurando..."

    if [ -f ".pre-commit-config.yaml" ]; then
        pre-commit install
        print_success "Pre-commit hooks instalados via framework!"

        print_status "Testando hooks..."
        if pre-commit run --all-files; then
            print_success "Todos os hooks estão funcionando!"
        else
            print_warning "Alguns hooks falharam. Verifique os arquivos."
        fi
    else
        print_error "Arquivo .pre-commit-config.yaml não encontrado!"
        exit 1
    fi

# Método 2: Hook manual do Git
else
    print_status "Framework pre-commit não encontrado. Usando hook manual do Git..."

    # Cria diretório de hooks se não existir
    mkdir -p .git/hooks

    # Copia o script de pre-commit
    if [ -f "scripts/pre-commit" ]; then
        cp scripts/pre-commit .git/hooks/pre-commit
        chmod +x .git/hooks/pre-commit
        print_success "Hook manual instalado em .git/hooks/pre-commit"
    else
        print_error "Script pre-commit não encontrado em scripts/pre-commit!"
        exit 1
    fi

    # Testa o hook
    print_status "Testando hook manual..."
    if .git/hooks/pre-commit; then
        print_success "Hook manual está funcionando!"
    else
        print_warning "Hook manual falhou. Verifique os erros acima."
    fi
fi

echo ""
print_success "Configuração concluída! 🎉"
echo ""
echo "Validações configuradas:"
echo "  ✓ PHPStan (análise estática)"
echo "  ✓ PHPUnit (testes unitários)"
echo "  ✓ PSR-12 (padrão de código)"
echo "  ✓ Sintaxe PHP"
echo "  ✓ Verificações básicas de arquivo"
echo ""
echo "As validações serão executadas automaticamente antes de cada commit."
echo ""

# Instruções adicionais
if ! command -v pre-commit >/dev/null 2>&1; then
    print_warning "Para melhor experiência, instale o framework pre-commit:"
    echo "  pip install pre-commit"
    echo "  Depois execute: pre-commit install"
    echo ""
fi

print_status "Para testar manualmente, execute:"
echo "  ./scripts/pre-commit"
echo ""
print_status "Para pular as validações temporariamente, use:"
echo "  git commit --no-verify"
