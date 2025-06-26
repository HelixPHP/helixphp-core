<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Express\ApiExpress;
use Express\Controller\Router;

$app = new ApiExpress();

// Exemplo 1: Streaming de texto simples
Router::get('/stream/text', function ($req, $res) {
    $res->startStream('text/plain; charset=utf-8');

    for ($i = 1; $i <= 10; $i++) {
        $res->write("Chunk {$i}\n");
        sleep(1); // Simula processamento
    }

    $res->endStream();
});

// Exemplo 2: Streaming de dados JSON
Router::get('/stream/json', function ($req, $res) {
    $res->startStream('application/json; charset=utf-8');

    $res->write('['); // Início do array JSON

    for ($i = 1; $i <= 5; $i++) {
        $data = [
            'id' => $i,
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => "Dados do chunk {$i}"
        ];

        if ($i > 1) {
            $res->write(','); // Separador entre objetos
        }

        $res->writeJson($data, true);
        sleep(1); // Simula processamento
    }

    $res->write(']'); // Fim do array JSON
    $res->endStream();
});

// Exemplo 3: Server-Sent Events (SSE)
Router::get('/stream/events', function ($req, $res) {
    $res->sendEvent('Conexão estabelecida', 'connect', '1');

    for ($i = 1; $i <= 10; $i++) {
        $eventData = [
            'counter' => $i,
            'timestamp' => time(),
            'random' => rand(1, 100)
        ];

        $res->sendEvent($eventData, 'update', (string)($i + 1));

        // Enviar heartbeat a cada 5 iterações
        if ($i % 5 === 0) {
            $res->sendHeartbeat();
        }

        sleep(1);
    }

    $res->sendEvent('Stream finalizado', 'disconnect');
    $res->endStream();
});

// Exemplo 4: Streaming de arquivo
Router::get('/stream/file', function ($req, $res) {
    $filePath = __DIR__ . '/large-file.txt';

    // Criar arquivo de exemplo se não existir
    if (!file_exists($filePath)) {
        $content = str_repeat("Esta é uma linha de exemplo de um arquivo grande.\n", 1000);
        file_put_contents($filePath, $content);
    }

    try {
        $headers = [
            'Content-Disposition' => 'attachment; filename="large-file.txt"'
        ];

        $res->streamFile($filePath, $headers);
    } catch (Exception $e) {
        $res->status(404)->json(['error' => $e->getMessage()]);
    }
});

// Exemplo 5: Streaming de dados de um processo
Router::get('/stream/process', function ($req, $res) {
    $command = 'ping -c 10 google.com';
    $process = popen($command, 'r');

    if (!$process) {
        $res->status(500)->json(['error' => 'Falha ao executar comando']);
        return;
    }

    $res->startStream('text/plain; charset=utf-8');

    while (!feof($process)) {
        $line = fgets($process);
        if ($line !== false) {
            $res->write($line, true);
        }
    }

    pclose($process);
    $res->endStream();
});

// Exemplo 6: Streaming de dados em tempo real com buffer personalizado
Router::get('/stream/custom-buffer', function ($req, $res) {
    $res->setStreamBufferSize(1024) // 1KB buffer
        ->startStream('text/plain; charset=utf-8');

    // Simular geração de dados em tempo real
    for ($i = 1; $i <= 20; $i++) {
        $data = str_repeat("Data chunk {$i} ", 50) . "\n";
        $res->write($data);

        usleep(500000); // 0.5 segundos
    }

    $res->endStream();
});

// Página HTML para testar Server-Sent Events
Router::get('/stream/test-sse', function ($req, $res) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <title>Test Server-Sent Events</title>
    <meta charset="utf-8">
</head>
<body>
    <h1>Server-Sent Events Test</h1>
    <div id="events"></div>

    <script>
        const eventSource = new EventSource("/stream/events");
        const eventsDiv = document.getElementById("events");

        eventSource.addEventListener("connect", function(e) {
            eventsDiv.innerHTML += "<p><strong>Conectado:</strong> " + e.data + "</p>";
        });

        eventSource.addEventListener("update", function(e) {
            const data = JSON.parse(e.data);
            eventsDiv.innerHTML += "<p><strong>Update " + data.counter + ":</strong> " +
                                 JSON.stringify(data) + "</p>";
        });

        eventSource.addEventListener("disconnect", function(e) {
            eventsDiv.innerHTML += "<p><strong>Desconectado:</strong> " + e.data + "</p>";
            eventSource.close();
        });

        eventSource.onerror = function(e) {
            eventsDiv.innerHTML += "<p><strong>Erro:</strong> " + e.type + "</p>";
        };
    </script>
</body>
</html>';

    $res->html($html);
});

// Rota principal com links para os exemplos
Router::get('/', function ($req, $res) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <title>Exemplos de Streaming</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1 { color: #333; }
        ul { margin: 20px 0; }
        li { margin: 10px 0; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .description { color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <h1>Exemplos de Streaming no Express-PHP</h1>

    <h2>Exemplos Disponíveis:</h2>
    <ul>
        <li>
            <a href="/stream/text" target="_blank">/stream/text</a>
            <div class="description">Streaming de texto simples com chunks enviados a cada segundo</div>
        </li>
        <li>
            <a href="/stream/json" target="_blank">/stream/json</a>
            <div class="description">Streaming de dados JSON em array</div>
        </li>
        <li>
            <a href="/stream/events" target="_blank">/stream/events</a>
            <div class="description">Server-Sent Events (SSE) - dados em tempo real</div>
        </li>
        <li>
            <a href="/stream/test-sse" target="_blank">/stream/test-sse</a>
            <div class="description">Página HTML para testar Server-Sent Events</div>
        </li>
        <li>
            <a href="/stream/file" target="_blank">/stream/file</a>
            <div class="description">Download de arquivo via streaming</div>
        </li>
        <li>
            <a href="/stream/process" target="_blank">/stream/process</a>
            <div class="description">Streaming da saída de um processo (ping)</div>
        </li>
        <li>
            <a href="/stream/custom-buffer" target="_blank">/stream/custom-buffer</a>
            <div class="description">Streaming com buffer personalizado</div>
        </li>
    </ul>

    <h2>Como Usar:</h2>
    <p>Clique nos links acima para testar os diferentes tipos de streaming.
    Alguns exemplos funcionam melhor em uma nova aba do navegador.</p>
</body>
</html>';

    $res->html($html);
});

$app->listen(8000);
