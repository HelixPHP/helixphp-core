# Development Setup - Express PHP

This document provides information about the development environment setup for Express PHP.

## 📁 Project Structure

```
Express-PHP/
├── .gitignore          # Git ignore patterns
├── .editorconfig       # Editor configuration
├── composer.json       # Package configuration
├── phpunit.xml         # PHPUnit configuration
├── phpstan.neon        # PHPStan configuration
├── SRC/                # Source code (PSR-4: Express\)
├── tests/              # PHPUnit tests
├── examples/           # Usage examples
├── docs/               # Documentation
└── vendor/             # Composer dependencies (ignored)
```

## 🚫 .gitignore Overview

The `.gitignore` file is configured to ignore:

### Dependencies & Build
- `/vendor/` - Composer dependencies
- `composer.lock` - Lock file (include if you want reproducible builds)
- `node_modules/` - Node.js dependencies (if used)

### Development Tools
- `.vscode/`, `.idea/` - IDE configuration
- `.phpunit.cache/` - PHPUnit cache
- `.phpstan.cache/` - PHPStan cache
- Coverage reports and build artifacts

### Runtime Files
- `*.log` - Log files
- `/tmp/`, `/cache/` - Temporary and cache directories
- Session files

### Environment & Security
- `.env*` - Environment configuration files
- `*.key`, `*.pem` - Private keys and certificates
- Database files (`.sqlite`, `.db`)

### Operating System
- `.DS_Store` (macOS)
- `Thumbs.db` (Windows)
- Linux temporary files

## 🔧 Development Workflow

### Initial Setup
```bash
# Clone repository
git clone <repository-url>
cd Express-PHP

# Install dependencies
composer install

# Run tests
composer test
```

### Working with Git
```bash
# Check what will be committed
git status

# Stage specific files
git add SRC/ tests/ docs/

# Commit changes
git commit -m "feat: add new feature"

# Push changes
git push origin main
```

### Files to Track vs Ignore

#### ✅ Track These:
- Source code (`SRC/`)
- Tests (`tests/`)
- Documentation (`docs/`)
- Configuration files (`composer.json`, `phpunit.xml`)
- Examples (`examples/`)

#### ❌ Ignore These:
- Dependencies (`vendor/`)
- Cache files (`.phpunit.cache/`)
- Logs (`*.log`)
- Environment files (`.env`)
- IDE configuration (`.vscode/`, `.idea/`)

## 🧪 Testing Considerations

When writing tests, avoid:
- Creating temporary files in tracked directories
- Using absolute paths
- Depending on external services without mocking

Use these patterns:
```php
// Use temporary directory
$tempFile = sys_get_temp_dir() . '/test_' . uniqid();

// Clean up in tearDown()
public function tearDown(): void
{
    if (file_exists($this->tempFile)) {
        unlink($this->tempFile);
    }
}
```

## 📦 Distribution

When creating releases, these files are excluded via `composer.json`:
- `/test` - Legacy test directory
- `/tests` - PHPUnit tests
- `/examples` - Usage examples
- Development configuration files

## 🔐 Security Notes

Never commit:
- Database credentials
- API keys
- Private keys or certificates
- Environment-specific configuration

Use environment variables or `.env` files (which are ignored) for sensitive data.

## 📝 Editor Configuration

The `.editorconfig` file ensures consistent coding style across different editors:
- UTF-8 encoding
- LF line endings
- 4 spaces for PHP
- 2 spaces for JSON/YAML/Markdown
- Trim trailing whitespace

## 🤝 Contributing

When contributing:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests: `composer test`
5. Check code style: `composer run cs:check`
6. Submit a pull request

Make sure your changes don't add ignored files to version control!
