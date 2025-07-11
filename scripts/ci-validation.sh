#!/bin/bash

# PivotPHP - Minimal CI/CD Validation
# Quick critical validations only - tests are done locally via Docker

set -e

echo "⚡ Running minimal CI/CD validations..."
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

# Check if dependencies are installed
if [ ! -d "vendor" ]; then
    print_error "Dependencies not found. Run 'composer install' first."
    exit 1
fi

echo "========================================="
echo "    MINIMAL CI/CD VALIDATION"
echo "========================================="
echo ""

# 1. PHPStan Level 9 (catches breaking changes & type issues)
print_status "1. Static Analysis (PHPStan Level 9)..."
if composer phpstan --no-progress >/dev/null 2>&1; then
    print_success "PHPStan Level 9 - PASSED"
else
    print_error "PHPStan Level 9 - FAILED"
    echo "❌ Static analysis failed. This catches:"
    echo "   • Type errors"
    echo "   • Breaking changes"
    echo "   • Logic issues"
    echo ""
    echo "Run 'composer phpstan' for details"
    exit 1
fi

# 2. PSR-12 Code Style (quick check)
print_status "2. Code Style (PSR-12)..."
if composer cs:check:summary >/dev/null 2>&1; then
    print_success "PSR-12 Compliance - PASSED"
else
    print_error "PSR-12 Compliance - FAILED"
    echo "❌ Code style issues found."
    echo "Run 'composer cs:fix' to auto-fix"
    exit 1
fi

# 3. Composer validation (quick syntax check)
print_status "3. Composer Configuration..."
if composer validate --strict >/dev/null 2>&1; then
    print_success "Composer Configuration - PASSED"
else
    print_error "Composer Configuration - FAILED"
    echo "❌ composer.json validation failed"
    exit 1
fi

# 4. Basic autoload check
print_status "4. Autoload Check..."
if composer dump-autoload --optimize >/dev/null 2>&1; then
    print_success "Autoload Generation - PASSED"
else
    print_error "Autoload Generation - FAILED"
    exit 1
fi

echo ""
echo "========================================="
echo "        CI/CD VALIDATION SUMMARY"
echo "========================================="
echo ""
print_success "All critical validations passed! ✨"
echo ""
echo "✅ Static Analysis (PHPStan Level 9)"
echo "✅ Code Style (PSR-12)"
echo "✅ Composer Configuration"
echo "✅ Autoload Generation"
echo ""
echo "📋 Note: Full tests are validated locally via Docker"
echo "🐳 Run: ./scripts/test-all-php-versions.sh"
echo ""
echo "🚀 CI/CD Ready!"