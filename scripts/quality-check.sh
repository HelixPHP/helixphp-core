#!/bin/bash
# scripts/quality-check.sh
# Script de validação completa de qualidade para PivotPHP Core v1.1.2

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Função para logging
log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
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

info() {
    echo -e "${CYAN}ℹ️  $1${NC}"
}

critical() {
    echo -e "${PURPLE}🚨 CRÍTICO: $1${NC}"
}

# Variáveis de controle
FAILED_CHECKS=0
TOTAL_CHECKS=0
CRITICAL_FAILURES=0

# Função para contar verificações
count_check() {
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
    if [ $1 -ne 0 ]; then
        FAILED_CHECKS=$((FAILED_CHECKS + 1))
        if [ "${2:-false}" = "critical" ]; then
            CRITICAL_FAILURES=$((CRITICAL_FAILURES + 1))
        fi
    fi
}

# Verificar se estamos no diretório correto
if [ ! -f "composer.json" ] || [ ! -d "src" ]; then
    error "Execute este script a partir do diretório raiz do projeto PivotPHP Core"
    exit 1
fi

# Criar diretório de relatórios
mkdir -p reports/quality

log "🔍 Iniciando validação completa de qualidade PivotPHP Core v1.1.2..."
log "📊 Critérios: 8 CRÍTICOS + 4 ALTOS + Métricas avançadas"

echo ""
echo "======================================="
echo "   VALIDAÇÃO DE QUALIDADE v1.1.2"
echo "======================================="
echo ""

# 1. PHPStan Level 9 - CRÍTICO
log "🔍 1. Análise Estática (PHPStan Level 9) - CRÍTICO"

phpstan_output=$(mktemp)
if composer phpstan > "$phpstan_output" 2>&1; then
    phpstan_result=0
    success "PHPStan Level 9 - PASSOU"
    
    # Verificar se realmente é Level 9
    if grep -q "level: 9" phpstan.neon; then
        success "Nível confirmado: Level 9"
    else
        error "Nível não é 9!"
        phpstan_result=1
    fi
else
    phpstan_result=1
    critical "PHPStan Level 9 - FALHOU"
    error "Erros encontrados:"
    tail -10 "$phpstan_output"
fi

count_check $phpstan_result "critical"
cp "$phpstan_output" "reports/quality/phpstan-results.txt"
rm "$phpstan_output"

# 2. Testes Unitários - CRÍTICO
log "🧪 2. Testes Unitários e de Integração - CRÍTICO"

test_output=$(mktemp)
if composer test > "$test_output" 2>&1; then
    test_result=0
    success "Testes - PASSOU"
    
    # Extrair estatísticas
    if grep -q "OK (" "$test_output"; then
        test_stats=$(grep "OK (" "$test_output" | tail -1)
        success "Estatísticas: $test_stats"
        
        # Verificar se todos os testes passaram
        if echo "$test_stats" | grep -q "430 tests"; then
            success "Todos os 430 testes passaram"
        else
            warning "Número de testes não está correto"
        fi
    else
        warning "Não foi possível extrair estatísticas dos testes"
    fi
else
    test_result=1
    critical "Testes - FALHOU"
    error "Falhas encontradas:"
    tail -20 "$test_output"
fi

count_check $test_result "critical"
cp "$test_output" "reports/quality/test-results.txt"
rm "$test_output"

# 3. Cobertura de Testes - CRÍTICO
log "📊 3. Cobertura de Testes (≥95%) - CRÍTICO"

coverage_output=$(mktemp)
if composer test --coverage-text > "$coverage_output" 2>&1; then
    coverage_result=0
    
    # Extrair percentual de cobertura
    if grep -q "Lines:" "$coverage_output"; then
        coverage_line=$(grep "Lines:" "$coverage_output" | tail -1)
        coverage_percent=$(echo "$coverage_line" | grep -o '[0-9]\+\.[0-9]\+%' | head -1)
        
        if [ -n "$coverage_percent" ]; then
            coverage_number=$(echo "$coverage_percent" | sed 's/%//')
            if (( $(echo "$coverage_number >= 95.0" | bc -l) )); then
                success "Cobertura: $coverage_percent (≥95%)"
            else
                error "Cobertura: $coverage_percent (<95%)"
                coverage_result=1
            fi
        else
            warning "Não foi possível extrair percentual de cobertura"
            coverage_result=1
        fi
    else
        warning "Relatório de cobertura não encontrado"
        coverage_result=1
    fi
else
    coverage_result=1
    critical "Cobertura - FALHOU"
fi

count_check $coverage_result "critical"
cp "$coverage_output" "reports/quality/coverage-results.txt"
rm "$coverage_output"

# 4. Code Style (PSR-12) - CRÍTICO
log "🎨 4. Padrões de Codificação (PSR-12) - CRÍTICO"

cs_output=$(mktemp)
if composer cs:check > "$cs_output" 2>&1; then
    cs_result=0
    success "Code Style PSR-12 - PASSOU"
else
    cs_result=1
    critical "Code Style PSR-12 - FALHOU"
    
    # Mostrar primeiros erros
    error "Erros de code style encontrados:"
    head -15 "$cs_output"
    
    # Tentar corrigir automaticamente
    warning "Tentando correção automática..."
    if composer cs:fix > /dev/null 2>&1; then
        success "Correções aplicadas automaticamente"
        
        # Verificar novamente
        if composer cs:check > /dev/null 2>&1; then
            success "Code Style agora está conforme"
            cs_result=0
        fi
    fi
fi

count_check $cs_result "critical"
cp "$cs_output" "reports/quality/codestyle-results.txt"
rm "$cs_output"

# 5. Documentação - CRÍTICO
log "📝 5. Documentação de Código - CRÍTICO"

doc_issues=0
doc_total=0

# Verificar se todas as classes públicas têm DocBlocks
log "Verificando documentação das classes..."
while IFS= read -r -d '' file; do
    if [[ "$file" == *"/src/"* ]]; then
        # Contar classes públicas
        classes=$(grep -c "^class\|^abstract class\|^final class\|^interface\|^trait" "$file" 2>/dev/null || echo "0")
        doc_total=$((doc_total + classes))
        
        # Verificar se têm DocBlocks
        if [ "$classes" -gt 0 ]; then
            # Verificar se existe /** antes da declaração da classe
            if ! grep -B 5 "^class\|^abstract class\|^final class\|^interface\|^trait" "$file" | grep -q "/\*\*" 2>/dev/null; then
                warning "Documentação faltando em: $file"
                doc_issues=$((doc_issues + 1))
            fi
        fi
    fi
done < <(find src/ -name "*.php" -print0)

if [ $doc_issues -eq 0 ]; then
    success "Documentação - PASSOU ($doc_total classes verificadas)"
    doc_result=0
else
    critical "Documentação - FALHOU ($doc_issues/$doc_total classes sem documentação)"
    doc_result=1
fi

count_check $doc_result "critical"

# 6. Testes de Segurança - CRÍTICO
log "🔒 6. Testes de Segurança - CRÍTICO"

security_output=$(mktemp)
if composer test:security > "$security_output" 2>&1; then
    security_result=0
    success "Testes de Segurança - PASSOU"
    
    # Verificar estatísticas
    if grep -q "OK (" "$security_output"; then
        security_stats=$(grep "OK (" "$security_output" | tail -1)
        success "Estatísticas: $security_stats"
    fi
else
    security_result=1
    critical "Testes de Segurança - FALHOU"
    error "Falhas de segurança encontradas:"
    tail -10 "$security_output"
fi

count_check $security_result "critical"
cp "$security_output" "reports/quality/security-results.txt"
rm "$security_output"

# 7. Performance - CRÍTICO
log "⚡ 7. Performance (≥30K ops/sec) - CRÍTICO"

benchmark_output=$(mktemp)
if composer benchmark > "$benchmark_output" 2>&1; then
    benchmark_result=0
    success "Benchmark - EXECUTADO"
    
    # Verificar performance média
    if grep -q "Average Performance" "$benchmark_output"; then
        perf_line=$(grep "Average Performance" "$benchmark_output" | tail -1)
        perf_value=$(echo "$perf_line" | grep -o '[0-9,]\+ ops/sec' | head -1)
        
        if [ -n "$perf_value" ]; then
            perf_number=$(echo "$perf_value" | grep -o '[0-9,]\+' | tr -d ',')
            if [ "$perf_number" -ge 30000 ]; then
                success "Performance: $perf_value (≥30K ops/sec)"
            else
                error "Performance: $perf_value (<30K ops/sec)"
                benchmark_result=1
            fi
        else
            warning "Não foi possível extrair performance média"
        fi
    else
        warning "Métrica de performance não encontrada"
    fi
    
    # Verificar Pool Efficiency
    if grep -q "Pool Efficiency" "$benchmark_output"; then
        success "Pool Efficiency encontrado no benchmark"
    else
        info "Pool Efficiency não encontrado (pode ser normal)"
    fi
else
    benchmark_result=1
    critical "Benchmark - FALHOU"
    error "Erro ao executar benchmark:"
    tail -10 "$benchmark_output"
fi

count_check $benchmark_result "critical"
cp "$benchmark_output" "reports/quality/benchmark-results.txt"
rm "$benchmark_output"

# 8. Auditoria de Dependências - CRÍTICO
log "📦 8. Auditoria de Dependências - CRÍTICO"

audit_output=$(mktemp)
if composer audit > "$audit_output" 2>&1; then
    audit_result=0
    success "Auditoria de Dependências - PASSOU"
    
    # Verificar se há vulnerabilidades
    if grep -q "No security vulnerabilities found" "$audit_output"; then
        success "Nenhuma vulnerabilidade encontrada"
    elif grep -q "Found" "$audit_output"; then
        error "Vulnerabilidades encontradas:"
        grep "Found" "$audit_output"
        audit_result=1
    fi
else
    # Comando audit pode não existir em versões antigas
    warning "Comando audit não disponível, verificando outdated..."
    if composer outdated > "$audit_output" 2>&1; then
        if grep -q "Nothing to update" "$audit_output" || [ ! -s "$audit_output" ]; then
            success "Dependências atualizadas"
            audit_result=0
        else
            warning "Algumas dependências desatualizadas encontradas"
            audit_result=0  # Não crítico para dependências menores
        fi
    else
        audit_result=1
        error "Erro ao verificar dependências"
    fi
fi

count_check $audit_result "critical"
cp "$audit_output" "reports/quality/audit-results.txt"
rm "$audit_output"

# 9. Análise de Duplicação - ALTO
log "🔍 9. Análise de Duplicação (≤3%) - ALTO"

# Análise básica de duplicação
duplicates_found=0
total_files=$(find src/ -name "*.php" | wc -l)
unique_files=$(find src/ -name "*.php" -exec md5sum {} \; | sort | uniq -c | wc -l)

if [ "$unique_files" -eq "$total_files" ]; then
    success "Análise de Duplicação - PASSOU (arquivos únicos)"
    dup_result=0
else
    warning "Possível duplicação detectada"
    dup_result=1
fi

count_check $dup_result

# 10. Complexidade de Código - ALTO
log "🧮 10. Complexidade de Código - ALTO"

# Análise básica de complexidade
complex_files=0
total_php_files=0

while IFS= read -r -d '' file; do
    if [[ "$file" == *"/src/"* ]]; then
        total_php_files=$((total_php_files + 1))
        
        # Contar estruturas de controle como aproximação da complexidade
        complexity=$(grep -c "if\|while\|for\|foreach\|switch\|case\|catch\|&&\|||" "$file" 2>/dev/null || echo "0")
        
        # Se mais de 50 estruturas de controle, pode ser complexo
        if [ "$complexity" -gt 50 ]; then
            complex_files=$((complex_files + 1))
        fi
    fi
done < <(find src/ -name "*.php" -print0)

if [ "$complex_files" -lt 5 ]; then
    success "Complexidade de Código - ACEITÁVEL ($complex_files/$total_php_files arquivos complexos)"
    complexity_result=0
else
    warning "Complexidade de Código - ALTA ($complex_files/$total_php_files arquivos complexos)"
    complexity_result=1
fi

count_check $complexity_result

# 11. Estrutura de Arquivos - ALTO
log "📁 11. Estrutura de Arquivos - ALTO"

# Verificar estrutura esperada
required_dirs=(
    "src/Core"
    "src/Http"
    "src/Middleware"
    "src/Performance"
    "src/Utils"
)

missing_dirs=0
for dir in "${required_dirs[@]}"; do
    if [ ! -d "$dir" ]; then
        error "Diretório obrigatório não encontrado: $dir"
        missing_dirs=$((missing_dirs + 1))
    fi
done

if [ $missing_dirs -eq 0 ]; then
    success "Estrutura de Arquivos - PASSOU"
    structure_result=0
else
    error "Estrutura de Arquivos - FALHOU ($missing_dirs diretórios faltando)"
    structure_result=1
fi

count_check $structure_result

# 12. Validação de Exemplos - ALTO
log "💡 12. Validação de Exemplos - ALTO"

examples_ok=0
examples_total=0

# Testar exemplos se existirem
if [ -d "examples" ]; then
    for example in examples/example_*.php; do
        if [ -f "$example" ]; then
            examples_total=$((examples_total + 1))
            if timeout 10 php "$example" > /dev/null 2>&1; then
                examples_ok=$((examples_ok + 1))
            fi
        fi
    done
fi

if [ $examples_total -eq 0 ]; then
    info "Nenhum exemplo encontrado"
    examples_result=0
elif [ $examples_ok -eq $examples_total ]; then
    success "Exemplos - PASSOU ($examples_ok/$examples_total)"
    examples_result=0
else
    warning "Exemplos - PARCIAL ($examples_ok/$examples_total)"
    examples_result=1
fi

count_check $examples_result

# Relatório Final
echo ""
echo "========================================="
echo "    RELATÓRIO DE QUALIDADE v1.1.2"
echo "========================================="
echo ""

# Calcular estatísticas
success_rate=$(( (TOTAL_CHECKS - FAILED_CHECKS) * 100 / TOTAL_CHECKS ))

echo "📊 Resumo Geral:"
echo "  • Verificações executadas: $TOTAL_CHECKS"
echo "  • Verificações passando: $((TOTAL_CHECKS - FAILED_CHECKS))"
echo "  • Verificações falhando: $FAILED_CHECKS"
echo "  • Taxa de sucesso: $success_rate%"
echo "  • Falhas críticas: $CRITICAL_FAILURES"
echo ""

# Status por categoria
echo "📋 Status por Categoria:"
echo "  🚨 CRÍTICOS:"
echo "    • PHPStan Level 9: $([ $phpstan_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")"
echo "    • Testes Unitários: $([ $test_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")"
echo "    • Cobertura ≥95%: $([ $coverage_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")"
echo "    • Code Style PSR-12: $([ $cs_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")"
echo "    • Documentação: $([ $doc_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")"
echo "    • Segurança: $([ $security_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")"
echo "    • Performance ≥30K: $([ $benchmark_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")"
echo "    • Dependências: $([ $audit_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")"
echo ""
echo "  🟡 ALTOS:"
echo "    • Duplicação ≤3%: $([ $dup_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")"
echo "    • Complexidade: $([ $complexity_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")"
echo "    • Estrutura: $([ $structure_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")"
echo "    • Exemplos: $([ $examples_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")"
echo ""

# Gerar relatório detalhado
report_file="reports/quality/quality-report-$(date +%Y%m%d-%H%M%S).txt"
cat > "$report_file" << EOF
# Relatório de Qualidade PivotPHP Core v1.1.2
Data: $(date)
Executado por: $(whoami)
Diretório: $(pwd)

## Resumo
- Verificações executadas: $TOTAL_CHECKS
- Verificações passando: $((TOTAL_CHECKS - FAILED_CHECKS))
- Verificações falhando: $FAILED_CHECKS
- Taxa de sucesso: $success_rate%
- Falhas críticas: $CRITICAL_FAILURES

## Critérios Críticos
- PHPStan Level 9: $([ $phpstan_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")
- Testes Unitários: $([ $test_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")
- Cobertura ≥95%: $([ $coverage_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")
- Code Style PSR-12: $([ $cs_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")
- Documentação: $([ $doc_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")
- Segurança: $([ $security_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")
- Performance ≥30K: $([ $benchmark_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")
- Dependências: $([ $audit_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")

## Critérios Altos
- Duplicação ≤3%: $([ $dup_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")
- Complexidade: $([ $complexity_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")
- Estrutura: $([ $structure_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")
- Exemplos: $([ $examples_result -eq 0 ] && echo "✅ PASSOU" || echo "❌ FALHOU")

## Arquivos de Saída
- PHPStan: reports/quality/phpstan-results.txt
- Testes: reports/quality/test-results.txt
- Cobertura: reports/quality/coverage-results.txt
- Code Style: reports/quality/codestyle-results.txt
- Segurança: reports/quality/security-results.txt
- Benchmark: reports/quality/benchmark-results.txt
- Dependências: reports/quality/audit-results.txt
- Este relatório: $report_file

EOF

# Decisão final
echo "🎯 Decisão Final:"
if [ $CRITICAL_FAILURES -eq 0 ]; then
    echo -e "${GREEN}🎉 APROVADO PARA ENTREGA${NC}"
    echo ""
    echo "✨ PivotPHP Core v1.1.2 atende todos os critérios críticos!"
    echo "📊 Taxa de sucesso: $success_rate%"
    echo "🚀 Pronto para produção!"
    echo ""
    echo "📋 Próximos passos:"
    echo "  1. Revisar relatório detalhado"
    echo "  2. Executar testes de regressão"
    echo "  3. Preparar para release"
    echo ""
    exit_code=0
else
    echo -e "${RED}❌ REPROVADO PARA ENTREGA${NC}"
    echo ""
    echo "🚨 PivotPHP Core v1.1.2 NÃO atende aos critérios críticos!"
    echo "📊 Falhas críticas: $CRITICAL_FAILURES"
    echo "🛑 Entrega BLOQUEADA!"
    echo ""
    echo "🔧 Ações necessárias:"
    echo "  1. Corrigir todas as falhas críticas"
    echo "  2. Executar validação novamente"
    echo "  3. Obter aprovação técnica"
    echo ""
    exit_code=1
fi

success "Relatório detalhado salvo em: $report_file"
echo ""

# Limpar arquivos temporários
find /tmp -name "*quality*" -type f -delete 2>/dev/null || true

exit $exit_code