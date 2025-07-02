#!/bin/bash

# Express PHP - Correção de linhas longas PSR-12
# Corrige automaticamente linhas que excedem 120 caracteres

set -e

echo "🔧 Corrigindo linhas longas para PSR-12..."

# Cores para output
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

print_warning() {
    echo -e "${YELLOW}[⚠]${NC} $1"
}

# Arquivos com problemas conhecidos
FILES_TO_FIX=(
    "src/Middleware/Security/CorsMiddleware.php"
    "src/Middleware/Security/AuthMiddleware.php"
    "src/Middleware/MiddlewareStack.php"
)

for file in "${FILES_TO_FIX[@]}"; do
    if [ -f "$file" ]; then
        print_status "Processando $file..."

        # Backup do arquivo original
        cp "$file" "$file.backup"

        # Aplica correções específicas usando sed
        case "$file" in
            "src/Middleware/Security/CorsMiddleware.php")
                # Quebra linha 296 que tem 128 caracteres
                sed -i '296s/.*/:        throw new \\InvalidArgumentException(\n            "Invalid CORS origin format: $origin"\n        );/' "$file"
                ;;
            "src/Middleware/Security/AuthMiddleware.php")
                # Quebra linha 147 que tem 151 caracteres
                sed -i '147s/.*/            throw new \\UnauthorizedHttpException(\n                "Authentication failed: " . $e->getMessage()\n            );/' "$file"
                ;;
            "src/Middleware/MiddlewareStack.php")
                # Quebra linha 224 que tem 124 caracteres
                sed -i '224s/.*/                "Middleware {$this->middlewares[$key]} is not callable"\n            );/' "$file"
                ;;
        esac

        print_success "Corrigido $file"
    fi
done

# Verifica se as correções funcionaram
print_status "Verificando se as correções resolveram os problemas..."

if composer cs:check > /dev/null 2>&1; then
    print_success "Todas as violações PSR-12 foram corrigidas!"

    # Remove backups
    for file in "${FILES_TO_FIX[@]}"; do
        if [ -f "$file.backup" ]; then
            rm "$file.backup"
        fi
    done
else
    print_warning "Ainda existem algumas violações PSR-12:"
    composer cs:check

    print_status "Mantendo backups dos arquivos originais (.backup)"
fi
