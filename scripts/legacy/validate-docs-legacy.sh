#!/bin/bash

# PivotPHP v1.0.0 - Validador de Documentação Consolidada
# Verifica se a documentação está organizada corretamente após consolidação

set -e

echo "📚 Validando estrutura de documentação consolidada do PivotPHP v1.0.0..."

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
    local min_size="${3:-100}"

    if [ -f "$file" ]; then
        local size=$(wc -c < "$file" 2>/dev/null || echo "0")
        if [ "$size" -gt "$min_size" ]; then
            print_success "$description existe e tem conteúdo adequado ($size bytes)"
            return 0
        else
            print_warning "$description existe mas tem pouco conteúdo ($size bytes, mínimo: $min_size)"
            return 1
        fi
    else
        print_error "$description não encontrado: $file"
        return 1
    fi
}

# Função para verificar se diretório existe e tem arquivos
check_directory() {
    local dir="$1"
    local description="$2"
    local min_files="${3:-1}"

    if [ -d "$dir" ]; then
        local file_count=$(find "$dir" -type f | wc -l)
        if [ "$file_count" -ge "$min_files" ]; then
            print_success "$description existe e tem $file_count arquivo(s)"
            return 0
        else
            print_warning "$description existe mas tem poucos arquivos ($file_count, mínimo: $min_files)"
            return 1
        fi
    else
        print_error "$description não encontrado: $dir"
        return 1
    fi
# Contadores
ERRORS=0
WARNINGS=0

print_status "Verificando documentação principal consolidada..."

# Documentação principal (nova estrutura v1.0.0)
check_file "README.md" "README principal" || ((ERRORS++))
check_file "FRAMEWORK_OVERVIEW_v1.0.0.md" "Guia completo v1.0.0 (PRINCIPAL)" 1000 || ((ERRORS++))
check_file "DOCUMENTATION_GUIDE.md" "Guia de navegação" || ((ERRORS++))
check_file "CHANGELOG.md" "Changelog" || ((WARNINGS++))
check_file "CONTRIBUTING.md" "Guia de contribuição" || ((WARNINGS++))

print_status "Verificando se arquivos redundantes foram removidos..."

# Arquivos que DEVEM ter sido removidos (redundantes)
REDUNDANT_FILES=(
    "README_v1.0.0.md"
    "PERFORMANCE_REPORT_FINAL.md"
    "TECHNICAL_OPTIMIZATION_SUMMARY.md"
    "CONSOLIDATION_SUMMARY_v1.0.0.md"
    "ADVANCED_OPTIMIZATIONS_REPORT.md"
    "OPTIMIZATION_FINAL_REPORT.md"
)

for file in "${REDUNDANT_FILES[@]}"; do
    if [ -f "$file" ]; then
        print_warning "Arquivo redundante ainda existe: $file (deveria ter sido removido)"
        ((WARNINGS++))
    else
        print_success "Arquivo redundante removido corretamente: $file"
    fi
done

print_status "Verificando estrutura de benchmarks..."

# Benchmarks (essenciais para v1.0.0)
check_directory "benchmarks" "Diretório de benchmarks" 5 || ((ERRORS++))
check_directory "benchmarks/reports" "Relatórios de benchmark" 5 || ((ERRORS++))
check_file "benchmarks/run_benchmark.sh" "Script de execução de benchmarks" || ((ERRORS++))
check_file "benchmarks/README.md" "Documentação dos benchmarks" || ((WARNINGS++))

print_status "Verificando estrutura de documentação técnica..."

# Documentação técnica organizada
check_directory "docs" "Diretório de documentação técnica" 3 || ((WARNINGS++))
check_directory "docs/performance" "Documentação de performance" 1 || ((WARNINGS++))
check_directory "docs/implementation" "Documentação de implementação" 1 || ((WARNINGS++))
check_directory "docs/releases" "Notas de release" 1 || ((WARNINGS++))

print_status "Verificando exemplos práticos..."

# Exemplos (importantes para v1.0.0)
check_directory "examples" "Diretório de exemplos" 3 || ((ERRORS++))
check_file "examples/example_v1.0.0_showcase.php" "Showcase v1.0.0" || ((WARNINGS++))
check_file "examples/example_complete_optimizations.php" "Exemplo de otimizações" || ((WARNINGS++))

print_status "Verificando scripts de suporte..."

# Scripts
check_directory "scripts" "Diretório de scripts" 5 || ((WARNINGS++))
check_file "scripts/cleanup_docs.sh" "Script de limpeza de documentação" || ((WARNINGS++))
check_file "scripts/publish_v1.0.0.sh" "Script de publicação v1.0.0" || ((WARNINGS++))

echo ""
print_status "Verificando conteúdo e qualidade da documentação..."

# Verificar se FRAMEWORK_OVERVIEW tem conteúdo específico da v1.0.0
if [ -f "FRAMEWORK_OVERVIEW_v1.0.0.md" ]; then
    if grep -q "52M ops/sec" "FRAMEWORK_OVERVIEW_v1.0.0.md" && \
       grep -q "ML-Powered Cache" "FRAMEWORK_OVERVIEW_v1.0.0.md" && \
       grep -q "Zero-Copy Operations" "FRAMEWORK_OVERVIEW_v1.0.0.md"; then
        print_success "FRAMEWORK_OVERVIEW_v1.0.0.md contém métricas de performance esperadas"
    else
        print_warning "FRAMEWORK_OVERVIEW_v1.0.0.md pode estar incompleto (faltam métricas)"
        ((WARNINGS++))
    fi
fi

# Verificar se README principal referencia a nova estrutura
if [ -f "README.md" ]; then
    if grep -q "FRAMEWORK_OVERVIEW_v1.0.0.md" "README.md"; then
        print_success "README principal referencia corretamente a documentação v1.0.0"
    else
        print_warning "README principal não referencia FRAMEWORK_OVERVIEW_v1.0.0.md"
        ((WARNINGS++))
    fi
fi

# Verificar se há backup da limpeza
if [ -d "backup_docs_"* ]; then
    BACKUP_DIR=$(ls -d backup_docs_* 2>/dev/null | head -1)
    print_success "Backup da limpeza encontrado: $BACKUP_DIR"
else
    print_warning "Backup da limpeza de documentação não encontrado"
    ((WARNINGS++))
fi

# Verificar versão no Application.php
if [ -f "src/Core/Application.php" ]; then
    if grep -q "VERSION = '2.0.1'" "src/Core/Application.php"; then
        print_success "Versão 2.0.1 confirmada no código fonte"
    else
        print_error "Versão no código fonte não está em 2.0.1"
        ((ERRORS++))
    fi
fi

echo ""
print_status "Verificando estrutura de diretórios consolidada..."

# Verifica se as pastas essenciais existem
REQUIRED_DIRS=(
    "docs"
    "docs/performance"
    "docs/implementation"
    "docs/releases"
    "benchmarks"
    "benchmarks/reports"
    "examples"
    "scripts"
    "src"
)

for dir in "${REQUIRED_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        print_success "Diretório essencial $dir existe"
    else
        print_error "Diretório essencial $dir não encontrado"
        ((ERRORS++))
    fi
done

echo ""
print_status "Validando qualidade da documentação consolidada..."

# Verificar se a documentação principal tem tamanho adequado
if [ -f "FRAMEWORK_OVERVIEW_v1.0.0.md" ]; then
    OVERVIEW_SIZE=$(wc -c < "FRAMEWORK_OVERVIEW_v1.0.0.md")
    if [ "$OVERVIEW_SIZE" -gt 10000 ]; then
        print_success "FRAMEWORK_OVERVIEW_v1.0.0.md tem tamanho adequado ($OVERVIEW_SIZE bytes)"
    else
        print_warning "FRAMEWORK_OVERVIEW_v1.0.0.md pode estar incompleto ($OVERVIEW_SIZE bytes)"
        ((WARNINGS++))
    fi
fi

# Verificar se há relatórios de benchmark recentes
if [ -d "benchmarks/reports" ]; then
    RECENT_REPORTS=$(find benchmarks/reports -name "*.md" -newer "FRAMEWORK_OVERVIEW_v1.0.0.md" 2>/dev/null | wc -l)
    if [ "$RECENT_REPORTS" -gt 0 ]; then
        print_success "Há $RECENT_REPORTS relatórios de benchmark mais recentes que a documentação"
    else
        print_warning "Relatórios de benchmark podem estar desatualizados"
        ((WARNINGS++))
    fi
fi

echo ""
echo "=========================================="
echo "🏁 RELATÓRIO FINAL DE VALIDAÇÃO v1.0.0"
echo "=========================================="

# Resumo da validação
print_status "Resumo da estrutura consolidada:"
echo "  📋 Documentação Principal: FRAMEWORK_OVERVIEW_v1.0.0.md"
echo "  📖 Navegação: DOCUMENTATION_GUIDE.md"
echo "  📊 Benchmarks: benchmarks/reports/"
echo "  💡 Exemplos: examples/"
echo "  🔧 Técnica: docs/"

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    print_success "Documentação consolidada v1.0.0 perfeita! ✨"
    echo ""
    echo "🎯 Estrutura validada:"
    echo "  ✅ Arquivos redundantes removidos"
    echo "  ✅ Documentação consolidada criada"
    echo "  ✅ Navegação simplificada"
    echo "  ✅ Performance data preservada"
    echo "  ✅ Versão 2.0.1 consistente"
    echo ""
    echo "📖 Próximos passos:"
    echo "  1. Revisar FRAMEWORK_OVERVIEW_v1.0.0.md"
    echo "  2. Testar navegação com DOCUMENTATION_GUIDE.md"
    echo "  3. Executar benchmarks para validar dados"
    echo "  4. Fazer commit das mudanças"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    print_warning "Estrutura consolidada boa com $WARNINGS avisos"
    echo ""
    echo "⚠️  Avisos encontrados:"
    echo "  • Alguns arquivos opcionais podem estar ausentes"
    echo "  • Documentação pode precisar de pequenos ajustes"
    echo "  • Considera revisar os avisos acima"
    echo ""
    echo "✅ Estrutura essencial está correta e funcional"
    exit 0
else
    print_error "Problemas encontrados na estrutura consolidada!"
    echo ""
    echo "❌ Erros: $ERRORS | ⚠️ Avisos: $WARNINGS"
    echo ""
    echo "🔧 Ações necessárias:"
    echo "  • Corrija os erros antes de continuar"
    echo "  • Verifique se a consolidação foi executada corretamente"
    echo "  • Execute: ./scripts/cleanup_docs.sh se necessário"
    echo "  • Verifique se FRAMEWORK_OVERVIEW_v1.0.0.md foi criado"
    echo "  • Confirme que a versão 2.0.1 está no código"
    exit 1
fi
