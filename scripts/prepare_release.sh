#!/bin/bash

# Script de preparação para publicação do Express PHP
# Este script limpa e valida o projeto antes da publicação

set -e

echo "🧹 Preparando Express PHP para publicação..."
echo "=============================================="

# Função para exibir status
status() {
    echo "✅ $1"
}

warning() {
    echo "⚠️  $1"
}

error() {
    echo "❌ $1"
    exit 1
}

# Verificar se estamos na raiz do projeto
if [ ! -f "composer.json" ]; then
    error "Este script deve ser executado na raiz do projeto"
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
    status "composer.lock encontrado - normal para aplicações, opcional para bibliotecas"
fi

# 2. Validar estrutura básica
echo "📁 Validando estrutura do projeto..."

required_files=("composer.json" "README.md" "LICENSE")
for file in "${required_files[@]}"; do
    if [ -f "$file" ]; then
        status "Arquivo $file presente"
    else
        error "Arquivo obrigatório $file não encontrado"
    fi
done

required_dirs=("SRC" "docs")
for dir in "${required_dirs[@]}"; do
    if [ -d "$dir" ]; then
        status "Diretório $dir presente"
    else
        error "Diretório obrigatório $dir não encontrado"
    fi
done

# 3. Verificar sintaxe PHP
echo "🔧 Verificando sintaxe PHP..."

find SRC -name "*.php" -exec php -l {} \; > /dev/null
if [ $? -eq 0 ]; then
    status "Sintaxe PHP válida em todos os arquivos"
else
    error "Erros de sintaxe encontrados"
fi

# 4. Executar testes (se disponível)
echo "🧪 Executando testes..."

if [ -f "vendor/bin/phpunit" ]; then
    ./vendor/bin/phpunit --no-coverage --stop-on-failure
    status "Testes passaram"
elif [ -f "phpunit.phar" ]; then
    php phpunit.phar --no-coverage --stop-on-failure
    status "Testes passaram"
else
    warning "PHPUnit não encontrado - testes não executados"
fi

# 5. Executar análise estática (se disponível)
echo "🔍 Análise estática..."

if [ -f "vendor/bin/phpstan" ]; then
    ./vendor/bin/phpstan analyse --no-progress
    status "Análise estática passou"
else
    warning "PHPStan não encontrado - análise estática não executada"
fi

# 6. Verificar composer.json
echo "📦 Validando composer.json..."

# Verificar se composer.json é válido
composer validate --no-check-all --no-check-lock
if [ $? -eq 0 ]; then
    status "composer.json válido"
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
        status "Todos os arquivos estão commitados"
    fi
fi

# 8. Executar validação personalizada
echo "🎯 Executando validação personalizada..."

if [ -f "scripts/validate_project.php" ]; then
    php scripts/validate_project.php
    if [ $? -eq 0 ]; then
        status "Validação personalizada passou"
    else
        error "Validação personalizada falhou"
    fi
else
    warning "Script de validação personalizada não encontrado"
fi

# 9. Limpar arquivos temporários
echo "🧹 Limpando arquivos temporários..."

# Remover cache de desenvolvimento
if [ -d ".phpunit.cache" ]; then
    rm -rf .phpunit.cache
    status "Cache do PHPUnit removido"
fi

if [ -f ".phpunit.result.cache" ]; then
    rm -f .phpunit.result.cache
    status "Cache de resultados do PHPUnit removido"
fi

if [ -d ".phpstan.cache" ]; then
    rm -rf .phpstan.cache
    status "Cache do PHPStan removido"
fi

# Limpar logs de desenvolvimento
if [ -d "logs" ]; then
    find logs -name "*.log" -type f -delete 2>/dev/null || true
    status "Logs de desenvolvimento limpos"
fi

# 10. Verificar tamanho do projeto
echo "📊 Análise do tamanho do projeto..."

project_size=$(du -sh . 2>/dev/null | cut -f1)
status "Tamanho total do projeto: $project_size"

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
echo "   - Repositório: https://github.com/CAFernandes/express-php"
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
echo "🚀 Express PHP está pronto para o mundo!"
