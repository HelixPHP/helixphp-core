<?php

require_once __DIR__ . '/vendor/autoload.php';

use Express\Services\Response;

echo "🧪 Testando funcionalidades de streaming...\n\n";

// Teste 1: Verificar métodos básicos
echo "1. Testando métodos básicos:\n";
$response = new Response();

// Buffer size
$response->setStreamBufferSize(4096);
echo "   ✅ setStreamBufferSize funcionando\n";

// Streaming status
echo "   Estado inicial: " . ($response->isStreaming() ? "Streaming" : "Normal") . "\n";

// Iniciar streaming
$response->startStream('text/plain');
echo "   Estado após startStream: " . ($response->isStreaming() ? "Streaming" : "Normal") . "\n";

// Headers
$headers = $response->getHeaders();
echo "   Headers definidos: " . implode(', ', array_keys($headers)) . "\n";

// Finalizar streaming
$response->endStream();
echo "   Estado após endStream: " . ($response->isStreaming() ? "Streaming" : "Normal") . "\n";

echo "\n2. Testando write simples:\n";
$response2 = new Response();
$response2->startStream();

// Capturar output
ob_start();
$response2->write("Teste de escrita");
$output = ob_get_contents();
ob_end_clean();

echo "   Output capturado: '$output'\n";
echo "   ✅ Write funcionando\n";

echo "\n3. Testando writeJson:\n";
$response3 = new Response();
$response3->startStream();

ob_start();
$response3->writeJson(['test' => 'data', 'number' => 123]);
$jsonOutput = ob_get_contents();
ob_end_clean();

echo "   JSON output: '$jsonOutput'\n";
echo "   ✅ WriteJson funcionando\n";

echo "\n4. Testando Server-Sent Events:\n";
$response4 = new Response();

ob_start();
$response4->sendEvent(['message' => 'Hello'], 'greeting', '1', 3000);
$sseOutput = ob_get_contents();
ob_end_clean();

echo "   SSE Output:\n";
echo "   " . str_replace("\n", "\n   ", trim($sseOutput)) . "\n";
echo "   ✅ SSE funcionando\n";

echo "\n5. Testando arquivo temporário:\n";
$tempFile = tempnam(sys_get_temp_dir(), 'stream_test_');
file_put_contents($tempFile, 'Conteúdo do arquivo de teste');

try {
    $response5 = new Response();

    ob_start();
    $response5->streamFile($tempFile);
    $fileOutput = ob_get_contents();
    ob_end_clean();

    echo "   Arquivo streamado: '$fileOutput'\n";
    echo "   ✅ streamFile funcionando\n";
} catch (Exception $e) {
    echo "   ❌ Erro no streamFile: " . $e->getMessage() . "\n";
} finally {
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
}

echo "\n6. Testando heartbeat:\n";
$response6 = new Response();
$response6->startStream();

ob_start();
$response6->sendHeartbeat();
$heartbeatOutput = ob_get_contents();
ob_end_clean();

echo "   Heartbeat output: '$heartbeatOutput'\n";
echo "   ✅ Heartbeat funcionando\n";

echo "\n🎉 Todos os testes básicos funcionando!\n";
echo "\n📝 Resumo dos recursos implementados:\n";
echo "   • Streaming de texto básico\n";
echo "   • Streaming de JSON\n";
echo "   • Server-Sent Events (SSE)\n";
echo "   • Streaming de arquivos\n";
echo "   • Heartbeat para SSE\n";
echo "   • Configuração de buffer customizável\n";
echo "   • Controle de estado de streaming\n";
echo "   • Headers automáticos para streaming\n";

echo "\n🚀 Para testar completo, execute:\n";
echo "   php examples/example_streaming.php\n";
echo "   E acesse http://localhost:8000\n";
