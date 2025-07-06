#!/bin/bash

# HelixPHP v1.0.0 - Validador de Documentação
# Valida a nova estrutura de documentação v1.0.0

echo "📚 Validando estrutura de documentação HelixPHP v1.0.0..."
echo "============================================================="
echo ""

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
PASSED=0

# Função para validar arquivo
validate_file() {
    local file="$1"
    local description="$2"
    local min_size="${3:-500}"

    if [ -f "$file" ]; then
        local size=$(stat -c%s "$file" 2>/dev/null || echo "0")
        if [ "$size" -gt "$min_size" ]; then
            print_success "$description ($size bytes)"
            ((PASSED++))
        else
            print_warning "$description tem pouco conteúdo ($size bytes)"
            ((WARNINGS++))
        fi
    else
        print_error "$description não encontrado: $file"
        ((ERRORS++))
    fi
}

# Função para validar diretório
validate_directory() {
    local dir="$1"
    local description="$2"

    if [ -d "$dir" ]; then
        print_success "$description"
        ((PASSED++))
    else
        print_error "$description não encontrado: $dir"
        ((ERRORS++))
    fi
}

print_status "Validando estrutura principal de documentação..."

# Estrutura principal
validate_directory "docs" "Diretório principal docs/"
validate_directory "docs/releases" "Diretório de releases"
validate_directory "docs/technical" "Diretório técnico"
validate_directory "docs/performance" "Diretório de performance"
validate_directory "docs/implementions" "Diretório de implementações"
validate_directory "docs/testing" "Diretório de testes"
validate_directory "docs/contributing" "Diretório de contribuição"

echo ""
print_status "Validando documentação principal..."

# Documentação principal
validate_file "README.md" "README principal" 1000
validate_file "docs/index.md" "Índice principal da documentação" 1000
validate_file "CHANGELOG.md" "Changelog" 500
validate_file "CONTRIBUTING.md" "Guia de contribuição" 1000

echo ""
print_status "Validando documentação de releases..."

# Releases
validate_file "docs/releases/README.md" "Índice de releases" 1000
validate_file "docs/releases/FRAMEWORK_OVERVIEW_v1.0.0.md" "Overview v1.0.0 (ATUAL)" 10000
validate_file "docs/releases/FRAMEWORK_OVERVIEW_v1.0.0.md" "Overview v1.0.0" 5000
validate_file "docs/releases/FRAMEWORK_OVERVIEW_v1.0.0.md" "Overview v1.0.0" 5000

echo ""
print_status "Validando documentação técnica..."

# Documentação técnica
validate_file "docs/technical/application.md" "Documentação da Application" 5000
validate_file "docs/technical/http/request.md" "Documentação de Request" 5000
validate_file "docs/technical/http/response.md" "Documentação de Response" 5000
validate_file "docs/technical/routing/router.md" "Documentação do Router" 5000
validate_file "docs/technical/middleware/README.md" "Índice de middlewares" 5000
validate_file "docs/technical/authentication/usage_native.md" "Autenticação nativa" 10000

# Verificar documentação OpenAPI
if [ -f "docs/technical/http/openapi_documentation.md" ]; then
    validate_file "docs/technical/http/openapi_documentation.md" "Documentação OpenAPI" 5000
else
    print_warning "Documentação OpenAPI não encontrada (opcional)"
    ((WARNINGS++))
fi

echo ""
print_status "Validando documentação de implementações..."

validate_file "docs/implementions/usage_basic.md" "Guia básico de uso" 5000

echo ""
print_status "Validando documentação de performance..."

validate_file "docs/performance/PerformanceMonitor.md" "Monitor de performance" 5000
validate_file "docs/performance/benchmarks/README.md" "Documentação de benchmarks" 10000

echo ""
print_status "Validando documentação de testes..."

validate_file "docs/testing/api_testing.md" "Testes de API" 5000

echo ""
print_status "Validando documentação de contribuição..."

validate_file "docs/contributing/README.md" "Guia de contribuição" 5000

echo ""
print_status "Verificando conteúdo específico v1.0.0..."

# Verificar conteúdo específico da v1.0.0
if [ -f "docs/releases/FRAMEWORK_OVERVIEW_v1.0.0.md" ]; then
    content=$(cat "docs/releases/FRAMEWORK_OVERVIEW_v1.0.0.md")

    if echo "$content" | grep -q "2.69M" && echo "$content" | grep -q "PHP 8.4.8" && echo "$content" | grep -q "JIT"; then
        print_success "FRAMEWORK_OVERVIEW_v1.0.0.md contém métricas de performance v1.0.0"
        ((PASSED++))
    else
        print_warning "FRAMEWORK_OVERVIEW_v1.0.0.md pode estar incompleto (faltam métricas v1.0.0)"
        ((WARNINGS++))
    fi
fi

# Verificar se índice principal está atualizado
if [ -f "docs/index.md" ]; then
    content=$(cat "docs/index.md")

    if echo "$content" | grep -q "v1.0.0" && echo "$content" | grep -q "releases/" && echo "$content" | grep -q "technical/"; then
        print_success "Índice principal atualizado para estrutura v1.0.0"
        ((PASSED++))
    else
        print_warning "Índice principal pode não estar totalmente atualizado para v1.0.0"
        ((WARNINGS++))
    fi
fi

echo ""
print_status "Verificando migração de arquivos da raiz..."

# Verificar se arquivos antigos foram movidos da raiz
old_files=("FRAMEWORK_OVERVIEW_v1.0.0.md" "FRAMEWORK_OVERVIEW_v1.0.0.md" "FRAMEWORK_OVERVIEW_v1.0.0.md")

for file in "${old_files[@]}"; do
    if [ -f "$file" ]; then
        print_warning "Arquivo deveria ter sido movido para docs/releases/: $file"
        ((WARNINGS++))
    else
        print_success "Arquivo movido corretamente da raiz: $file"
        ((PASSED++))
    fi
done

echo ""
echo "==========================================="
echo "📊 RELATÓRIO DE VALIDAÇÃO DE DOCUMENTAÇÃO"
echo "==========================================="
echo ""

total=$((PASSED + WARNINGS + ERRORS))
if [ $total -gt 0 ]; then
    success_rate=$((PASSED * 100 / total))
else
    success_rate=0
fi

echo "📈 Estatísticas:"
echo "  Total de verificações: $total"
echo "  Sucessos: $PASSED"
echo "  Avisos: $WARNINGS"
echo "  Erros: $ERRORS"
echo "  Taxa de sucesso: $success_rate%"
echo ""

if [ $ERRORS -eq 0 ]; then
    print_success "🎉 VALIDAÇÃO DE DOCUMENTAÇÃO CONCLUÍDA COM SUCESSO!"
    echo ""
    echo "✅ A documentação HelixPHP v1.0.0 está:"
    echo "   • Bem estruturada e organizada"
    echo "   • Atualizada para a versão v1.0.0"
    echo "   • Pronta para uso por desenvolvedores"
    echo "   • Compatível com publicação"

    if [ $WARNINGS -gt 0 ]; then
        echo ""
        echo "⚠️  Considera resolver os $WARNINGS aviso(s) para melhor qualidade"
    fi

    echo ""
    echo "📖 Estrutura de navegação:"
    echo "   • Início: docs/index.md"
    echo "   • Releases: docs/releases/"
    echo "   • Técnico: docs/technical/"
    echo "   • Performance: docs/performance/"
    echo "   • Implementações: docs/implementions/"
    echo "   • Testes: docs/testing/"
    echo "   • Contribuição: docs/contributing/"

    exit 0
else
    print_error "❌ VALIDAÇÃO DE DOCUMENTAÇÃO FALHOU!"
    echo ""
    echo "🚨 Encontrados $ERRORS erro(s) crítico(s) na documentação."
    echo "   Corrija os problemas antes de publicar o projeto."
    echo ""
    echo "🔧 Ações recomendadas:"
    echo "   • Verifique se todos os diretórios existem"
    echo "   • Crie arquivos de documentação faltantes"
    echo "   • Garanta que arquivos tenham conteúdo adequado"
    echo "   • Execute novamente após correções"

    exit 1
fi
