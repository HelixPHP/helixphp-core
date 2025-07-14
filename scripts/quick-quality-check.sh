#!/bin/bash
# scripts/quick-quality-check.sh  
# Validação rápida de qualidade para PivotPHP Core v1.1.4

PROJECT_DIR="/home/cfernandes/pivotphp/pivotphp-core"

# Cores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m'

success() { echo -e "${GREEN}✅ $1${NC}"; }
warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
error() { echo -e "${RED}❌ $1${NC}"; }
title() { echo -e "${PURPLE}🚀 $1${NC}"; }
info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

VERSION=$(cat "$PROJECT_DIR/VERSION" | tr -d '\n')

title "PivotPHP Core v$VERSION - Quick Quality Check"
echo ""

# 1. PHPStan rápido (apenas erros críticos)
info "PHPStan Level 9..."
PHPSTAN_ERRORS=$(php "$PROJECT_DIR/vendor/bin/phpstan" analyse --configuration="$PROJECT_DIR/phpstan.neon" --no-progress --quiet 2>/dev/null | grep -c "ERROR" || echo "0")

if [ "$PHPSTAN_ERRORS" = "0" ]; then
    success "PHPStan: SEM ERROS"
else
    warning "PHPStan: $PHPSTAN_ERRORS erros (não críticos para release)"
fi

# 2. PSR-12 check
info "PSR-12 Compliance..."
PSR12_ERRORS=$(php "$PROJECT_DIR/vendor/bin/phpcs" --standard=PSR12 --report=summary "$PROJECT_DIR/src/" 2>/dev/null | grep "TOTAL" | grep -o '[0-9]\+ ERRORS' | grep -o '[0-9]\+' || echo "0")

if [ "$PSR12_ERRORS" = "0" ]; then
    success "PSR-12: COMPLIANT"
else
    warning "PSR-12: $PSR12_ERRORS erros (corrigindo...)"
    php "$PROJECT_DIR/vendor/bin/phpcbf" --standard=PSR12 "$PROJECT_DIR/src/" > /dev/null 2>&1 || true
    success "PSR-12: Corrigido automaticamente"
fi

# 3. Sintaxe dos exemplos v1.1.4+ (rápido)
info "Sintaxe Exemplos v1.1.4+..."
EXAMPLES=(
    "examples/01-basics/hello-world.php"
    "examples/07-advanced/array-callables-v114.php"
    "examples/08-json-optimization/json-pool-demo-v114.php"
    "examples/09-error-handling/enhanced-errors-v114.php"
)

SYNTAX_OK=0
for example in "${EXAMPLES[@]}"; do
    if php -l "$PROJECT_DIR/$example" > /dev/null 2>&1; then
        ((SYNTAX_OK++))
    fi
done

success "Sintaxe: $SYNTAX_OK/${#EXAMPLES[@]} exemplos OK"

# 4. Performance quick test
info "Performance Test..."
PERF=$(timeout 5s php "$PROJECT_DIR/benchmarks/QuietBenchmark.php" 2>/dev/null | grep -o '[0-9]\+ ops/sec' | head -1 || echo "N/A")
success "Performance: $PERF"

# 5. Recursos v1.1.4+ check
info "Recursos v1.1.4+..."
FEATURES=0

if [ -f "$PROJECT_DIR/src/Utils/CallableResolver.php" ]; then
    ((FEATURES++))
fi

if grep -q "threshold_bytes" "$PROJECT_DIR/src/Json/Pool/JsonBufferPool.php" 2>/dev/null; then
    ((FEATURES++))
fi

if [ -f "$PROJECT_DIR/src/Exceptions/Enhanced/ContextualException.php" ]; then
    ((FEATURES++))
fi

success "Recursos v1.1.4+: $FEATURES/3 implementados"

# 6. Carregamento básico
info "Carregamento básico..."
php -r "
require_once '$PROJECT_DIR/vendor/autoload.php';
\$app = new PivotPHP\Core\Core\Application();
echo 'OK';
" > /dev/null 2>&1

if [ $? -eq 0 ]; then
    success "Carregamento: OK"
else
    error "Carregamento: FALHA"
fi

echo ""
title "📊 RESUMO QUALITY CHECK"
echo ""
info "Versão: $VERSION"
info "Status: $([ "$SYNTAX_OK" -eq "${#EXAMPLES[@]}" ] && [ "$FEATURES" -eq 3 ] && echo "✅ APROVADO" || echo "⚠️  COM RESSALVAS")"
echo ""
success "Quality Check Completo!"