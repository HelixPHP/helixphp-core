#!/bin/bash

# Express PHP v2.1.2 - Validador de Documentação Atualizada
# Verifica se a documentação está organizada corretamente com nova estrutura v2.1.2

echo "📚 Validando estrutura de documentação do Express PHP v2.1.2..."

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
}

# Contadores
ERRORS=0
WARNINGS=0

print_status "Verificando documentação principal..."

# Documentação principal (estrutura atual v2.1.2)
check_file "README.md" "README principal" || ((ERRORS++))
check_file "CHANGELOG.md" "Changelog" || ((WARNINGS++))
check_file "CONTRIBUTING.md" "Guia de contribuição" || ((WARNINGS++))

print_status "Verificando estrutura de releases..."

# Nova estrutura de releases
check_directory "docs/releases" "Diretório de releases" 3 || ((ERRORS++))
check_file "docs/releases/README.md" "Índice de releases" 500 || ((ERRORS++))
check_file "docs/releases/FRAMEWORK_OVERVIEW_v2.1.2.md" "Overview v2.1.2 (ATUAL)" 5000 || ((ERRORS++))
check_file "docs/releases/FRAMEWORK_OVERVIEW_v2.1.1.md" "Overview v2.1.1" 3000 || ((WARNINGS++))
check_file "docs/releases/FRAMEWORK_OVERVIEW_v2.0.1.md" "Overview v2.0.1" 3000 || ((WARNINGS++))

print_status "Verificando se arquivos foram movidos corretamente da raiz..."

# Arquivos que DEVEM ter sido movidos para docs/releases/
MOVED_FILES=(
    "FRAMEWORK_OVERVIEW_v2.0.1.md"
    "FRAMEWORK_OVERVIEW_v2.1.1.md"
    "FRAMEWORK_OVERVIEW_v2.1.2.md"
)

for file in "${MOVED_FILES[@]}"; do
    if [ -f "$file" ]; then
        print_warning "Arquivo deveria ter sido movido para docs/releases/: $file"
        ((WARNINGS++))
    else
        print_success "Arquivo movido corretamente da raiz: $file"
    fi
done

print_status "Verificando se arquivos redundantes foram removidos..."

# Arquivos que DEVEM ter sido removidos (redundantes)
REDUNDANT_FILES=(
    "README_v2.0.1.md"
    "PERFORMANCE_REPORT_FINAL.md"
    "TECHNICAL_OPTIMIZATION_SUMMARY.md"
    "CONSOLIDATION_SUMMARY_v2.0.1.md"
    "ADVANCED_OPTIMIZATIONS_REPORT.md"
    "OPTIMIZATION_FINAL_REPORT.md"
    "DOCUMENTATION_GUIDE.md"
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

# Benchmarks (essenciais para v2.0.1)
check_directory "benchmarks" "Diretório de benchmarks" 5 || ((ERRORS++))
check_directory "benchmarks/reports" "Relatórios de benchmark" 5 || ((ERRORS++))
check_file "benchmarks/run_benchmark.sh" "Script de execução de benchmarks" || ((ERRORS++))
check_file "benchmarks/README.md" "Documentação dos benchmarks" || ((WARNINGS++))

print_status "Verificando estrutura de documentação técnica atualizada..."

# Documentação técnica organizada (estrutura v2.1.2)
check_directory "docs" "Diretório de documentação técnica" 5 || ((WARNINGS++))
check_file "docs/index.md" "Índice principal da documentação" 2000 || ((ERRORS++))

# Implementações
check_directory "docs/implementions" "Documentação de implementação" 3 || ((WARNINGS++))
check_file "docs/implementions/usage_basic.md" "Guia básico de uso" || ((WARNINGS++))

# Documentação técnica
check_directory "docs/techinical" "Documentação técnica" 5 || ((WARNINGS++))
check_file "docs/techinical/application.md" "Documentação da Application" || ((WARNINGS++))
check_file "docs/techinical/http/request.md" "Documentação de Request" || ((WARNINGS++))
check_file "docs/techinical/http/response.md" "Documentação de Response" || ((WARNINGS++))
check_file "docs/techinical/routing/router.md" "Documentação do Router" || ((WARNINGS++))

# Middleware
check_directory "docs/techinical/middleware" "Documentação de middleware" 3 || ((WARNINGS++))
check_file "docs/techinical/middleware/README.md" "Índice de middlewares" || ((WARNINGS++))

# Autenticação
check_directory "docs/techinical/authentication" "Documentação de autenticação" 2 || ((WARNINGS++))
check_file "docs/techinical/authentication/usage_native.md" "Autenticação nativa" || ((WARNINGS++))

# Performance
check_directory "docs/performance" "Documentação de performance" 2 || ((WARNINGS++))
check_file "docs/performance/PerformanceMonitor.md" "Monitor de performance" || ((WARNINGS++))
check_file "docs/performance/benchmarks/README.md" "Documentação de benchmarks" 2000 || ((ERRORS++))

# Testes
check_directory "docs/testing" "Documentação de testes" 3 || ((WARNINGS++))
check_file "docs/testing/api_testing.md" "Testes de API" || ((WARNINGS++))

# Contribuição
check_directory "docs/contributing" "Documentação de contribuição" 1 || ((WARNINGS++))
check_file "docs/contributing/README.md" "Guia de contribuição" || ((WARNINGS++))

# Releases (já verificado acima)
check_directory "docs/releases" "Documentação de releases" 4 || ((WARNINGS++))

print_status "Verificando exemplos atualizados..."

# Exemplos (importantes para v2.1.2)
check_directory "examples" "Diretório de exemplos" 5 || ((ERRORS++))
check_file "examples/example_basic.php" "Exemplo básico" || ((WARNINGS++))
check_file "examples/example_middleware.php" "Exemplo com middleware" || ((WARNINGS++))
check_file "examples/example_auth.php" "Exemplo de autenticação" || ((WARNINGS++))
check_file "examples/example_complete_optimizations.php" "Exemplo de otimizações" || ((WARNINGS++))
check_file "examples/example_high_performance.php" "Exemplo de alta performance" || ((WARNINGS++))

print_status "Verificando scripts de suporte..."

# Scripts
check_directory "scripts" "Diretório de scripts" 8 || ((WARNINGS++))
check_file "scripts/cleanup_docs.sh" "Script de limpeza de documentação" || ((WARNINGS++))
check_file "scripts/validate-docs.sh" "Script de validação de docs" || ((WARNINGS++))
check_file "scripts/validate_project.php" "Script de validação do projeto" || ((WARNINGS++))

echo ""
print_status "Verificando conteúdo e qualidade da documentação..."

# Verificar se FRAMEWORK_OVERVIEW v2.1.2 tem conteúdo específico
if [ -f "docs/releases/FRAMEWORK_OVERVIEW_v2.1.2.md" ]; then
    if grep -q "2.69M ops/sec" "docs/releases/FRAMEWORK_OVERVIEW_v2.1.2.md" && \
       grep -q "PHP 8.4.8" "docs/releases/FRAMEWORK_OVERVIEW_v2.1.2.md" && \
       grep -q "JIT" "docs/releases/FRAMEWORK_OVERVIEW_v2.1.2.md"; then
        print_success "FRAMEWORK_OVERVIEW_v2.1.2.md contém métricas de performance esperadas"
    else
        print_warning "FRAMEWORK_OVERVIEW_v2.1.2.md pode estar incompleto (faltam métricas v2.1.2)"
        ((WARNINGS++))
    fi
else
    print_error "FRAMEWORK_OVERVIEW_v2.1.2.md não encontrado em docs/releases/"
    ((ERRORS++))
fi

# Verificar se docs/index.md referencia as releases
if [ -f "docs/index.md" ]; then
    if grep -q "releases" "docs/index.md" && \
       grep -q "v2.1.2" "docs/index.md"; then
        print_success "docs/index.md referencia corretamente as releases"
    else
        print_warning "docs/index.md pode não estar referenciando as releases corretamente"
        ((WARNINGS++))
    fi
else
    print_error "docs/index.md não encontrado"
    ((ERRORS++))
fi

# Verificar se benchmarks foram atualizados
if [ -f "docs/performance/benchmarks/README.md" ]; then
    if grep -q "02/07/2025" "docs/performance/benchmarks/README.md" && \
       grep -q "2.69M" "docs/performance/benchmarks/README.md" && \
       grep -q "PHP 8.4.8" "docs/performance/benchmarks/README.md"; then
        print_success "Benchmarks atualizados com dados v2.1.2"
    else
        print_warning "Benchmarks podem não estar atualizados para v2.1.2"
        ((WARNINGS++))
    fi
else
    print_error "docs/performance/benchmarks/README.md não encontrado"
    ((ERRORS++))
fi

# Verificar se README principal referencia a nova estrutura
if [ -f "README.md" ]; then
    if grep -q "docs/releases" "README.md" || grep -q "v2.1.2" "README.md"; then
        print_success "README principal referencia corretamente a nova estrutura v2.1.2"
    else
        print_warning "README principal pode não estar referenciando a nova estrutura v2.1.2"
        ((WARNINGS++))
    fi
fi

# Verificar se há backup da limpeza (opcional - pode estar em outra branch)
BACKUP_DIRS=(backup_docs_*)
if [ -d "${BACKUP_DIRS[0]}" ]; then
    print_success "Backup da limpeza encontrado: ${BACKUP_DIRS[0]}"
else
    print_status "Backup gerenciado em branch separada (não é erro)"
fi

# Verificar versão no Application.php
if [ -f "src/Core/Application.php" ]; then
    if grep -q "VERSION = '2.1.2'" "src/Core/Application.php"; then
        print_success "Versão 2.1.2 confirmada no código fonte"
    else
        print_warning "Versão no código fonte não está em 2.1.2 - verifique se é intencional"
        ((WARNINGS++))
    fi
fi

echo ""
print_status "Verificando estrutura de diretórios consolidada..."

# Verifica se as pastas essenciais existem
REQUIRED_DIRS=(
    "docs"
    "docs/releases"
    "docs/techinical"
    "docs/implementions"
    "docs/performance"
    "docs/testing"
    "docs/contributing"
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
echo "=========================================="
echo "🏁 RELATÓRIO FINAL DE VALIDAÇÃO v2.1.2"
echo "=========================================="

# Resumo da validação
print_status "Resumo da estrutura v2.1.2:"
echo "  📋 Releases: docs/releases/FRAMEWORK_OVERVIEW_v2.1.2.md"
echo "  📖 Navegação: docs/index.md"
echo "  📊 Benchmarks: docs/performance/benchmarks/README.md"
echo "  💡 Exemplos: examples/"
echo "  🔧 Técnica: docs/techinical/"
echo "  🏗️ Implementação: docs/implementions/"
echo "  🧪 Testes: docs/testing/"
echo "  🤝 Contribuição: docs/contributing/"

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    print_success "Documentação v2.1.2 perfeita! ✨"
    echo ""
    echo "🎯 Estrutura validada:"
    echo "  ✅ Nova estrutura de releases implementada"
    echo "  ✅ Documentação técnica organizada"
    echo "  ✅ Navegação por categorias criada"
    echo "  ✅ Performance data v2.1.2 preservada"
    echo "  ✅ Benchmarks atualizados"
    echo ""
    echo "📖 Próximos passos:"
    echo "  1. Revisar docs/releases/FRAMEWORK_OVERVIEW_v2.1.2.md"
    echo "  2. Testar navegação com docs/index.md"
    echo "  3. Executar benchmarks para validar dados"
    echo "  4. Fazer commit das mudanças"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    print_warning "Estrutura v2.1.2 boa com $WARNINGS avisos"
    echo ""
    echo "⚠️  Avisos encontrados:"
    echo "  • Alguns arquivos opcionais podem estar ausentes"
    echo "  • Documentação pode precisar de pequenos ajustes"
    echo "  • Considera revisar os avisos acima"
    echo ""
    echo "✅ Estrutura essencial está correta e funcional"
    exit 0
else
    print_error "Problemas encontrados na estrutura v2.1.2!"
    echo ""
    echo "❌ Erros: $ERRORS | ⚠️ Avisos: $WARNINGS"
    echo ""
    echo "🔧 Ações necessárias:"
    echo "  • Corrija os erros antes de continuar"
    echo "  • Verifique se a reestruturação foi executada corretamente"
    echo "  • Execute scripts de validação específicos se necessário"
    echo "  • Verifique se FRAMEWORK_OVERVIEW_v2.1.2.md foi criado"
    echo "  • Confirme que a documentação está na nova estrutura"
    exit 1
fi
