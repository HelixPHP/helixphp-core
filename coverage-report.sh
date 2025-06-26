#!/bin/bash

echo "=========================================="
echo "    RELATÓRIO DE COBERTURA DE TESTES"
echo "=========================================="
echo ""

# Limpar diretórios de relatório
rm -rf reports/coverage
mkdir -p reports/coverage

echo "📊 Executando testes com cobertura..."
echo ""

# Executar testes com cobertura
./vendor/bin/phpunit --coverage-text --colors=never > reports/coverage-text.txt 2>&1
./vendor/bin/phpunit --coverage-html reports/coverage > reports/coverage-html.log 2>&1

# Mostrar resumo dos testes
echo "📋 RESUMO DOS TESTES:"
echo "=========================================="

# Contar arquivos de teste
test_files=$(find tests -name "*.php" | wc -l)
echo "Total de arquivos de teste: $test_files"

# Executar testes por categoria
echo ""
echo "🧪 RESULTADOS POR CATEGORIA:"
echo "----------------------------------------"

categories=("ApiExpressTest" "Helpers" "Services" "Controller" "Core" "Security")

for category in "${categories[@]}"; do
    echo "📁 $category:"
    if [ -d "tests/$category" ]; then
        result=$(./vendor/bin/phpunit tests/$category 2>&1 | tail -1)
        echo "   $result"
    elif [ -f "tests/${category}Test.php" ]; then
        result=$(./vendor/bin/phpunit tests/${category}Test.php 2>&1 | tail -1)
        echo "   $result"
    else
        echo "   ❌ Não encontrado"
    fi
done

echo ""
echo "📈 COBERTURA DE CÓDIGO:"
echo "=========================================="

# Extrair informações de cobertura do relatório
if [ -f "reports/coverage-text.txt" ]; then
    grep -A 50 "Code Coverage Report" reports/coverage-text.txt | head -30
else
    echo "❌ Relatório de cobertura não gerado"
fi

echo ""
echo "📂 ARQUIVOS DE RELATÓRIO GERADOS:"
echo "=========================================="
echo "  📄 Relatório texto: reports/coverage-text.txt"
echo "  🌐 Relatório HTML: reports/coverage/index.html"

if [ -f "reports/coverage/index.html" ]; then
    echo "  ✅ Relatório HTML disponível em: file://$(pwd)/reports/coverage/index.html"
else
    echo "  ❌ Relatório HTML não foi gerado"
fi

echo ""
echo "🔍 ANÁLISE DE QUALIDADE:"
echo "=========================================="

# Contar linhas de código
src_lines=$(find src -name "*.php" -exec wc -l {} + | tail -1 | awk '{print $1}')
test_lines=$(find tests -name "*.php" -exec wc -l {} + | tail -1 | awk '{print $1}')

echo "  📊 Linhas de código fonte: $src_lines"
echo "  🧪 Linhas de código de teste: $test_lines"

if [ $src_lines -gt 0 ]; then
    ratio=$(echo "scale=2; $test_lines / $src_lines" | bc -l 2>/dev/null || echo "N/A")
    echo "  📈 Razão teste/código: $ratio"
fi

echo ""
echo "✅ Relatório de cobertura concluído!"
echo "=========================================="
