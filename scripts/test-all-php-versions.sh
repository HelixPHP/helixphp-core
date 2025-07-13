#!/bin/bash

# PivotPHP - Test All PHP Versions
# Tests the framework against multiple PHP versions using Docker

set -e

echo "🐳 Testing PivotPHP across multiple PHP versions..."
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

# Check if Docker is available
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed or not available"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose is not installed or not available"
    exit 1
fi

# Test individual PHP versions in parallel
PHP_VERSIONS=("php81" "php82" "php83" "php84")
FAILED_VERSIONS=()
PASSED_VERSIONS=()

print_status "Starting PHP version tests in parallel..."
echo ""

# Start all tests in background
declare -A PIDS
for version in "${PHP_VERSIONS[@]}"; do
    print_status "Starting $version..."
    
    (
        if docker-compose -f docker-compose.test.yml run --rm "test-$version" > "/tmp/test_output_$version.log" 2>&1; then
            echo "PASSED" > "/tmp/test_result_$version"
        else
            echo "FAILED" > "/tmp/test_result_$version"
        fi
    ) &
    
    PIDS[$version]=$!
done

print_status "All tests started. Waiting for completion..."
echo ""

# Wait for all processes and collect results
for version in "${PHP_VERSIONS[@]}"; do
    wait ${PIDS[$version]}
    
    if [ -f "/tmp/test_result_$version" ]; then
        result=$(cat "/tmp/test_result_$version")
        if [ "$result" = "PASSED" ]; then
            print_success "$version passed"
            PASSED_VERSIONS+=("$version")
        else
            print_error "$version failed"
            FAILED_VERSIONS+=("$version")
            echo "   Log: /tmp/test_output_$version.log"
        fi
        rm -f "/tmp/test_result_$version"
    else
        print_error "$version - no result file"
        FAILED_VERSIONS+=("$version")
    fi
done

# Cleanup temp files
rm -f /tmp/test_output_*.log

# Summary
echo ""
echo "===========================================" 
echo "         PHP VERSION TEST SUMMARY"
echo "==========================================="
echo ""

if [ ${#PASSED_VERSIONS[@]} -gt 0 ]; then
    print_success "Passed versions: ${PASSED_VERSIONS[*]}"
fi

if [ ${#FAILED_VERSIONS[@]} -gt 0 ]; then
    print_error "Failed versions: ${FAILED_VERSIONS[*]}"
    echo ""
    print_error "Some PHP versions failed!"
    echo ""
    echo "🔧 Recommendations:"
    echo "  1. Check compatibility issues in failed versions"
    echo "  2. Review PHPStan errors for version-specific problems"
    echo "  3. Update code to be compatible with all supported PHP versions"
    echo ""
    exit 1
else
    print_success "All PHP versions passed! 🎉"
    echo ""
    echo "📊 Compatibility Status:"
    echo "  ✅ PHP 8.1 Compatible"
    echo "  ✅ PHP 8.2 Compatible"
    echo "  ✅ PHP 8.3 Compatible"
    echo "  ✅ PHP 8.4 Compatible"
fi

# Optional: Run quality metrics
if [[ "$1" == "--with-quality" ]]; then
    echo ""
    print_status "Running quality metrics..."
    
    if docker-compose -f docker-compose.test.yml run --rm quality-check; then
        print_success "Quality metrics generated"
    else
        print_warning "Quality metrics failed (non-blocking)"
    fi
fi

# Cleanup
print_status "Cleaning up Docker containers..."
docker-compose -f docker-compose.test.yml down --remove-orphans --volumes

echo ""
print_success "PHP version testing completed successfully!"
echo ""
echo "🚀 Ready for push!"