#!/bin/bash

# Script de preparação para publicação do PivotPHP v1.0.0
# Este script limpa, valida e prepara o projeto para release

set -e

# Cores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m'

title() { echo -e "${PURPLE}🚀 $1${NC}"; }
info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
success() { echo -e "${GREEN}✅ $1${NC}"; }
warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
error() { echo -e "${RED}❌ $1${NC}"; exit 1; }

title "PivotPHP v1.0.0 - Release Preparation"
echo ""

# Verificar se estamos na raiz do projeto
if [ ! -f "composer.json" ]; then
    error "Execute este script na raiz do projeto PivotPHP"
fi

# 1. Verificar se há arquivos sensíveis
echo "🔍 Verificando arquivos sensíveis..."

if [ -f ".env" ]; then
    warning "Arquivo .env encontrado - certifique-se de que está no .gitignore"
fi

if [ -d "vendor" ]; then
    warning "Diretório vendor/ encontrado - será ignorado na publicação"
fi

if [ -f "composer.lock" ]; then
    info "composer.lock encontrado - normal para aplicações, opcional para bibliotecas"
fi

# 2. Validar estrutura básica
echo "📁 Validando estrutura do projeto..."

required_files=("composer.json" "README.md" "LICENSE")
for file in "${required_files[@]}"; do
    if [ -f "$file" ]; then
        info "Arquivo $file presente"
    else
        error "Arquivo obrigatório $file não encontrado"
    fi
done

required_dirs=("src" "docs")
for dir in "${required_dirs[@]}"; do
    if [ -d "$dir" ]; then
        info "Diretório $dir presente"
    else
        error "Diretório obrigatório $dir não encontrado"
    fi
done

# 3. Verificar sintaxe PHP
echo "🔧 Verificando sintaxe PHP..."

find src -name "*.php" -exec php -l {} \; > /dev/null
if [ $? -eq 0 ]; then
    info "Sintaxe PHP válida em todos os arquivos"
else
    error "Erros de sintaxe encontrados"
fi

# 4. Executar testes (se disponível)
echo "🧪 Executando testes..."

if [ -f "vendor/bin/phpunit" ]; then
    ./vendor/bin/phpunit --no-coverage --stop-on-failure
    info "Testes passaram"
elif [ -f "phpunit.phar" ]; then
    php phpunit.phar --no-coverage --stop-on-failure
    info "Testes passaram"
else
    warning "PHPUnit não encontrado - testes não executados"
fi

# 5. Executar análise estática (se disponível)
echo "🔍 Análise estática..."

if [ -f "vendor/bin/phpstan" ]; then
    ./vendor/bin/phpstan analyse --no-progress
    info "Análise estática passou"
else
    warning "PHPStan não encontrado - análise estática não executada"
fi

# 6. Verificar composer.json
echo "📦 Validando composer.json..."

# Verificar se composer.json é válido
composer validate --no-check-all --no-check-lock
if [ $? -eq 0 ]; then
    info "composer.json válido"
else
    error "composer.json inválido"
fi

# 7. Verificar se há mudanças não commitadas (se for um repositório Git)
if [ -d ".git" ]; then
    echo "📝 Verificando status do Git..."

    if [ -n "$(git status --porcelain)" ]; then
        warning "Há mudanças não commitadas:"
        git status --porcelain
        echo ""
        read -p "Continuar mesmo assim? (y/N) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            error "Cancelado pelo usuário"
        fi
    else
        info "Todos os arquivos estão commitados"
    fi
fi

# 8. Executar validação personalizada
echo "🎯 Executando validação completa..."

if [ -f "scripts/validate_all.sh" ]; then
    scripts/validate_all.sh
    if [ $? -eq 0 ]; then
        info "Validação completa passou"
    else
        error "Validação completa falhou - corrija os problemas antes de continuar"
    fi
elif [ -f "scripts/validate_project.php" ]; then
    php scripts/validate_project.php
    if [ $? -eq 0 ]; then
        info "Validação personalizada passou"
    else
        error "Validação personalizada falhou"
    fi
else
    warning "Scripts de validação não encontrados"
fi

# 9. Limpar arquivos temporários
echo "🧹 Limpando arquivos temporários..."

# Remover cache de desenvolvimento
if [ -d ".phpunit.cache" ]; then
    rm -rf .phpunit.cache
    info "Cache do PHPUnit removido"
fi

if [ -f ".phpunit.result.cache" ]; then
    rm -f .phpunit.result.cache
    info "Cache de resultados do PHPUnit removido"
fi

if [ -d ".phpstan.cache" ]; then
    rm -rf .phpstan.cache
    info "Cache do PHPStan removido"
fi

# Limpar logs de desenvolvimento
if [ -d "logs" ]; then
    find logs -name "*.log" -type f -delete 2>/dev/null || true
    info "Logs de desenvolvimento limpos"
fi

# 10. Verificar tamanho do projeto
echo "📊 Análise do tamanho do projeto..."

project_size=$(du -sh . 2>/dev/null | cut -f1)
info "Tamanho total do projeto: $project_size"

# Verificar arquivos grandes
echo "Arquivos maiores que 1MB:"
find . -type f -size +1M -not -path "./vendor/*" -not -path "./.git/*" 2>/dev/null | head -10

# 11. Relatório final
echo ""
echo "🎉 PREPARAÇÃO CONCLUÍDA!"
echo "========================"
echo ""
echo "✅ Projeto validado e pronto para publicação"
echo ""
echo "📋 Próximos passos:"
echo "   1. Revisar as mudanças uma última vez"
echo "   2. Fazer commit final (se necessário)"
echo "   3. Criar tag de versão: git tag -a v1.0.0 -m 'Release v1.0.0'"
echo "   4. Push para o repositório: git push origin main --tags"
echo "   5. Publicar no Packagist"
echo ""
echo "🔗 Links úteis:"
echo "   - Repositório: https://github.com/CAFernandes/pivotphp-core"
echo "   - Packagist: https://packagist.org"
echo "   - Guia de publicação: PUBLISHING_GUIDE.md"
echo ""

# 12. Oferece executar comandos úteis
read -p "Deseja executar 'composer validate' agora? (y/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    composer validate
fi

read -p "Deseja ver um preview do que será incluído no package? (y/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Arquivos que serão incluídos no package:"
    git ls-files 2>/dev/null || find . -type f -not -path "./vendor/*" -not -path "./.git/*" -not -path "./node_modules/*"
fi

echo ""
echo "🚀 PivotPHP está pronto para o mundo!"
