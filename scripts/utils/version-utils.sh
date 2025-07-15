#!/bin/bash

# PivotPHP Core - Version Detection Utilities
# Shared functions for automatic version detection across all scripts

# Color definitions
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# Logging functions
info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
success() { echo -e "${GREEN}✅ $1${NC}"; }
warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
error() { echo -e "${RED}❌ $1${NC}"; }

# Get project root directory (works from any script location)
get_project_root() {
    local current_dir="$PWD"
    
    # Try current directory first
    if [ -f "VERSION" ] && [ -f "composer.json" ]; then
        echo "$current_dir"
        return 0
    fi
    
    # Try parent directories
    local dir="$current_dir"
    while [ "$dir" != "/" ]; do
        if [ -f "$dir/VERSION" ] && [ -f "$dir/composer.json" ]; then
            echo "$dir"
            return 0
        fi
        dir=$(dirname "$dir")
    done
    
    # If called from scripts directory, try parent
    if [[ "$current_dir" == */scripts ]]; then
        local parent_dir=$(dirname "$current_dir")
        if [ -f "$parent_dir/VERSION" ] && [ -f "$parent_dir/composer.json" ]; then
            echo "$parent_dir"
            return 0
        fi
    fi
    
    error "Project root not found. Missing VERSION or composer.json file."
    return 1
}

# Get current version from VERSION file (REQUIRED)
get_current_version() {
    local project_root
    project_root=$(get_project_root) || return 1
    
    local version_file="$project_root/VERSION"
    
    if [ ! -f "$version_file" ]; then
        error "REQUIRED VERSION file not found at: $version_file"
        error "PivotPHP Core requires a VERSION file in the project root"
        return 1
    fi
    
    local version
    version=$(head -n1 "$version_file" | tr -d '[:space:]')
    
    if [ -z "$version" ]; then
        error "VERSION file is empty or invalid at: $version_file"
        error "VERSION file must contain a valid semantic version (X.Y.Z)"
        return 1
    fi
    
    # Validate semantic version format
    if [[ ! "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        error "Invalid version format in VERSION file: $version"
        error "Expected format: X.Y.Z (semantic versioning)"
        return 1
    fi
    
    echo "$version"
    return 0
}

# Get version from composer.json (fallback)
get_composer_version() {
    local project_root
    project_root=$(get_project_root) || return 1
    
    local composer_file="$project_root/composer.json"
    
    if [ ! -f "$composer_file" ]; then
        error "composer.json not found at: $composer_file"
        return 1
    fi
    
    local version
    version=$(grep '"version"' "$composer_file" | sed 's/.*"version": "\([^"]*\)".*/\1/' | head -n1)
    
    if [ -z "$version" ]; then
        warning "No version found in composer.json"
        return 1
    fi
    
    echo "$version"
    return 0
}

# Get version (VERSION file REQUIRED - no fallbacks)
get_version() {
    # Only use VERSION file - no fallbacks
    get_current_version
}

# Check if we're in the correct project directory
validate_project_context() {
    local project_root
    project_root=$(get_project_root) || return 1
    
    # Check for PivotPHP Core specific files
    local required_files=(
        "composer.json"
        "VERSION"
        "src/Core/Application.php"
    )
    
    for file in "${required_files[@]}"; do
        if [ ! -f "$project_root/$file" ]; then
            error "Required file not found: $file"
            error "Are you in the PivotPHP Core project directory?"
            return 1
        fi
    done
    
    # Verify it's actually PivotPHP Core by checking composer.json
    if ! grep -q '"pivotphp/core"' "$project_root/composer.json" 2>/dev/null; then
        error "This doesn't appear to be the PivotPHP Core project"
        return 1
    fi
    
    return 0
}

# Get project information
get_project_info() {
    local project_root
    project_root=$(get_project_root) || return 1
    
    local version
    version=$(get_version) || return 1
    
    echo "PROJECT_ROOT=$project_root"
    echo "VERSION=$version"
    echo "COMPOSER_FILE=$project_root/composer.json"
    echo "VERSION_FILE=$project_root/VERSION"
}

# Change to project root directory
cd_to_project_root() {
    local project_root
    project_root=$(get_project_root) || return 1
    
    if [ "$PWD" != "$project_root" ]; then
        info "Changing to project root: $project_root"
        cd "$project_root" || {
            error "Failed to change to project root directory"
            return 1
        }
    fi
    
    return 0
}

# Print version info banner
print_version_banner() {
    local version
    version=$(get_version) || return 1
    
    echo "🚀 PivotPHP Core v$version"
    echo "=========================================="
    echo ""
}

# Note: Functions are available when script is sourced