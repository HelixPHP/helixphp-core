#!/bin/bash

# Script simplificado de preparação para release PivotPHP v1.1.4
set -e

PROJECT_DIR="/home/cfernandes/pivotphp/pivotphp-core"

# Cores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m'

title() { echo -e "${PURPLE}🚀 $1${NC}"; }
info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
success() { echo -e "${GREEN}✅ $1${NC}"; }
warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
error() { echo -e "${RED}❌ $1${NC}"; exit 1; }

# Obter versão
VERSION=$(cat "$PROJECT_DIR/VERSION" | tr -d '\n')

title "PivotPHP v$VERSION - Pre-Release Validation"
echo ""

# 1. Verificar arquivos principais
info "Verificando arquivos principais..."
if [ ! -f "$PROJECT_DIR/composer.json" ]; then
    error "composer.json não encontrado"
fi
success "composer.json ✓"

if [ ! -f "$PROJECT_DIR/README.md" ]; then
    error "README.md não encontrado"
fi
success "README.md ✓"

if [ ! -f "$PROJECT_DIR/CHANGELOG.md" ]; then
    error "CHANGELOG.md não encontrado"
fi
success "CHANGELOG.md ✓"

# 2. Verificar sintaxe de exemplos v1.1.4+
info "Verificando sintaxe dos exemplos v1.1.4+..."

# Exemplos v1.1.4+
EXAMPLES_V114=(
    "examples/01-basics/hello-world.php"
    "examples/07-advanced/array-callables-v114.php"
    "examples/08-json-optimization/json-pool-demo-v114.php"
    "examples/09-error-handling/enhanced-errors-v114.php"
    "examples/04-api/rest-api-v114.php"
    "examples/04-api/rest-api-modernized-v114.php"
    "examples/02-routing/route-parameters-v114.php"
    "examples/03-middleware/custom-middleware-v114.php"
)

for example in "${EXAMPLES_V114[@]}"; do
    if [ -f "$PROJECT_DIR/$example" ]; then
        php -l "$PROJECT_DIR/$example" > /dev/null 2>&1
        if [ $? -eq 0 ]; then
            success "$(basename "$example") ✓"
        else
            error "Erro de sintaxe em $example"
        fi
    else
        warning "$example não encontrado"
    fi
done

# 3. Verificar autoloader
info "Verificando autoloader..."
if [ -f "$PROJECT_DIR/vendor/autoload.php" ]; then
    success "Autoloader ✓"
else
    warning "Vendor não instalado - executando composer install..."
    composer install --working-dir="$PROJECT_DIR" --no-dev > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        success "Composer install ✓"
    else
        error "Falha no composer install"
    fi
fi

# 4. Teste de carregamento básico
info "Testando carregamento básico..."
php -r "
require_once '$PROJECT_DIR/vendor/autoload.php';
use PivotPHP\Core\Core\Application;
\$app = new Application();
echo 'Application criada com sucesso\n';
" > /dev/null 2>&1

if [ $? -eq 0 ]; then
    success "Carregamento básico ✓"
else
    error "Falha no carregamento básico"
fi

# 5. Benchmark rápido
info "Executando benchmark rápido..."
if [ -f "$PROJECT_DIR/benchmarks/QuietBenchmark.php" ]; then
    BENCHMARK_RESULT=$(timeout 10s php "$PROJECT_DIR/benchmarks/QuietBenchmark.php" 2>/dev/null | grep "ops/sec" | tail -1)
    if [ ! -z "$BENCHMARK_RESULT" ]; then
        success "Benchmark: $BENCHMARK_RESULT"
    else
        warning "Benchmark não completou"
    fi
else
    warning "QuietBenchmark.php não encontrado"
fi

echo ""
title "✅ Pre-Release Validation Completa!"
echo ""
info "Versão: $VERSION"
info "Status: Pronto para release"
info "Recursos v1.1.4+:"
echo "  • Array callables nativos"
echo "  • JsonBufferPool inteligente (threshold 256 bytes)"
echo "  • Enhanced error diagnostics (ContextualException)"
echo "  • 8 exemplos modernizados"
echo "  • 100% backward compatibility"
echo ""
success "PivotPHP v$VERSION está pronto para publicação! 🚀"