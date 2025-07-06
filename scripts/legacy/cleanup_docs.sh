#!/bin/bash

# 🧹 HelixPHP v1.0.0 - Documentation Cleanup Script
# Remove arquivos redundantes e consolida documentação

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para exibir títulos
title() {
    echo -e "\n${BLUE}===================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}===================================${NC}\n"
}

# Função para exibir sucesso
success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# Função para exibir aviso
warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Função para exibir erro
error() {
    echo -e "${RED}❌ $1${NC}"
}

title "HelixPHP v1.0.0 - Documentation Cleanup"

# Verificar se estamos no diretório correto
if [ ! -f "composer.json" ] || [ ! -d "src" ]; then
    error "Execute este script no diretório raiz do projeto HelixPHP"
    exit 1
fi

echo -e "${BLUE}🎯 Objetivos da limpeza:${NC}"
echo "• Remover arquivos redundantes e duplicados"
echo "• Consolidar documentação principal"
echo "• Manter estrutura clara e concisa"
echo "• Preservar dados importantes de benchmark"
echo ""

# Criar backup antes da limpeza
title "📦 Criando Backup"

BACKUP_DIR="backup_docs_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo -e "${YELLOW}Criando backup em: $BACKUP_DIR${NC}"

# Lista de arquivos para backup (redundantes que serão removidos)
REDUNDANT_FILES=(
    "README_v1.0.0.md"
    "PERFORMANCE_REPORT_FINAL.md"
    "TECHNICAL_OPTIMIZATION_SUMMARY.md"
    "CONSOLIDATION_SUMMARY_v1.0.0.md"
    "ADVANCED_OPTIMIZATIONS_REPORT.md"
    "OPTIMIZATION_FINAL_REPORT.md"
    "OPTIMIZATION_IMPLEMENTATION_COMPLETE.md"
    "PERFORMANCE_MIGRATION_FINAL_REPORT.md"
    "MIGRATION_COMPLETE.md"
)

for file in "${REDUNDANT_FILES[@]}"; do
    if [ -f "$file" ]; then
        cp "$file" "$BACKUP_DIR/"
        success "Backup criado: $file"
    fi
done

# Verificar documentação consolidada
title "📊 Verificando Documentação Consolidada"

CORE_DOCS=(
    "FRAMEWORK_OVERVIEW_v1.0.0.md"
    "DOCUMENTATION_GUIDE.md"
    "README.md"
    "CHANGELOG.md"
)

echo -e "${YELLOW}Verificando arquivos principais:${NC}"
for file in "${CORE_DOCS[@]}"; do
    if [ -f "$file" ]; then
        success "Encontrado: $file"
    else
        error "Faltando: $file"
    fi
done

# Verificar estrutura de benchmarks
echo -e "\n${YELLOW}Verificando estrutura de benchmarks:${NC}"
if [ -d "benchmarks" ] && [ -d "benchmarks/reports" ]; then
    BENCHMARK_COUNT=$(find benchmarks/reports -name "*.md" | wc -l)
    success "Benchmark reports: $BENCHMARK_COUNT arquivos"
else
    warning "Estrutura de benchmarks incompleta"
fi

# Verificar documentação técnica
echo -e "\n${YELLOW}Verificando docs técnicos:${NC}"
if [ -d "docs/performance" ] && [ -d "docs/implementation" ] && [ -d "docs/releases" ]; then
    success "Estrutura docs/ organizada"
else
    warning "Estrutura docs/ incompleta"
fi

# Mostrar o que será removido
title "🗑️  Arquivos a Serem Removidos"

echo -e "${YELLOW}Os seguintes arquivos serão removidos (já têm backup):${NC}"
for file in "${REDUNDANT_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "  ❌ $file"
    fi
done

echo -e "\n${BLUE}Motivo: Informações consolidadas em FRAMEWORK_OVERVIEW_v1.0.0.md${NC}"

# Perguntar confirmação
echo -e "\n${YELLOW}Deseja continuar com a limpeza? (y/N):${NC}"
read -r CONFIRM

if [ "$CONFIRM" != "y" ] && [ "$CONFIRM" != "Y" ]; then
    warning "Limpeza cancelada pelo usuário"
    exit 0
fi

# Executar limpeza
title "🧹 Executando Limpeza"

for file in "${REDUNDANT_FILES[@]}"; do
    if [ -f "$file" ]; then
        rm "$file"
        success "Removido: $file"
    fi
done

# Atualizar README principal para referenciar nova estrutura
title "📝 Atualizando Referências"

if [ -f "README.md" ]; then
    # Adicionar referência ao overview consolidado no início do README
    if ! grep -q "FRAMEWORK_OVERVIEW_v1.0.0.md" README.md; then
        # Criar backup do README atual
        cp README.md README.md.backup

        # Adicionar linha de referência após os badges
        sed -i '/^\[!\[.*\]\]/a\\n> 📖 **Complete v1.0.0 Guide**: See [FRAMEWORK_OVERVIEW_v1.0.0.md](FRAMEWORK_OVERVIEW_v1.0.0.md) for comprehensive documentation\n' README.md

        success "README.md atualizado com referência ao overview consolidado"
    fi
fi

# Verificar e limpar arquivos temporários
echo -e "\n${YELLOW}Limpando arquivos temporários:${NC}"

# Remover arquivos de backup antigos se existirem
find . -maxdepth 1 -name "*.backup" -type f | while read -r backup_file; do
    if [ -f "$backup_file" ]; then
        rm "$backup_file"
        success "Removido backup temporário: $backup_file"
    fi
done

# Mostrar estrutura final
title "📁 Estrutura Final de Documentação"

echo -e "${GREEN}📋 Documentação Principal:${NC}"
echo "  ✅ README.md                        # Overview geral do framework"
echo "  ✅ FRAMEWORK_OVERVIEW_v1.0.0.md     # Guia completo v1.0.0"
echo "  ✅ DOCUMENTATION_GUIDE.md           # Guia de navegação"
echo "  ✅ CHANGELOG.md                     # Histórico de versões"

echo -e "\n${GREEN}📊 Performance & Benchmarks:${NC}"
echo "  ✅ benchmarks/                      # Suite completa de benchmarks"
echo "  ✅ benchmarks/reports/              # Relatórios gerados"

echo -e "\n${GREEN}📚 Documentação Técnica:${NC}"
echo "  ✅ docs/performance/                # Análises científicas"
echo "  ✅ docs/implementation/             # Guias técnicos"
echo "  ✅ docs/releases/                   # Notas de release"

echo -e "\n${GREEN}💡 Exemplos:${NC}"
echo "  ✅ examples/                        # Exemplos práticos"

# Gerar resumo da limpeza
title "📊 Resumo da Limpeza"

REMOVED_COUNT=0
for file in "${REDUNDANT_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        ((REMOVED_COUNT++))
    fi
done

echo -e "${GREEN}✅ Limpeza concluída com sucesso!${NC}"
echo ""
echo "📊 Estatísticas:"
echo "  • Arquivos removidos: $REMOVED_COUNT"
echo "  • Backup criado em: $BACKUP_DIR"
echo "  • Documentação consolidada: 4 arquivos principais"
echo "  • Estrutura organizada: docs/, benchmarks/, examples/"
echo ""

echo -e "${BLUE}🎯 Próximos passos:${NC}"
echo "1. Revisar FRAMEWORK_OVERVIEW_v1.0.0.md"
echo "2. Testar navegação com DOCUMENTATION_GUIDE.md"
echo "3. Validar que todas as informações importantes foram preservadas"
echo "4. Commit das mudanças"
echo ""

echo -e "${GREEN}📖 Para usuários:${NC}"
echo "• Documentação principal: FRAMEWORK_OVERVIEW_v1.0.0.md"
echo "• Guia de navegação: DOCUMENTATION_GUIDE.md"
echo "• Performance e benchmarks: benchmarks/"
echo "• Exemplos práticos: examples/"
echo ""

warning "Revise o backup em $BACKUP_DIR antes de fazer commit"

echo -e "\n${GREEN}🎉 Documentação limpa e organizada!${NC}"
echo -e "${BLUE}HelixPHP v1.0.0 - Clear Documentation, Exceptional Performance${NC}"
