#!/bin/bash

# HelixPHP v1.0.0 - Validador de Benchmarks
# Verifica se os benchmarks estão atualizados e funcionando

echo "🏃‍♂️ Validando benchmarks do HelixPHP v1.0.0..."

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

# Contadores
ERRORS=0
WARNINGS=0

print_status "Verificando estrutura de benchmarks..."

# Diretórios obrigatórios
if [ -d "benchmarks" ]; then
    print_success "Diretório benchmarks/ existe"
else
    print_error "Diretório benchmarks/ não encontrado"
    ((ERRORS++))
fi

if [ -d "benchmarks/reports" ]; then
    print_success "Diretório benchmarks/reports/ existe"
else
    print_error "Diretório benchmarks/reports/ não encontrado"
    ((ERRORS++))
fi

print_status "Verificando scripts de benchmark..."

# Scripts essenciais
BENCHMARK_SCRIPTS=(
    "benchmarks/run_benchmark.sh"
    "benchmarks/ExpressPhpBenchmark.php"
    "benchmarks/ComprehensivePerformanceAnalysis.php"
    "benchmarks/EnhancedAdvancedOptimizationsBenchmark.php"
    "benchmarks/generate_comprehensive_report.php"
)

for script in "${BENCHMARK_SCRIPTS[@]}"; do
    if [ -f "$script" ]; then
        if [ -x "$script" ]; then
            print_success "Script $script existe e é executável"
        else
            print_warning "Script $script existe mas não é executável"
            ((WARNINGS++))
        fi
    else
        print_error "Script $script não encontrado"
        ((ERRORS++))
    fi
done

print_status "Verificando documentação de benchmarks..."

# Documentação
if [ -f "docs/performance/benchmarks/README.md" ]; then
    size=$(wc -c < "docs/performance/benchmarks/README.md")
    if [ "$size" -gt 5000 ]; then
        print_success "docs/performance/benchmarks/README.md existe e tem conteúdo adequado ($size bytes)"

        # Verificar se contém dados v1.0.0
        if grep -q "02/07/2025" "docs/performance/benchmarks/README.md" && \
           grep -q "2.69M" "docs/performance/benchmarks/README.md" && \
           grep -q "PHP 8.4.8" "docs/performance/benchmarks/README.md"; then
            print_success "Documentação contém dados atualizados v1.0.0"
        else
            print_warning "Documentação pode não estar atualizada para v1.0.0"
            ((WARNINGS++))
        fi
    else
        print_warning "docs/performance/benchmarks/README.md tem pouco conteúdo ($size bytes)"
        ((WARNINGS++))
    fi
else
    print_error "docs/performance/benchmarks/README.md não encontrado"
    ((ERRORS++))
fi

print_status "Verificando relatórios de benchmark recentes..."

# Verificar se existem relatórios recentes
RECENT_REPORTS=$(find benchmarks/reports -name "*.json" -mtime -1 2>/dev/null | wc -l)
if [ "$RECENT_REPORTS" -gt 0 ]; then
    print_success "$RECENT_REPORTS relatório(s) de benchmark encontrado(s) nas últimas 24h"
else
    print_warning "Nenhum relatório de benchmark recente encontrado"
    ((WARNINGS++))
fi

# Verificar relatórios v1.0.0 específicos
V212_REPORTS=$(find benchmarks/reports -name "*2025-07-02*" 2>/dev/null | wc -l)
if [ "$V212_REPORTS" -gt 0 ]; then
    print_success "$V212_REPORTS relatório(s) v1.0.0 encontrado(s)"
else
    print_warning "Nenhum relatório específico v1.0.0 encontrado"
    ((WARNINGS++))
fi

print_status "Testando execução rápida de benchmark..."

# Testar se o benchmark roda
if [ -x "benchmarks/run_benchmark.sh" ]; then
    echo "Executando teste rápido de benchmark..."
    if timeout 30s ./benchmarks/run_benchmark.sh -q > /dev/null 2>&1; then
        print_success "Benchmark rápido executado com sucesso"
    else
        print_warning "Benchmark rápido falhou ou demorou muito (timeout 30s)"
        ((WARNINGS++))
    fi
else
    print_error "Script de benchmark não é executável"
    ((ERRORS++))
fi

print_status "Verificando configuração de performance..."

# Verificar PHP
PHP_VERSION=$(php -v | head -n1 | grep -o 'PHP [0-9]\+\.[0-9]\+\.[0-9]\+' || echo "PHP não encontrado")
if [[ "$PHP_VERSION" == *"8.4"* ]]; then
    print_success "PHP 8.4.x detectado: $PHP_VERSION"
else
    print_warning "PHP 8.4.x recomendado para v1.0.0, detectado: $PHP_VERSION"
    ((WARNINGS++))
fi

# Verificar OPcache
OPCACHE_STATUS=$(php -m | grep -i opcache || echo "")
if [ -n "$OPCACHE_STATUS" ]; then
    print_success "OPcache está disponível"
else
    print_warning "OPcache não detectado (recomendado para performance)"
    ((WARNINGS++))
fi

# Verificar Composer
if [ -f "vendor/autoload.php" ]; then
    print_success "Autoload do Composer disponível"
else
    print_error "vendor/autoload.php não encontrado - execute 'composer install'"
    ((ERRORS++))
fi

echo ""
print_status "Relatório final de validação de benchmarks:"

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    print_success "✅ Todos os benchmarks estão configurados corretamente!"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    print_warning "⚠️ Benchmarks OK, mas $WARNINGS aviso(s) encontrado(s)"
    exit 0
else
    print_error "❌ $ERRORS erro(s) e $WARNINGS aviso(s) encontrado(s)"
    echo ""
    echo "Para corrigir os problemas:"
    echo "1. Execute 'composer install' se necessário"
    echo "2. Verifique se os scripts têm permissão de execução: chmod +x benchmarks/*.sh"
    echo "3. Execute um benchmark teste: ./benchmarks/run_benchmark.sh -q"
    echo "4. Atualize a documentação se necessário"
    exit 1
fi
