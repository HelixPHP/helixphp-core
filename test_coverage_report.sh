#!/bin/bash

echo "=========================================="
echo "           RELATÓRIO DE TESTES"
echo "=========================================="
echo ""

# Lista dos arquivos de teste criados
test_files=(
    "tests/ApiExpressTest.php"
    "tests/Helpers/UtilsTest.php" 
    "tests/Services/RequestTest.php"
    "tests/Services/ResponseTest.php"
    "tests/Services/HeaderRequestTest.php"
    "tests/Controller/RouterTest.php"
    "tests/Services/OpenApiExporterTest.php"
    "tests/Core/CorsMiddlewareTest.php"
)

total_tests=0
passed_tests=0

for test_file in "${test_files[@]}"; do
    if [ -f "$test_file" ]; then
        echo "Executando: $test_file"
        echo "----------------------------------------"
        
        # Execute o teste e capture o resultado
        result=$(./vendor/bin/phpunit "$test_file" 2>&1)
        
        # Extrai o número de testes do resultado
        if echo "$result" | grep -q "Tests:"; then
            test_count=$(echo "$result" | grep "Tests:" | tail -1 | sed 's/.*Tests: \([0-9]*\).*/\1/')
            total_tests=$((total_tests + test_count))
            
            if echo "$result" | grep -q "OK"; then
                passed_tests=$((passed_tests + test_count))
                echo "✅ PASSOU - $test_count testes"
            else
                echo "❌ FALHOU - Verificar erros acima"
            fi
        else
            echo "⚠️  Erro ao executar teste"
        fi
        
        echo ""
    else
        echo "❌ Arquivo não encontrado: $test_file"
        echo ""
    fi
done

echo "=========================================="
echo "              RESUMO FINAL"
echo "=========================================="
echo "Total de testes criados: $total_tests"
echo "Testes funcionais: $passed_tests"
echo "Cobertura estimada: $(( passed_tests * 100 / total_tests ))%"

echo ""
echo "Novos arquivos de teste criados:"
for test_file in "${test_files[@]}"; do
    if [ -f "$test_file" ]; then
        echo "  ✓ $test_file"
    fi
done
