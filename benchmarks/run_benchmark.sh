#!/bin/bash

# Express PHP Framework - Performance Benchmark Runner
# Runs comprehensive performance tests and generates reports

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
ITERATIONS=1000
WARMUP=true
COMPARE=false
BASELINE=""
RUN_ALL=false
BENCHMARK_CATEGORY="normal"

# Function to display help
show_help() {
    echo "Express PHP Framework - Performance Benchmark Runner"
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -i, --iterations NUM    Number of iterations per test (default: 1000)"
    echo "  -q, --quick            Quick benchmark (100 iterations) - Low Quantity"
    echo "  -f, --full             Full benchmark (10000 iterations) - High Quantity"
    echo "  -a, --all              Run all benchmarks (quick, normal, full)"
    echo "  --no-warmup            Skip warmup phase"
    echo "  -c, --compare FILE     Compare with baseline report"
    echo "  -b, --baseline         Save as baseline for future comparisons"
    echo "  -h, --help             Show this help message"
    echo ""
    echo "Benchmark Categories:"
    echo "  - Low Quantity (100 iterations): Quick testing for development"
    echo "  - Normal Quantity (1000 iterations): Standard performance testing"
    echo "  - High Quantity (10000 iterations): Comprehensive performance analysis"
    echo ""
    echo "Examples:"
    echo "  $0                     # Run normal benchmark (1000 iterations)"
    echo "  $0 -q                  # Quick benchmark (100 iterations)"
    echo "  $0 -f                  # Full benchmark (10000 iterations)"
    echo "  $0 -a                  # Run all benchmarks (low, normal, high)"
    echo "  $0 -i 5000             # Custom iterations"
    echo "  $0 -b                  # Save as baseline"
    echo "  $0 -c baseline.json    # Compare with baseline"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -i|--iterations)
            ITERATIONS="$2"
            shift 2
            ;;
        -q|--quick)
            ITERATIONS=100
            BENCHMARK_CATEGORY="low"
            shift
            ;;
        -f|--full)
            ITERATIONS=10000
            BENCHMARK_CATEGORY="high"
            shift
            ;;
        -a|--all)
            RUN_ALL=true
            shift
            ;;
        --no-warmup)
            WARMUP=false
            shift
            ;;
        -c|--compare)
            COMPARE=true
            BASELINE="$2"
            shift 2
            ;;
        -b|--baseline)
            BASELINE="baseline"
            shift
            ;;
        -h|--help)
            show_help
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            show_help
            exit 1
            ;;
    esac
done

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo -e "${RED}Error: This script must be run from the project root directory${NC}"
    exit 1
fi

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}Installing dependencies...${NC}"
    composer install --no-dev --optimize-autoloader
fi

echo -e "${BLUE}🚀 Express PHP Framework - Performance Benchmark${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

# Display system information
echo -e "${YELLOW}📋 System Information:${NC}"
echo "  OS: $(uname -s) $(uname -r)"
echo "  PHP Version: $(php -v | head -n1)"
echo "  Memory Limit: $(php -r 'echo ini_get("memory_limit");')"
echo "  CPU: $(nproc) cores"
echo "  Iterations: ${ITERATIONS}"
echo ""

# Create benchmarks directory if it doesn't exist
mkdir -p benchmarks/reports

# Function to run a single benchmark category
run_benchmark_category() {
    local category=$1
    local iterations=$2
    local category_name=$3

    echo -e "${GREEN}🔄 Running ${category_name} performance tests (${iterations} iterations)...${NC}"
    echo ""

    # Execute the benchmark
    php benchmarks/ExpressPhpBenchmark.php $iterations

    # Get the latest generated report
    LATEST_REPORT=$(ls -t benchmarks/benchmark_report_*.json 2>/dev/null | head -n1)

    if [ -f "$LATEST_REPORT" ]; then
        # Create category-specific filename
        TIMESTAMP=$(date '+%Y-%m-%d_%H-%M-%S')
        CATEGORY_REPORT="benchmarks/reports/benchmark_${category}_${TIMESTAMP}.json"
        CATEGORY_SUMMARY="benchmarks/reports/PERFORMANCE_SUMMARY_${category}.md"

        # Move and rename the report
        mv "$LATEST_REPORT" "$CATEGORY_REPORT"

        # Move and rename the summary if it exists
        if [ -f benchmarks/PERFORMANCE_SUMMARY.md ]; then
            mv benchmarks/PERFORMANCE_SUMMARY.md "$CATEGORY_SUMMARY"
        fi

        echo -e "${GREEN}✅ ${category_name} benchmark completed!${NC}"
        echo -e "${BLUE}📋 Report: $CATEGORY_REPORT${NC}"
        echo -e "${BLUE}📄 Summary: $CATEGORY_SUMMARY${NC}"
        echo ""
    fi
}

# Function to run all benchmark categories
run_all_benchmarks() {
    echo -e "${YELLOW}🎯 Running comprehensive benchmark suite...${NC}"
    echo ""

    # Low quantity benchmark
    run_benchmark_category "low" 100 "Low Quantity"

    # Normal quantity benchmark
    run_benchmark_category "normal" 1000 "Normal Quantity"

    # High quantity benchmark
    run_benchmark_category "high" 10000 "High Quantity"

    # Generate comprehensive comparison report
    echo -e "${YELLOW}📊 Generating comprehensive comparison report...${NC}"
    php benchmarks/generate_comprehensive_report.php

    echo -e "${GREEN}🎉 Comprehensive benchmark suite completed!${NC}"
    echo ""
}

# Main execution logic
if [ "$RUN_ALL" = true ]; then
    run_all_benchmarks
    exit 0
fi

# Run the benchmark
run_benchmark_category "$BENCHMARK_CATEGORY" "$ITERATIONS" "$(echo $BENCHMARK_CATEGORY | sed 's/.*/\u&/') Quantity"

# Handle baseline saving
if [ "$BASELINE" = "baseline" ]; then
    LATEST_REPORT=$(ls -t benchmarks/reports/benchmark_${BENCHMARK_CATEGORY}_*.json 2>/dev/null | head -n1)
    if [ -f "$LATEST_REPORT" ]; then
        cp "$LATEST_REPORT" "benchmarks/reports/baseline_${BENCHMARK_CATEGORY}.json"
        echo -e "${GREEN}✅ Baseline saved to benchmarks/reports/baseline_${BENCHMARK_CATEGORY}.json${NC}"
    fi
fi

# Handle comparison
if [ "$COMPARE" = true ] && [ -n "$BASELINE" ]; then
    echo -e "${YELLOW}📊 Comparing with baseline...${NC}"
    LATEST_REPORT=$(ls -t benchmarks/reports/benchmark_${BENCHMARK_CATEGORY}_*.json 2>/dev/null | head -n1)
    if [ -f "$LATEST_REPORT" ]; then
        php benchmarks/compare_benchmarks.php "benchmarks/reports/$BASELINE" "$LATEST_REPORT"
    fi
fi

echo ""
echo -e "${GREEN}✅ Benchmark completed successfully!${NC}"
echo ""
echo -e "${BLUE}📁 Reports location:${NC}"
echo "  benchmarks/reports/"
echo ""
echo -e "${BLUE}🔗 Next steps:${NC}"
echo "  1. Review the performance summary"
echo "  2. Check detailed JSON report for specific metrics"
echo "  3. Compare with previous benchmarks if available"
echo "  4. Use results to optimize performance-critical code"
