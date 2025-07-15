#!/bin/bash

# PivotPHP Core - Release Preparation Script
# Validates and prepares the project for release with automatic version detection

set -e

# Load shared utilities
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../utils/version-utils.sh"

# Validate project context and change to project root
validate_project_context || exit 1
cd_to_project_root || exit 1

# Get version automatically
VERSION=$(get_version) || exit 1
PROJECT_ROOT=$(get_project_root) || exit 1

print_version_banner
echo "🚀 Release Preparation"
echo ""

# 1. Check for sensitive files
echo "🔍 Checking for sensitive files..."

if [ -f ".env" ]; then
    warning "File .env found - ensure it's in .gitignore"
fi

if [ -d "vendor" ]; then
    warning "Directory vendor/ found - will be ignored in publication"
fi

if [ -f "composer.lock" ]; then
    info "composer.lock found - normal for applications, optional for libraries"
fi

# 2. Validate basic structure
echo "📁 Validating project structure..."

required_files=("composer.json" "README.md" "LICENSE")
for file in "${required_files[@]}"; do
    if [ -f "$file" ]; then
        info "File $file present"
    else
        error "Required file $file not found"
    fi
done

required_dirs=("src" "docs")
for dir in "${required_dirs[@]}"; do
    if [ -d "$dir" ]; then
        info "Directory $dir present"
    else
        error "Required directory $dir not found"
    fi
done

# 3. Check PHP syntax
echo "🔧 Checking PHP syntax..."

if find src -name "*.php" -exec php -l {} \; > /dev/null 2>&1; then
    info "PHP syntax valid in all files"
else
    error "Syntax errors found"
fi

# 4. Execute tests (if available)
echo "🧪 Executing tests..."

if [ -f "vendor/bin/phpunit" ]; then
    # Use CI test suite for faster release preparation
    if composer test:ci --no-coverage --stop-on-failure > /dev/null 2>&1; then
        info "CI tests passed"
    else
        error "CI tests failed"
    fi
elif [ -f "phpunit.phar" ]; then
    if php phpunit.phar --no-coverage --stop-on-failure > /dev/null 2>&1; then
        info "Tests passed"
    else
        error "Tests failed"
    fi
else
    warning "PHPUnit not found - tests not executed"
fi

# 5. Execute static analysis (if available)
echo "🔍 Static analysis..."

if [ -f "vendor/bin/phpstan" ]; then
    if ./vendor/bin/phpstan analyse --no-progress > /dev/null 2>&1; then
        info "Static analysis passed"
    else
        error "Static analysis failed"
    fi
else
    warning "PHPStan not found - static analysis not executed"
fi

# 6. Validate composer.json
echo "📦 Validating composer.json..."

# Check if composer.json is valid
if composer validate --no-check-all --no-check-lock > /dev/null 2>&1; then
    info "composer.json valid"
else
    error "composer.json invalid"
fi

# 7. Check for uncommitted changes (if it's a Git repository)
if [ -d ".git" ]; then
    echo "📝 Checking Git status..."

    if [ -n "$(git status --porcelain)" ]; then
        warning "There are uncommitted changes:"
        git status --porcelain
        echo ""
        read -p "Continue anyway? (y/N) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            error "Cancelled by user"
        fi
    else
        info "All files are committed"
    fi
fi

# 8. Execute custom validation
echo "🎯 Executing comprehensive validation..."

if [ -f "scripts/validation/validate_all.sh" ]; then
    if scripts/validation/validate_all.sh > /dev/null 2>&1; then
        info "Comprehensive validation passed"
    else
        error "Comprehensive validation failed - fix issues before continuing"
    fi
elif [ -f "scripts/validate_project.php" ]; then
    if php scripts/validate_project.php > /dev/null 2>&1; then
        info "Custom validation passed"
    else
        error "Custom validation failed"
    fi
else
    warning "Validation scripts not found"
fi

# 9. Clean temporary files
echo "🧹 Cleaning temporary files..."

# Remove development cache
if [ -d ".phpunit.cache" ]; then
    rm -rf .phpunit.cache
    info "PHPUnit cache removed"
fi

if [ -f ".phpunit.result.cache" ]; then
    rm -f .phpunit.result.cache
    info "PHPUnit result cache removed"
fi

if [ -d ".phpstan.cache" ]; then
    rm -rf .phpstan.cache
    info "PHPStan cache removed"
fi

# Clean development logs
if [ -d "logs" ]; then
    find logs -name "*.log" -type f -delete 2>/dev/null || true
    info "Development logs cleaned"
fi

# 10. Analyze project size
echo "📊 Project size analysis..."

project_size=$(du -sh . 2>/dev/null | cut -f1)
info "Total project size: $project_size"

# Check for large files
echo "Files larger than 1MB:"
find . -type f -size +1M -not -path "./vendor/*" -not -path "./.git/*" 2>/dev/null | head -10 || true

# 11. Final report
echo ""
echo "🎉 PREPARATION COMPLETED!"
echo "========================"
echo ""
echo "✅ Project validated and ready for publication"
echo ""
echo "📋 Next steps:"
echo "   1. Review changes one last time"
echo "   2. Make final commit (if necessary)"
echo "   3. Create version tag: git tag -a v$VERSION -m 'Release v$VERSION'"
echo "   4. Push to repository: git push origin main --tags"
echo "   5. Publish to Packagist"
echo ""
echo "🔗 Useful links:"
echo "   - Repository: https://github.com/PivotPHP/pivotphp-core"
echo "   - Packagist: https://packagist.org"
echo "   - Documentation: https://pivotphp.com"
echo ""

# 12. Offer to execute useful commands
read -p "Do you want to execute 'composer validate' now? (y/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    composer validate
fi

read -p "Do you want to see a preview of what will be included in the package? (y/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Files that will be included in the package:"
    if [ -d ".git" ]; then
        git ls-files 2>/dev/null || find . -type f -not -path "./vendor/*" -not -path "./.git/*" -not -path "./node_modules/*"
    else
        find . -type f -not -path "./vendor/*" -not -path "./.git/*" -not -path "./node_modules/*"
    fi
fi

echo ""
success "🚀 PivotPHP v$VERSION is ready for the world!"
echo ""