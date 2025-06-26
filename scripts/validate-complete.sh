#!/bin/bash

# Script de Validação Completa para Express-PHP
# Executa todas as verificações necessárias antes de release

set -e

# Cores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m'

title() { echo -e "${PURPLE}🔍 $1${NC}"; }
info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
success() { echo -e "${GREEN}✅ $1${NC}"; }
warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
error() { echo -e "${RED}❌ $1${NC}"; exit 1; }

# Contadores
CHECKS_TOTAL=0
CHECKS_PASSED=0
CHECKS_FAILED=0
CHECKS_WARNINGS=0

check_start() {
    CHECKS_TOTAL=$((CHECKS_TOTAL + 1))
    info "$1..."
}

check_pass() {
    CHECKS_PASSED=$((CHECKS_PASSED + 1))
    success "$1"
}

check_fail() {
    CHECKS_FAILED=$((CHECKS_FAILED + 1))
    error "$1"
}

check_warn() {
    CHECKS_WARNINGS=$((CHECKS_WARNINGS + 1))
    warning "$1"
}

title "Express-PHP Framework - Validação Completa"
echo ""

# Verificar se estamos na raiz do projeto
if [ ! -f "composer.json" ]; then
    error "Execute este script na raiz do projeto Express-PHP"
fi

VERSION=$(grep '"version"' composer.json | sed 's/.*"version": "\([^"]*\)".*/\1/' || echo "unknown")
info "Validando Express-PHP v$VERSION"
echo ""

# 1. Verificar estrutura de arquivos
title "1. Estrutura do Projeto"

check_start "Verificando arquivos essenciais"
required_files=(
    "composer.json"
    "README.md"
    "CHANGELOG.md"
    "src/ApiExpress.php"
    "examples/COMO_USAR.md"
)

missing_files=()
for file in "${required_files[@]}"; do
    if [ ! -f "$file" ]; then
        missing_files+=("$file")
    fi
done

if [ ${#missing_files[@]} -eq 0 ]; then
    check_pass "Todos os arquivos essenciais presentes"
else
    check_fail "Arquivos ausentes: ${missing_files[*]}"
fi

check_start "Verificando estrutura modular"
required_dirs=(
    "src/Core"
    "src/Http"
    "src/Routing"
    "src/Middleware"
    "src/Authentication"
    "src/Utils"
    "src/Validation"
    "src/Cache"
    "src/Events"
    "src/Logging"
    "src/Support"
    "src/Database"
)

missing_dirs=()
for dir in "${required_dirs[@]}"; do
    if [ ! -d "$dir" ]; then
        missing_dirs+=("$dir")
    fi
done

if [ ${#missing_dirs[@]} -eq 0 ]; then
    check_pass "Estrutura modular completa"
else
    check_fail "Diretórios ausentes: ${missing_dirs[*]}"
fi

echo ""

# 2. Verificações de código
title "2. Qualidade do Código"

check_start "Verificando sintaxe PHP"
php_files=$(find src/ examples/ tests/ -name "*.php" 2>/dev/null || true)
syntax_errors=0

for file in $php_files; do
    if ! php -l "$file" > /dev/null 2>&1; then
        syntax_errors=$((syntax_errors + 1))
        echo "    Erro de sintaxe: $file"
    fi
done

if [ $syntax_errors -eq 0 ]; then
    check_pass "Sintaxe PHP válida em todos os arquivos"
else
    check_fail "$syntax_errors arquivos com erro de sintaxe"
fi

check_start "Executando PHPStan"
if [ -f "vendor/bin/phpstan" ]; then
    if ./vendor/bin/phpstan analyse --no-progress > /dev/null 2>&1; then
        check_pass "PHPStan passou sem erros"
    else
        check_warn "PHPStan encontrou problemas (não crítico)"
    fi
else
    check_warn "PHPStan não instalado"
fi

echo ""

# 3. Testes
title "3. Execução de Testes"

check_start "Executando testes unitários"
if [ -f "vendor/bin/phpunit" ]; then
    # Capturar saída dos testes
    test_output=$(./vendor/bin/phpunit --exclude-group streaming 2>&1 || echo "FAILED")

    if echo "$test_output" | grep -q "OK" && ! echo "$test_output" | grep -q "FAILURES"; then
        tests_count=$(echo "$test_output" | grep -o "Tests: [0-9]*" | sed 's/Tests: //')
        check_pass "Todos os testes passaram ($tests_count testes)"
    else
        failures=$(echo "$test_output" | grep -o "Failures: [0-9]*" | sed 's/Failures: //' || echo "0")
        if [ "$failures" = "0" ]; then
            check_warn "Testes executados com warnings (output buffer)"
        else
            check_fail "Testes falharam ($failures failures)"
        fi
    fi
else
    check_warn "PHPUnit não instalado"
fi

echo ""

# 4. Dependências
title "4. Dependências e Composer"

check_start "Validando composer.json"
if composer validate --no-check-all --no-check-lock > /dev/null 2>&1; then
    check_pass "composer.json válido"
else
    check_fail "composer.json inválido"
fi

check_start "Verificando autoload"
if composer dump-autoload --optimize > /dev/null 2>&1; then
    check_pass "Autoload PSR-4 funcionando"
else
    check_fail "Problema no autoload"
fi

check_start "Verificando dependências"
if [ -d "vendor/" ]; then
    check_pass "Dependências instaladas"
else
    check_fail "Execute 'composer install'"
fi

echo ""

# 5. Documentação
title "5. Documentação"

check_start "Verificando completude da documentação"
doc_files=(
    "README.md"
    "CHANGELOG.md"
    "examples/COMO_USAR.md"
    "docs/README.md"
)

incomplete_docs=()
for file in "${doc_files[@]}"; do
    if [ -f "$file" ]; then
        lines=$(wc -l < "$file")
        if [ "$lines" -lt 10 ]; then
            incomplete_docs+=("$file")
        fi
    else
        incomplete_docs+=("$file (missing)")
    fi
done

if [ ${#incomplete_docs[@]} -eq 0 ]; then
    check_pass "Documentação completa"
else
    check_warn "Documentação incompleta: ${incomplete_docs[*]}"
fi

check_start "Verificando exemplos"
example_count=$(find examples/ -name "*.php" | wc -l)
if [ "$example_count" -gt 5 ]; then
    check_pass "$example_count exemplos disponíveis"
else
    check_warn "Poucos exemplos ($example_count)"
fi

echo ""

# 6. Segurança
title "6. Verificações de Segurança"

check_start "Verificando middlewares de segurança"
security_middlewares=(
    "src/Middleware/Security/CorsMiddleware.php"
    "src/Middleware/Security/AuthMiddleware.php"
    "src/Middleware/Security/SecurityMiddleware.php"
    "src/Middleware/Security/XssMiddleware.php"
    "src/Middleware/Security/CsrfMiddleware.php"
    "src/Middleware/Core/RateLimitMiddleware.php"
)

missing_security=()
for middleware in "${security_middlewares[@]}"; do
    if [ ! -f "$middleware" ]; then
        missing_security+=("$middleware")
    fi
done

if [ ${#missing_security[@]} -eq 0 ]; then
    check_pass "Todos os middlewares de segurança presentes"
else
    check_fail "Middlewares ausentes: ${missing_security[*]}"
fi

check_start "Verificando por senhas hardcoded"
if grep -r "password.*=" src/ examples/ --include="*.php" | grep -v "password_hash\|password_verify" > /dev/null 2>&1; then
    check_warn "Possíveis senhas hardcoded encontradas"
else
    check_pass "Nenhuma senha hardcoded encontrada"
fi

echo ""

# 7. Performance e Tamanho
title "7. Performance e Otimização"

check_start "Verificando tamanho dos arquivos"
large_files=$(find src/ -name "*.php" -size +100k 2>/dev/null || true)
if [ -z "$large_files" ]; then
    check_pass "Nenhum arquivo muito grande encontrado"
else
    check_warn "Arquivos grandes: $large_files"
fi

check_start "Verificando vendor/ size"
if [ -d "vendor/" ]; then
    vendor_size=$(du -sh vendor/ | cut -f1)
    check_pass "Vendor size: $vendor_size"
fi

echo ""

# 8. Git e Versionamento
title "8. Git e Release"

check_start "Verificando status do Git"
if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
    check_warn "Há mudanças não commitadas"
else
    check_pass "Working directory limpo"
fi

check_start "Verificando tags de versão"
if git tag | grep -q "v$VERSION" 2>/dev/null; then
    check_warn "Tag v$VERSION já existe"
else
    check_pass "Tag v$VERSION disponível"
fi

echo ""

# Relatório Final
title "📊 Relatório Final da Validação"
echo ""

echo "Estatísticas:"
echo "  • Total de verificações: $CHECKS_TOTAL"
echo "  • ✅ Passou: $CHECKS_PASSED"
echo "  • ❌ Falhou: $CHECKS_FAILED"
echo "  • ⚠️  Warnings: $CHECKS_WARNINGS"
echo ""

success_rate=$(( (CHECKS_PASSED * 100) / CHECKS_TOTAL ))

if [ $CHECKS_FAILED -eq 0 ]; then
    if [ $CHECKS_WARNINGS -eq 0 ]; then
        success "🎉 Todas as verificações passaram! ($success_rate%)"
        echo ""
        info "O projeto está pronto para release!"
        exit 0
    else
        warning "⚠️  Validação passou com warnings ($success_rate%)"
        echo ""
        info "O projeto pode ser released, mas considere revisar os warnings."
        exit 0
    fi
else
    error "❌ Validação falhou ($success_rate%)"
    echo ""
    echo "Corrija os problemas antes de fazer o release."
    exit 1
fi
