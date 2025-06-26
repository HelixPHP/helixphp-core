#!/bin/bash

# Script para testar funcionalidades de streaming do Express-PHP

echo "🚀 Testando funcionalidades de streaming do Express-PHP"
echo "=================================================="

# Verificar se o PHP está instalado
if ! command -v php &> /dev/null; then
    echo "❌ PHP não encontrado. Por favor, instale o PHP."
    exit 1
fi

# Ir para o diretório do projeto
cd "$(dirname "$0")/.."

echo "📁 Diretório do projeto: $(pwd)"

# Verificar se o autoload existe
if [ ! -f "vendor/autoload.php" ]; then
    echo "❌ Autoload não encontrado. Execute 'composer install' primeiro."
    exit 1
fi

echo ""
echo "🔧 Iniciando servidor PHP em modo background..."

# Iniciar o servidor PHP com o exemplo de streaming
php -S localhost:8000 examples/example_streaming.php &
SERVER_PID=$!

# Aguardar o servidor inicializar
sleep 2

# Função para limpar ao sair
cleanup() {
    echo ""
    echo "🛑 Parando servidor..."
    kill $SERVER_PID 2>/dev/null
    exit 0
}

# Configurar trap para cleanup
trap cleanup EXIT INT TERM

echo "✅ Servidor iniciado com PID: $SERVER_PID"
echo ""
echo "🌐 Servidor rodando em: http://localhost:8000"
echo ""
echo "📊 Testando endpoints de streaming..."
echo ""

# Função para testar endpoint
test_endpoint() {
    local url="$1"
    local description="$2"
    local timeout="${3:-10}"

    echo "🔍 Testando: $description"
    echo "   URL: $url"

    # Testar se o endpoint responde
    if curl -s --max-time $timeout "$url" > /dev/null; then
        echo "   ✅ Endpoint funcionando"
    else
        echo "   ❌ Endpoint com problemas"
    fi
    echo ""
}

# Testar endpoints básicos
test_endpoint "http://localhost:8000/" "Página principal" 5
test_endpoint "http://localhost:8000/stream/text" "Streaming de texto" 15
test_endpoint "http://localhost:8000/stream/json" "Streaming de JSON" 10
test_endpoint "http://localhost:8000/stream/custom-buffer" "Streaming com buffer customizado" 15

echo "📋 Testando streaming com curl..."
echo ""

# Teste detalhado do streaming de texto
echo "🔤 Teste de streaming de texto (primeiros 5 chunks):"
echo "   Comando: curl -N http://localhost:8000/stream/text | head -5"
curl -N http://localhost:8000/stream/text 2>/dev/null | head -5
echo ""

# Teste do streaming de JSON
echo "📄 Teste de streaming JSON (início do stream):"
echo "   Comando: curl -N http://localhost:8000/stream/json | head -3"
curl -N http://localhost:8000/stream/json 2>/dev/null | head -3
echo ""

# Teste Server-Sent Events
echo "📡 Teste de Server-Sent Events (primeiros 10 eventos):"
echo "   Comando: curl -N http://localhost:8000/stream/events | head -10"
curl -N http://localhost:8000/stream/events 2>/dev/null | head -10
echo ""

# Verificar se arquivo de exemplo existe para download
echo "📁 Testando streaming de arquivo..."
if curl -s --head "http://localhost:8000/stream/file" | grep -q "200 OK"; then
    echo "   ✅ Endpoint de arquivo funcionando"
    echo "   📊 Tamanho do arquivo:"
    curl -s --head "http://localhost:8000/stream/file" | grep -i content-length
else
    echo "   ⚠️  Arquivo de exemplo pode não existir ainda"
fi
echo ""

echo "🧪 Executando testes unitários para streaming..."
echo ""

# Executar testes específicos de streaming se existirem
if [ -f "tests/Services/ResponseStreamingTest.php" ]; then
    echo "▶️  Executando ResponseStreamingTest..."
    ./vendor/bin/phpunit tests/Services/ResponseStreamingTest.php --colors=always
    echo ""
else
    echo "⚠️  Testes de streaming não encontrados"
fi

echo "📈 Testando performance básica..."
echo ""

# Teste simples de performance
echo "⏱️  Medindo tempo de resposta do streaming:"
time curl -s http://localhost:8000/stream/text > /dev/null
echo ""

echo "🔧 Informações do ambiente:"
echo "   PHP Version: $(php -v | head -1)"
echo "   Memory Limit: $(php -r 'echo ini_get("memory_limit");')"
echo "   Max Execution Time: $(php -r 'echo ini_get("max_execution_time");')"
echo "   Output Buffering: $(php -r 'echo ini_get("output_buffering");')"
echo ""

echo "📱 Para testar manualmente:"
echo "   • Abra http://localhost:8000 no navegador"
echo "   • Clique nos links para testar diferentes tipos de streaming"
echo "   • Use http://localhost:8000/stream/test-sse para testar Server-Sent Events"
echo ""

echo "🔍 Para monitorar logs em tempo real (em outro terminal):"
echo "   tail -f /var/log/apache2/error.log  # ou o arquivo de log do seu servidor"
echo ""

echo "💡 Exemplos de uso com curl:"
echo "   # Streaming de texto:"
echo "   curl -N http://localhost:8000/stream/text"
echo ""
echo "   # Server-Sent Events:"
echo "   curl -N -H 'Accept: text/event-stream' http://localhost:8000/stream/events"
echo ""
echo "   # Download de arquivo:"
echo "   curl -O http://localhost:8000/stream/file"
echo ""

echo "✨ Teste concluído! Servidor continua rodando..."
echo "   Pressione Ctrl+C para parar o servidor"
echo ""

# Manter o script rodando até o usuário parar
wait
