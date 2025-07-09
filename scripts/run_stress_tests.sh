#!/bin/bash

# PivotPHP v1.1.0 High-Performance Stress Tests Runner
# ====================================================

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
REPORTS_DIR="$PROJECT_ROOT/reports/stress"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Functions
print_header() {
    echo -e "${BLUE}==================================================${NC}"
    echo -e "${BLUE}🚀 PivotPHP v1.1.0 High-Performance Stress Tests${NC}"
    echo -e "${BLUE}==================================================${NC}"
}

print_info() {
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

check_requirements() {
    print_info "Checking requirements..."
    
    # Check PHP version
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    print_info "PHP Version: $PHP_VERSION"
    
    # Check memory limit
    MEMORY_LIMIT=$(php -r "echo ini_get('memory_limit');")
    print_info "Memory Limit: $MEMORY_LIMIT"
    
    # Check if Redis is available (for distributed tests)
    if command -v redis-cli &> /dev/null; then
        print_success "Redis available for distributed tests"
    else
        print_warning "Redis not available - distributed tests will be skipped"
    fi
}

prepare_environment() {
    print_info "Preparing test environment..."
    
    # Create reports directory
    mkdir -p "$REPORTS_DIR"
    
    # Increase memory limit for stress tests
    export PHP_MEMORY_LIMIT="512M"
    
    # Set environment for extreme testing
    export APP_ENV="testing"
    export STRESS_TEST_MODE="true"
    
    print_success "Environment prepared"
}

run_individual_test() {
    local test_method=$1
    local test_name=$2
    
    print_info "Running: $test_name"
    
    local output_file="$REPORTS_DIR/stress_${test_method}_${TIMESTAMP}.txt"
    
    if php vendor/bin/phpunit \
        --filter "$test_method" \
        tests/Stress/HighPerformanceStressTest.php \
        --testdox \
        > "$output_file" 2>&1; then
        
        print_success "$test_name completed"
        
        # Extract key metrics from output
        if grep -q "req/s" "$output_file"; then
            local throughput=$(grep -oP '\d+(?= req/s)' "$output_file" | tail -1)
            print_info "  Throughput: ${throughput} req/s"
        fi
        
        if grep -q "memory:" "$output_file"; then
            local memory=$(grep -oP '\d+\.\d+(?=MB)' "$output_file" | tail -1)
            print_info "  Memory usage: ${memory}MB"
        fi
    else
        print_error "$test_name failed - see $output_file for details"
        return 1
    fi
}

run_all_stress_tests() {
    print_header
    check_requirements
    prepare_environment
    
    echo ""
    print_info "Starting stress tests..."
    echo ""
    
    local total_tests=0
    local passed_tests=0
    local failed_tests=0
    
    # Define tests
    declare -A tests=(
        ["testConcurrentRequestHandling"]="Concurrent Request Handling"
        ["testPoolOverflowBehavior"]="Pool Overflow Behavior"
        ["testCircuitBreakerUnderFailures"]="Circuit Breaker Resilience"
        ["testLoadSheddingEffectiveness"]="Load Shedding Effectiveness"
        ["testMemoryManagementUnderPressure"]="Memory Management"
        ["testPerformanceMonitoringAccuracy"]="Performance Monitoring"
        ["testExtremeConcurrentPoolOperations"]="Extreme Pool Operations"
        ["testGracefulDegradation"]="Graceful Degradation"
    )
    
    # Run each test
    for test_method in "${!tests[@]}"; do
        ((total_tests++))
        if run_individual_test "$test_method" "${tests[$test_method]}"; then
            ((passed_tests++))
        else
            ((failed_tests++))
        fi
        echo ""
    done
    
    # Generate summary report
    generate_summary_report "$total_tests" "$passed_tests" "$failed_tests"
}

generate_summary_report() {
    local total=$1
    local passed=$2
    local failed=$3
    
    local report_file="$REPORTS_DIR/stress_summary_${TIMESTAMP}.txt"
    
    {
        echo "PivotPHP v1.1.0 Stress Test Summary"
        echo "==================================="
        echo "Date: $(date)"
        echo "PHP Version: $(php -r 'echo PHP_VERSION;')"
        echo "Memory Limit: $(php -r 'echo ini_get("memory_limit");')"
        echo ""
        echo "Test Results:"
        echo "  Total Tests: $total"
        echo "  Passed: $passed"
        echo "  Failed: $failed"
        echo "  Success Rate: $(( passed * 100 / total ))%"
        echo ""
        echo "Key Metrics:"
        
        # Aggregate metrics from individual test outputs
        for output_file in "$REPORTS_DIR"/stress_*_${TIMESTAMP}.txt; do
            if [ -f "$output_file" ]; then
                echo ""
                echo "From $(basename "$output_file"):"
                grep -E "(throughput|req/s|ops/s|memory|latency)" "$output_file" || true
            fi
        done
        
    } > "$report_file"
    
    echo ""
    echo -e "${BLUE}==================================================${NC}"
    echo -e "${BLUE}📊 STRESS TEST SUMMARY${NC}"
    echo -e "${BLUE}==================================================${NC}"
    echo ""
    echo "Total Tests: $total"
    echo -e "Passed: ${GREEN}$passed${NC}"
    echo -e "Failed: ${RED}$failed${NC}"
    echo -e "Success Rate: $(( passed * 100 / total ))%"
    echo ""
    
    if [ "$failed" -eq 0 ]; then
        print_success "All stress tests passed! 🎉"
        echo ""
        echo "The v1.1.0 high-performance features are working correctly under stress."
    else
        print_error "Some stress tests failed. Review the reports for details."
    fi
    
    echo ""
    echo "📄 Reports saved to: $REPORTS_DIR"
    echo "📄 Summary report: $report_file"
}

# Run based on arguments
case "${1:-all}" in
    concurrent)
        run_individual_test "testConcurrentRequestHandling" "Concurrent Request Handling"
        ;;
    pool)
        run_individual_test "testPoolOverflowBehavior" "Pool Overflow Behavior"
        ;;
    circuit)
        run_individual_test "testCircuitBreakerUnderFailures" "Circuit Breaker Resilience"
        ;;
    shedding)
        run_individual_test "testLoadSheddingEffectiveness" "Load Shedding Effectiveness"
        ;;
    memory)
        run_individual_test "testMemoryManagementUnderPressure" "Memory Management"
        ;;
    monitoring)
        run_individual_test "testPerformanceMonitoringAccuracy" "Performance Monitoring"
        ;;
    extreme)
        run_individual_test "testExtremeConcurrentPoolOperations" "Extreme Pool Operations"
        ;;
    degradation)
        run_individual_test "testGracefulDegradation" "Graceful Degradation"
        ;;
    all|*)
        run_all_stress_tests
        ;;
esac