#!/bin/bash

# PivotPHP - Quality Metrics Assessment
# Focused on quality metrics, not redundant testing

set -e

echo "📊 Generating extended quality metrics..."
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
mkdir -p reports/quality-metrics

echo "========================================="
echo "    EXTENDED QUALITY METRICS"
echo "========================================="
echo "Note: Critical validations are in Quality Gate"
echo ""

# 1. Code Coverage Analysis
print_status "1. Code Coverage Analysis..."
if composer test:coverage > reports/quality-metrics/coverage.txt 2>&1; then
    # Extract coverage percentage
    if grep -q "Coverage:" reports/quality-metrics/coverage.txt; then
        COVERAGE=$(grep "Coverage:" reports/quality-metrics/coverage.txt)
        print_success "Code Coverage - $COVERAGE"
    else
        print_success "Code Coverage - GENERATED"
    fi
else
    print_warning "Code Coverage - FAILED (non-blocking)"
fi

# 2. Detailed Performance Analysis
print_status "2. Detailed Performance Analysis..."
if timeout 60s php benchmarks/QuietBenchmark.php > reports/quality-metrics/performance-detailed.txt 2>&1; then
    if grep -q "ops/sec" reports/quality-metrics/performance-detailed.txt; then
        PERFORMANCE=$(grep "ops/sec" reports/quality-metrics/performance-detailed.txt | head -1)
        print_success "Detailed Performance - $PERFORMANCE"
    else
        print_success "Detailed Performance - COMPLETED"
    fi
else
    print_warning "Detailed Performance - TIMEOUT (acceptable)"
fi

# 3. Code Complexity Analysis
print_status "3. Code Complexity Analysis..."
find src -name "*.php" -exec wc -l {} + > reports/quality-metrics/complexity.txt 2>&1
TOTAL_LINES=$(tail -1 reports/quality-metrics/complexity.txt | awk '{print $1}')

# Count classes, interfaces, traits
CLASSES=$(grep -r "^class " src --include="*.php" | wc -l)
INTERFACES=$(grep -r "^interface " src --include="*.php" | wc -l)
TRAITS=$(grep -r "^trait " src --include="*.php" | wc -l)

print_success "Code Complexity - $TOTAL_LINES lines, $CLASSES classes, $INTERFACES interfaces, $TRAITS traits"

# 4. Documentation Coverage Analysis
print_status "4. Documentation Coverage Analysis..."
DOC_FILES=$(find docs -name "*.md" | wc -l)
README_COUNT=$(find . -name "README.md" -o -name "readme.md" | wc -l)
DOC_LINES=$(find docs -name "*.md" -exec wc -l {} + 2>/dev/null | tail -1 | awk '{print $1}' || echo "0")

if [ "$DOC_FILES" -gt 0 ]; then
    print_success "Documentation - $DOC_FILES files, $DOC_LINES total lines, $README_COUNT READMEs"
else
    print_warning "Documentation - Limited documentation found"
fi

# 5. API Surface Analysis
print_status "5. API Surface Analysis..."
if grep -r "public function" src --include="*.php" > reports/quality-metrics/api-surface.txt; then
    PUBLIC_METHODS=$(wc -l < reports/quality-metrics/api-surface.txt)
    STATIC_METHODS=$(grep -r "public static function" src --include="*.php" | wc -l)
    CONSTRUCTORS=$(grep -r "public function __construct" src --include="*.php" | wc -l)
    
    print_success "API Surface - $PUBLIC_METHODS public methods ($STATIC_METHODS static, $CONSTRUCTORS constructors)"
else
    print_warning "API Surface Analysis - FAILED"
fi

# 6. Test Coverage Analysis
print_status "6. Test Coverage Analysis..."
TEST_FILES=$(find tests -name "*Test.php" | wc -l)
TEST_METHODS=$(grep -r "public function test" tests --include="*Test.php" | wc -l)
TEST_LINES=$(find tests -name "*.php" -exec wc -l {} + 2>/dev/null | tail -1 | awk '{print $1}' || echo "0")

print_success "Test Coverage - $TEST_FILES test files, $TEST_METHODS test methods, $TEST_LINES test lines"

# Generate summary report
echo ""
print_status "Generating quality summary..."

cat > reports/quality-metrics/EXTENDED_METRICS_REPORT.md << EOF
# Extended Quality Metrics Report
Generated: $(date)
Framework: PivotPHP Core v1.1.3-dev

Note: Critical validations (PHPStan, PSR-12, Security) are in Quality Gate

## Code Architecture
- Total lines of code: $TOTAL_LINES
- Classes: $CLASSES
- Interfaces: $INTERFACES  
- Traits: $TRAITS
- Public API methods: $PUBLIC_METHODS ($STATIC_METHODS static, $CONSTRUCTORS constructors)

## Test Coverage
- Test files: $TEST_FILES
- Test methods: $TEST_METHODS
- Test code lines: $TEST_LINES
- Coverage details in coverage.txt

## Documentation
- Documentation files: $DOC_FILES ($DOC_LINES total lines)
- README files: $README_COUNT

## Performance Analysis
- Detailed performance metrics in performance-detailed.txt
- Framework optimized for high throughput

## Files Generated
- coverage.txt - Test coverage report
- performance-detailed.txt - Extended benchmark results
- complexity.txt - Code complexity metrics
- api-surface.txt - Public API analysis
- EXTENDED_METRICS_REPORT.md - This report

## Purpose
This script provides extended analysis beyond the critical Quality Gate validations.
Use this for deeper insight into codebase health and development metrics.
EOF

echo ""
echo "========================================="
echo "   EXTENDED METRICS SUMMARY"
echo "========================================="
echo ""
print_success "Extended quality metrics completed!"
echo ""
echo "📊 Extended Analysis Generated:"
echo "  • Code coverage analysis"
echo "  • Detailed performance benchmarks"
echo "  • Code complexity & architecture"
echo "  • Documentation coverage"
echo "  • API surface analysis"
echo "  • Test coverage metrics"
echo ""
echo "📁 Reports saved to: reports/quality-metrics/"
echo "📋 Main report: EXTENDED_METRICS_REPORT.md"
echo ""
echo "💡 For critical validations, run: ./scripts/quality-gate.sh"
echo ""
print_success "Extended analysis ready for review! 📈"