#!/bin/bash

# Express PHP - Validador de Estrutura de Documentação
# Verifica se a documentação está organizada corretamente

set -e

echo "📚 Validando estrutura de documentação do Express PHP..."

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

# Função para verificar se arquivo existe e tem conteúdo
check_file() {
    local file="$1"
    local description="$2"

    if [ -f "$file" ]; then
        local size=$(wc -c < "$file" 2>/dev/null || echo "0")
        if [ "$size" -gt 100 ]; then
            print_success "$description existe e tem conteúdo adequado"
            return 0
        else
            print_warning "$description existe mas tem pouco conteúdo ($size bytes)"
            return 1
        fi
    else
        print_error "$description não encontrado: $file"
        return 1
    fi
}

# Contadores
ERRORS=0
WARNINGS=0

print_status "Verificando documentação principal..."

# Documentação principal (raiz do projeto)
check_file "README.md" "README principal" || ((ERRORS++))
check_file "CHANGELOG.md" "Changelog" || ((WARNINGS++))
check_file "CONTRIBUTING.md" "Guia de contribuição" || ((WARNINGS++))

print_status "Verificando estrutura da pasta docs/..."

# Documentação organizada
check_file "docs/DOCUMENTATION_INDEX.md" "Índice de documentação" || ((ERRORS++))
check_file "docs/README.md" "README da documentação" || ((WARNINGS++))

print_status "Verificando guias de usuário..."

# Guias
check_file "docs/guides/QUICK_START_GUIDE.md" "Guia de início rápido" || ((ERRORS++))
check_file "docs/guides/CUSTOM_MIDDLEWARE_GUIDE.md" "Guia de middleware customizado" || ((WARNINGS++))
check_file "docs/guides/STANDARD_MIDDLEWARES.md" "Guia de middlewares padrão" || ((WARNINGS++))
check_file "docs/guides/SECURITY_IMPLEMENTATION.md" "Guia de implementação de segurança" || ((WARNINGS++))
check_file "docs/guides/PRECOMMIT_SETUP.md" "Guia de configuração pre-commit" || ((WARNINGS++))

print_status "Verificando documentação de implementação..."

# Documentação de implementação
check_file "docs/implementation/PRECOMMIT_VALIDATION_COMPLETE.md" "Documentação de validação pre-commit" || ((WARNINGS++))
check_file "docs/implementation/COMPREHENSIVE_PERFORMANCE_SUMMARY_2025-06-27.md" "Relatório de performance" || ((WARNINGS++))

print_status "Verificando documentação de scripts..."

# Documentação de scripts
check_file "scripts/README.md" "Documentação dos scripts" || ((ERRORS++))

print_status "Verificando documentação de benchmarks..."

# Documentação de benchmarks
check_file "benchmarks/README.md" "Documentação dos benchmarks" || ((WARNINGS++))

print_status "Verificando documentação PT-BR..."

# Documentação em português
check_file "docs/pt-br/README.md" "README em português" || ((WARNINGS++))

echo ""
print_status "Verificando estrutura de diretórios..."

# Verifica se as pastas existem
REQUIRED_DIRS=(
    "docs"
    "docs/guides"
    "docs/implementation"
    "docs/development"
    "docs/pt-br"
)

for dir in "${REQUIRED_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        print_success "Diretório $dir existe"
    else
        print_error "Diretório $dir não encontrado"
        ((ERRORS++))
    fi
done

echo ""
print_status "Verificando links e referências..."

# Verifica se os links no README principal funcionam
if [ -f "README.md" ]; then
    # Verifica se há referências para docs/
    if grep -q "docs/" "README.md"; then
        print_success "README contém referências para documentação"
    else
        print_warning "README não parece referenciar a documentação em docs/"
        ((WARNINGS++))
    fi
fi

# Verifica índice de documentação
if [ -f "docs/DOCUMENTATION_INDEX.md" ]; then
    # Conta quantos links há no índice
    LINKS_COUNT=$(grep -c "\[.*\](" "docs/DOCUMENTATION_INDEX.md" 2>/dev/null || echo "0")
    if [ "$LINKS_COUNT" -gt 5 ]; then
        print_success "Índice de documentação tem $LINKS_COUNT links"
    else
        print_warning "Índice de documentação tem poucos links ($LINKS_COUNT)"
        ((WARNINGS++))
    fi
fi

echo ""
echo "==========================================  "
echo "🏁 RELATÓRIO FINAL DE VALIDAÇÃO"
echo "=========================================="

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    print_success "Estrutura de documentação perfeita! ✨"
    echo "Todos os arquivos essenciais estão presentes e bem organizados."
    exit 0
elif [ $ERRORS -eq 0 ]; then
    print_warning "Estrutura de documentação boa com $WARNINGS avisos"
    echo "Arquivos essenciais presentes, mas alguns opcionais estão ausentes."
    exit 0
else
    print_error "Estrutura de documentação tem problemas!"
    echo "Erros: $ERRORS | Avisos: $WARNINGS"
    echo ""
    echo "Corrija os erros antes de continuar:"
    echo "- Arquivos essenciais ausentes precisam ser criados"
    echo "- Verifique se a estrutura de pastas está correta"
    echo "- Ensure que os arquivos têm conteúdo adequado"
    exit 1
fi
