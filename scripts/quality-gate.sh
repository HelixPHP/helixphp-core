#!/bin/bash

# PivotPHP - Quality Gate Assessment
# Focused quality metrics without unnecessary outputs

set -e

echo "🏆 Quality Gate Assessment..."
echo ""

# Colors for output
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

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[⚠]${NC} $1"
}

# Create reports directory
mkdir -p reports/quality-gate

echo "========================================="
echo "          QUALITY GATE v1.1.3"
echo "========================================="
echo ""

# 1. Static Analysis (Critical)
print_status "1. Static Analysis (PHPStan Level 9)..."
if composer phpstan --no-progress --quiet > reports/quality-gate/phpstan.log 2>&1; then
    print_success "Static Analysis - PASSED"
    PHPSTAN_STATUS="✅ PASSED"
else
    print_error "Static Analysis - FAILED"
    PHPSTAN_STATUS="❌ FAILED"
fi

# 2. Code Style (Critical)
print_status "2. Code Style (PSR-12)..."
if composer cs:check:summary --quiet > reports/quality-gate/codestyle.log 2>&1; then
    print_success "Code Style - PASSED"
    CODESTYLE_STATUS="✅ PASSED"
else
    print_error "Code Style - FAILED"
    CODESTYLE_STATUS="❌ FAILED"
fi

# 3. Security Assessment (Critical)
print_status "3. Security Assessment..."
if composer audit --quiet > reports/quality-gate/security.log 2>&1; then
    print_success "Security Assessment - PASSED"
    SECURITY_STATUS="✅ PASSED"
else
    print_warning "Security Assessment - ISSUES FOUND"
    SECURITY_STATUS="⚠️ ISSUES FOUND"
fi

# 4. Performance Baseline (Informational)
print_status "4. Performance Baseline..."
if timeout 30s php benchmarks/QuietBenchmark.php > reports/quality-gate/performance.log 2>&1; then
    # Extract performance metrics quietly
    if grep -q "ops/sec" reports/quality-gate/performance.log; then
        PERFORMANCE=$(grep "ops/sec" reports/quality-gate/performance.log | head -1 | sed 's/.*📈 //' | sed 's/ ops\/sec.*//')
        print_success "Performance Baseline - ${PERFORMANCE} ops/sec"
        PERFORMANCE_STATUS="✅ ${PERFORMANCE} ops/sec"
    else
        print_success "Performance Baseline - COMPLETED"
        PERFORMANCE_STATUS="✅ COMPLETED"
    fi
else
    print_warning "Performance Baseline - TIMEOUT (acceptable)"
    PERFORMANCE_STATUS="⚠️ TIMEOUT"
fi

# 5. Dependency Health (Informational)
print_status "5. Dependency Health..."
OUTDATED_COUNT=$(composer show --outdated --quiet 2>/dev/null | wc -l || echo "0")
if [ "$OUTDATED_COUNT" -eq 0 ]; then
    print_success "Dependencies - All up to date"
    DEPS_STATUS="✅ UP TO DATE"
else
    print_warning "Dependencies - ${OUTDATED_COUNT} outdated packages"
    DEPS_STATUS="⚠️ ${OUTDATED_COUNT} OUTDATED"
fi

# 6. Code Metrics (Informational)
print_status "6. Code Metrics..."
TOTAL_LINES=$(find src -name "*.php" -exec wc -l {} + 2>/dev/null | tail -1 | awk '{print $1}' || echo "0")
PUBLIC_METHODS=$(grep -r "public function" src --include="*.php" 2>/dev/null | wc -l || echo "0")
DOC_FILES=$(find docs -name "*.md" 2>/dev/null | wc -l || echo "0")

print_success "Code Metrics - ${TOTAL_LINES} lines, ${PUBLIC_METHODS} public methods, ${DOC_FILES} docs"
METRICS_STATUS="✅ ${TOTAL_LINES} lines"

# Calculate Quality Score
CRITICAL_PASSED=0
CRITICAL_TOTAL=3

if [[ "$PHPSTAN_STATUS" == *"PASSED"* ]]; then 
    CRITICAL_PASSED=$((CRITICAL_PASSED + 1))
fi
if [[ "$CODESTYLE_STATUS" == *"PASSED"* ]]; then 
    CRITICAL_PASSED=$((CRITICAL_PASSED + 1))
fi  
if [[ "$SECURITY_STATUS" == *"PASSED"* ]]; then 
    CRITICAL_PASSED=$((CRITICAL_PASSED + 1))
fi

QUALITY_SCORE=$((CRITICAL_PASSED * 100 / CRITICAL_TOTAL))

# Generate Quality Gate Report
cat > reports/quality-gate/QUALITY_GATE_REPORT.md << EOF
# Quality Gate Report
Generated: $(date)
Framework: PivotPHP Core v1.1.3-dev

## Quality Score: ${QUALITY_SCORE}%

### Critical Criteria (Must Pass)
- **Static Analysis**: $PHPSTAN_STATUS
- **Code Style**: $CODESTYLE_STATUS  
- **Security**: $SECURITY_STATUS

### Informational Metrics
- **Performance**: $PERFORMANCE_STATUS
- **Dependencies**: $DEPS_STATUS
- **Code Metrics**: $METRICS_STATUS

## Decision
EOF

if [ "$QUALITY_SCORE" -eq 100 ]; then
    echo "🎉 **QUALITY GATE PASSED** - All critical criteria met" >> reports/quality-gate/QUALITY_GATE_REPORT.md
    GATE_DECISION="PASSED"
else
    echo "❌ **QUALITY GATE FAILED** - Critical criteria not met" >> reports/quality-gate/QUALITY_GATE_REPORT.md
    GATE_DECISION="FAILED"
fi

cat >> reports/quality-gate/QUALITY_GATE_REPORT.md << EOF

## Recommendations
- Review logs in reports/quality-gate/ for details
- Address critical failures before proceeding
- Consider dependency updates for security

## Files Generated
- phpstan.log - Static analysis details
- codestyle.log - Code style violations
- security.log - Security audit results
- performance.log - Performance baseline
- QUALITY_GATE_REPORT.md - This report
EOF

echo ""
echo "========================================="
echo "        QUALITY GATE SUMMARY"
echo "========================================="
echo ""

if [ "$GATE_DECISION" = "PASSED" ]; then
    print_success "Quality Gate PASSED! ✨"
    echo ""
    echo "✅ Static Analysis (PHPStan Level 9)"
    echo "✅ Code Style (PSR-12)"
    echo "✅ Security Assessment"
    echo ""
    echo "📊 Quality Score: ${QUALITY_SCORE}%"
    echo "📁 Report: reports/quality-gate/QUALITY_GATE_REPORT.md"
    echo ""
    echo "🚀 Ready for production!"
    exit 0
else
    print_error "Quality Gate FAILED!"
    echo ""
    echo "Critical issues found:"
    [[ "$PHPSTAN_STATUS" != *"PASSED"* ]] && echo "❌ Static Analysis"
    [[ "$CODESTYLE_STATUS" != *"PASSED"* ]] && echo "❌ Code Style"
    [[ "$SECURITY_STATUS" != *"PASSED"* ]] && echo "❌ Security"
    echo ""
    echo "📊 Quality Score: ${QUALITY_SCORE}%"
    echo "📁 Report: reports/quality-gate/QUALITY_GATE_REPORT.md"
    echo ""
    echo "🔧 Fix critical issues before proceeding"
    exit 1
fi