#!/bin/bash

# HelixPHP - Instalador de Git Hooks
# Configura as validações de qualidade de código para pre-commit e pre-push

set -e

echo "🛠️  Configurando Git hooks para HelixPHP..."

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
    print_status "Framework pre-commit não encontrado. Usando hooks manuais do Git..."

    # Cria diretório de hooks se não existir
    mkdir -p .git/hooks

    # Instala pre-commit hook
    if [ -f "scripts/pre-commit" ]; then
        cp scripts/pre-commit .git/hooks/pre-commit
        chmod +x .git/hooks/pre-commit
        print_success "Pre-commit hook instalado em .git/hooks/pre-commit"
    else
        print_error "Script pre-commit não encontrado em scripts/pre-commit!"
        exit 1
    fi

    # Instala pre-push hook
    if [ -f "scripts/pre-push" ]; then
        cp scripts/pre-push .git/hooks/pre-push
        chmod +x .git/hooks/pre-push
        print_success "Pre-push hook instalado em .git/hooks/pre-push"
    else
        print_warning "Script pre-push não encontrado em scripts/pre-push"
    fi

    # Testa os hooks
    print_status "Testando pre-commit hook..."
    if .git/hooks/pre-commit; then
        print_success "Pre-commit hook está funcionando!"
    else
        print_warning "Pre-commit hook falhou. Verifique os erros acima."
    fi

    if [ -f ".git/hooks/pre-push" ]; then
        print_status "Pre-push hook instalado e pronto para uso"
        print_warning "Pre-push será testado no próximo push para o repositório remoto"
    fi
fi

echo ""
print_success "Configuração concluída! 🎉"
echo ""
echo "Git Hooks configurados:"
echo "  ✓ Pre-commit: Validações rápidas antes do commit"
echo "  ✓ Pre-push: Validação completa antes do push"
echo ""
echo "Validações incluídas:"
echo "  ✓ PHPStan (análise estática)"
echo "  ✓ PHPUnit (testes unitários)"
echo "  ✓ PSR-12 (padrão de código)"
echo "  ✓ Sintaxe PHP"
echo "  ✓ Verificações de estrutura"
echo "  ✓ Documentação (no pre-push)"
echo "  ✓ Benchmarks (no pre-push)"
echo ""
echo "Os hooks serão executados automaticamente:"
echo "• Pre-commit: Antes de cada commit"
echo "• Pre-push: Antes de cada push para o repositório remoto"
echo ""

# Instruções adicionais
if ! command -v pre-commit >/dev/null 2>&1; then
    print_warning "Para melhor experiência, instale o framework pre-commit:"
    echo "  pip install pre-commit"
    echo "  Depois execute: pre-commit install"
    echo ""
fi

print_status "Para testar manualmente:"
echo "  ./scripts/pre-commit     # Testa validações de commit"
echo "  ./scripts/pre-push       # Testa validação completa"
echo "  scripts/validate_all.sh  # Executa todos os testes"
echo ""
print_status "Para pular as validações temporariamente:"
echo "  git commit --no-verify   # Pula pre-commit"
echo "  git push --no-verify     # Pula pre-push"
