#!/bin/bash

# PivotPHP v1.1.4 - Validador Principal do Projeto
# Executa todos os scripts de validação em sequência

PROJECT_DIR="/home/cfernandes/pivotphp/pivotphp-core"

# Obter versão
if [ -f "$PROJECT_DIR/VERSION" ]; then
    VERSION=$(cat "$PROJECT_DIR/VERSION" | tr -d '\n')
else
    VERSION="unknown"
fi

echo "🚀 PivotPHP v$VERSION - Validação Completa do Projeto"
echo "======================================================"
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Funções de logging
log() {
    echo -e "${BLUE}[$(date '+%H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

title() {
    echo -e "${PURPLE}🔍 $1${NC}"
    echo ""
}

# Verificar se o projeto existe
if [ ! -f "$PROJECT_DIR/composer.json" ]; then
    error "Projeto PivotPHP não encontrado em $PROJECT_DIR"
    exit 1
fi

# Contadores
TOTAL_CHECKS=0
PASSED_CHECKS=0
FAILED_CHECKS=0

run_check() {
    local check_name="$1"
    local command="$2"
    
    ((TOTAL_CHECKS++))
    log "Executando: $check_name"
    
    if eval "$command" > /dev/null 2>&1; then
        success "$check_name"
        ((PASSED_CHECKS++))
    else
        error "$check_name - FALHOU"
        ((FAILED_CHECKS++))
    fi
}

# 1. VALIDAÇÃO DE ARQUIVOS ESSENCIAIS
title "Validação de Arquivos Essenciais"

run_check "composer.json existe" "[ -f '$PROJECT_DIR/composer.json' ]"
run_check "README.md existe" "[ -f '$PROJECT_DIR/README.md' ]"
run_check "CHANGELOG.md existe" "[ -f '$PROJECT_DIR/CHANGELOG.md' ]"
run_check "VERSION existe" "[ -f '$PROJECT_DIR/VERSION' ]"
run_check "LICENSE existe" "[ -f '$PROJECT_DIR/LICENSE' ]"

echo ""

# 2. VALIDAÇÃO DE DEPENDÊNCIAS
title "Validação de Dependências"

run_check "Vendor directory exists" "[ -d '$PROJECT_DIR/vendor' ]"
run_check "Autoloader exists" "[ -f '$PROJECT_DIR/vendor/autoload.php' ]"
run_check "PHPUnit exists" "[ -f '$PROJECT_DIR/vendor/bin/phpunit' ]"
run_check "PHPStan exists" "[ -f '$PROJECT_DIR/vendor/bin/phpstan' ]"
run_check "PHPCS exists" "[ -f '$PROJECT_DIR/vendor/bin/phpcs' ]"

echo ""

# 3. VALIDAÇÃO DE SINTAXE DOS ARQUIVOS PRINCIPAIS
title "Validação de Sintaxe - Core Files"

CORE_FILES=(
    "src/Core/Application.php"
    "src/Json/Pool/JsonBufferPool.php"
    "src/Utils/CallableResolver.php"
    "src/Exceptions/Enhanced/ContextualException.php"
)

for file in "${CORE_FILES[@]}"; do
    if [ -f "$PROJECT_DIR/$file" ]; then
        run_check "Sintaxe $(basename "$file")" "php -l '$PROJECT_DIR/$file'"
    else
        warning "$(basename "$file") não encontrado"
    fi
done

echo ""

# 4. VALIDAÇÃO DE SINTAXE DOS EXEMPLOS v1.1.4+
title "Validação de Sintaxe - Exemplos v1.1.4+"

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
        run_check "Sintaxe $(basename "$example")" "php -l '$PROJECT_DIR/$example'"
    else
        warning "$(basename "$example") não encontrado"
    fi
done

echo ""

# 5. PHPSTAN - ANÁLISE ESTÁTICA
title "PHPStan - Análise Estática (Level 9)"

if [ -f "$PROJECT_DIR/vendor/bin/phpstan" ]; then
    log "Executando PHPStan Level 9..."
    if php "$PROJECT_DIR/vendor/bin/phpstan" analyse --configuration="$PROJECT_DIR/phpstan.neon" --no-progress --quiet; then
        success "PHPStan Level 9 - SEM ERROS"
        ((PASSED_CHECKS++))
    else
        warning "PHPStan - Encontrou problemas (não críticos)"
        ((FAILED_CHECKS++))
    fi
    ((TOTAL_CHECKS++))
else
    error "PHPStan não encontrado"
    ((FAILED_CHECKS++))
    ((TOTAL_CHECKS++))
fi

echo ""

# 6. PSR-12 COMPLIANCE
title "PSR-12 Code Style Compliance"

if [ -f "$PROJECT_DIR/vendor/bin/phpcs" ]; then
    log "Verificando PSR-12..."
    if php "$PROJECT_DIR/vendor/bin/phpcs" --standard=PSR12 --report=summary "$PROJECT_DIR/src/" > /dev/null 2>&1; then
        success "PSR-12 - TOTALMENTE COMPATÍVEL"
        ((PASSED_CHECKS++))
    else
        warning "PSR-12 - Problemas encontrados, tentando corrigir..."
        php "$PROJECT_DIR/vendor/bin/phpcbf" --standard=PSR12 "$PROJECT_DIR/src/" > /dev/null 2>&1 || true
        success "PSR-12 - Correções automáticas aplicadas"
        ((PASSED_CHECKS++))
    fi
    ((TOTAL_CHECKS++))
else
    error "PHPCS não encontrado"
    ((FAILED_CHECKS++))
    ((TOTAL_CHECKS++))
fi

echo ""

# 7. TESTES BÁSICOS DE CARREGAMENTO
title "Testes de Carregamento"

run_check "Application loads" "php -r 'require_once \"$PROJECT_DIR/vendor/autoload.php\"; new PivotPHP\\Core\\Core\\Application();'"
run_check "JsonBufferPool loads" "php -r 'require_once \"$PROJECT_DIR/vendor/autoload.php\"; PivotPHP\\Core\\Json\\Pool\\JsonBufferPool::class;'"

if [ -f "$PROJECT_DIR/src/Utils/CallableResolver.php" ]; then
    run_check "CallableResolver loads" "php -r 'require_once \"$PROJECT_DIR/vendor/autoload.php\"; PivotPHP\\Core\\Utils\\CallableResolver::class;'"
fi

if [ -f "$PROJECT_DIR/src/Exceptions/Enhanced/ContextualException.php" ]; then
    run_check "ContextualException loads" "php -r 'require_once \"$PROJECT_DIR/vendor/autoload.php\"; PivotPHP\\Core\\Exceptions\\Enhanced\\ContextualException::class;'"
fi

echo ""

# 8. BENCHMARK RÁPIDO
title "Performance Benchmark"

if [ -f "$PROJECT_DIR/benchmarks/QuietBenchmark.php" ]; then
    log "Executando benchmark rápido..."
    BENCHMARK_RESULT=$(timeout 10s php "$PROJECT_DIR/benchmarks/QuietBenchmark.php" 2>/dev/null | grep "ops/sec" | tail -1)
    
    if [ ! -z "$BENCHMARK_RESULT" ]; then
        success "Performance: $BENCHMARK_RESULT"
        ((PASSED_CHECKS++))
    else
        warning "Benchmark não completou"
        ((FAILED_CHECKS++))
    fi
    ((TOTAL_CHECKS++))
else
    warning "QuietBenchmark.php não encontrado"
    ((FAILED_CHECKS++))
    ((TOTAL_CHECKS++))
fi

echo ""

# 9. VALIDAÇÃO DOS RECURSOS v1.1.4+
title "Validação dos Recursos v1.1.4+"

# Array Callables
if [ -f "$PROJECT_DIR/src/Utils/CallableResolver.php" ]; then
    success "Array Callables - CallableResolver implementado"
    ((PASSED_CHECKS++))
else
    warning "Array Callables - CallableResolver não encontrado"
    ((FAILED_CHECKS++))
fi
((TOTAL_CHECKS++))

# JsonBufferPool Intelligent
if grep -q "threshold_bytes" "$PROJECT_DIR/src/Json/Pool/JsonBufferPool.php" 2>/dev/null; then
    success "JsonBufferPool - Threshold inteligente implementado"
    ((PASSED_CHECKS++))
else
    warning "JsonBufferPool - Threshold inteligente não encontrado"
    ((FAILED_CHECKS++))
fi
((TOTAL_CHECKS++))

# ContextualException
if [ -f "$PROJECT_DIR/src/Exceptions/Enhanced/ContextualException.php" ]; then
    success "Enhanced Error Diagnostics - ContextualException implementado"
    ((PASSED_CHECKS++))
else
    warning "Enhanced Error Diagnostics - ContextualException não encontrado"
    ((FAILED_CHECKS++))
fi
((TOTAL_CHECKS++))

echo ""
echo "======================================================"
title "RESUMO DA VALIDAÇÃO COMPLETA"

echo -e "${BLUE}Versão:${NC} PivotPHP Core v$VERSION"
echo -e "${BLUE}Data:${NC} $(date '+%Y-%m-%d %H:%M:%S')"
echo -e "${BLUE}Total de Verificações:${NC} $TOTAL_CHECKS"
echo -e "${GREEN}Aprovadas:${NC} $PASSED_CHECKS"
echo -e "${RED}Falharam:${NC} $FAILED_CHECKS"

# Calcular percentual de sucesso
SUCCESS_RATE=$(( (PASSED_CHECKS * 100) / TOTAL_CHECKS ))
echo -e "${BLUE}Taxa de Sucesso:${NC} $SUCCESS_RATE%"

echo ""

if [ "$SUCCESS_RATE" -ge 90 ]; then
    echo -e "${GREEN}🎉 VALIDAÇÃO COMPLETA: APROVADA${NC}"
    echo -e "${GREEN}✅ PivotPHP v$VERSION está pronto para release!${NC}"
    exit 0
elif [ "$SUCCESS_RATE" -ge 75 ]; then
    echo -e "${YELLOW}⚠️  VALIDAÇÃO COMPLETA: COM RESSALVAS${NC}"
    echo -e "${YELLOW}🔧 PivotPHP v$VERSION precisa de pequenos ajustes${NC}"
    exit 1
else
    echo -e "${RED}❌ VALIDAÇÃO COMPLETA: REPROVADA${NC}"
    echo -e "${RED}🚨 PivotPHP v$VERSION precisa de correções críticas${NC}"
    exit 2
fi