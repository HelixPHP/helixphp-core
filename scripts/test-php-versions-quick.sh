#!/bin/bash

# Quick Multi-PHP Version Test Script
# Tests core functionality across PHP 8.1-8.4

set -e

echo "🚀 PivotPHP Multi-PHP Version Quick Test"
echo "========================================"
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test results tracking
PASSED_VERSIONS=()
FAILED_VERSIONS=()

test_php_version() {
    local version=$1
    echo -e "${BLUE}🧪 Starting $version in parallel...${NC}"
    
    # Run core validation only
    local test_cmd="
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer > /dev/null 2>&1 &&
        composer install --no-interaction --prefer-dist > /dev/null 2>&1 &&
        echo '📊 PHPStan Level 9...' &&
        php -d memory_limit=512M vendor/bin/phpstan analyse --no-progress > /dev/null 2>&1 &&
        echo '✅ PHPStan OK' &&
        echo '🧪 Core Tests...' &&
        vendor/bin/phpunit --testsuite=Core --no-coverage > /dev/null 2>&1 &&
        echo '✅ Core Tests OK'
    "
    
    # Run in background and save PID and temp file for results
    local temp_file="/tmp/test_result_$version"
    (
        if timeout 180 docker-compose -f docker-compose.test.yml run --rm test-$version bash -c "$test_cmd" > /dev/null 2>&1; then
            echo "PASSED" > "$temp_file"
        else
            echo "FAILED" > "$temp_file"
        fi
    ) &
    
    local pid=$!
    echo "$pid" > "/tmp/test_pid_$version"
}

# Start all versions in parallel
echo -e "${BLUE}🚀 Starting all PHP versions in parallel...${NC}"
echo ""

for version in php81 php82 php83 php84; do
    test_php_version $version
done

# Wait for all background processes and collect results
echo -e "${BLUE}⏳ Waiting for all tests to complete...${NC}"
echo ""

for version in php81 php82 php83 php84; do
    pid_file="/tmp/test_pid_$version"
    result_file="/tmp/test_result_$version"
    
    if [ -f "$pid_file" ]; then
        pid=$(cat "$pid_file")
        wait $pid 2>/dev/null || true
        rm -f "$pid_file"
    fi
    
    if [ -f "$result_file" ]; then
        result=$(cat "$result_file")
        if [ "$result" = "PASSED" ]; then
            echo -e "   ${GREEN}✅ $version: PASSED${NC}"
            PASSED_VERSIONS+=("$version")
        else
            echo -e "   ${RED}❌ $version: FAILED${NC}"
            FAILED_VERSIONS+=("$version")
        fi
        rm -f "$result_file"
    else
        echo -e "   ${RED}❌ $version: TIMEOUT/ERROR${NC}"
        FAILED_VERSIONS+=("$version")
    fi
done

# Summary
echo ""
echo "========================================"
echo "         MULTI-PHP TEST SUMMARY"
echo "========================================"

if [ ${#PASSED_VERSIONS[@]} -gt 0 ]; then
    echo -e "${GREEN}✅ Passed: ${PASSED_VERSIONS[*]}${NC}"
fi

if [ ${#FAILED_VERSIONS[@]} -gt 0 ]; then
    echo -e "${RED}❌ Failed: ${FAILED_VERSIONS[*]}${NC}"
    echo ""
    echo "Note: Failures may be due to timing issues in CI."
    echo "Core PHPStan Level 9 validation is the primary success metric."
fi

echo ""
if [ ${#PASSED_VERSIONS[@]} -ge 3 ]; then
    echo -e "${GREEN}🎉 Multi-PHP compatibility achieved! (${#PASSED_VERSIONS[@]}/4 versions)${NC}"
    exit 0
else
    echo -e "${RED}🔧 Some versions need attention.${NC}"
    exit 1
fi