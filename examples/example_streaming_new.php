<?php
// Exemplo de uso de streaming HTTP do Express PHP

require_once '../vendor/autoload.php';

use Express\ApiExpress;
use Express\Middleware\Security\CorsMiddleware;

// Criar aplicação
$app = new ApiExpress('http://localhost:8000');

// Middleware CORS para permitir streaming cross-origin
$app->use(CorsMiddleware::development());

// ========================================
// ROTAS DE STREAMING
// ========================================

// Server-Sent Events (SSE) - Eventos em tempo real
$app->get('/events', function($req, $res) {
    $res->startStream('text/event-stream')
        ->header('Cache-Control', 'no-cache')
        ->header('Connection', 'keep-alive');

    // Enviar eventos de exemplo
    for ($i = 1; $i <= 10; $i++) {
        $res->sendEvent([
            'id' => $i,
            'event' => 'update',
            'data' => [
                'message' => "Update #{$i}",
                'timestamp' => date('Y-m-d H:i:s'),
                'progress' => $i * 10
            ]
        ]);

        // Simular processamento
        if (connection_aborted()) break;
        sleep(1);
    }

    $res->sendEvent([
        'event' => 'complete',
        'data' => ['message' => 'Process completed']
    ]);

    $res->endStream();
});

// JSON Streaming - Stream de dados grandes
$app->get('/stream/data', function($req, $res) {
    $res->startStream('application/json')
        ->header('Transfer-Encoding', 'chunked');

    // Enviar início do array JSON
    $res->write('{"items":[');

    // Stream de 1000 itens
    for ($i = 1; $i <= 1000; $i++) {
        $item = [
            'id' => $i,
            'name' => "Item #{$i}",
            'value' => rand(1, 100),
            'timestamp' => date('c')
        ];

        if ($i > 1) $res->write(',');
        $res->writeJson($item);

        // Controlar taxa de envio
        if ($i % 50 === 0) {
            usleep(100000); // 100ms delay a cada 50 itens
        }

        if (connection_aborted()) break;
    }

    // Fechar array JSON
    $res->write(']}');
    $res->endStream();
});

// File Streaming - Download de arquivos grandes
$app->get('/stream/file/:filename', function($req, $res) {
    $filename = $req->param('filename');
    $filepath = __DIR__ . '/files/' . $filename; // Pasta de exemplo

    if (!file_exists($filepath)) {
        return $res->status(404)->json(['error' => 'File not found']);
    }

    $mimeType = mime_content_type($filepath);
    $filesize = filesize($filepath);

    $res->header('Content-Type', $mimeType)
        ->header('Content-Length', $filesize)
        ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

    $res->streamFile($filepath);
});

// Live Data Feed - Feed de dados em tempo real
$app->get('/live/feed', function($req, $res) {
    $res->startStream('text/event-stream')
        ->header('Cache-Control', 'no-cache');

    // Simular feed de dados em tempo real
    $count = 0;
    while ($count < 30 && !connection_aborted()) {
        $data = [
            'id' => uniqid(),
            'type' => ['info', 'warning', 'error'][rand(0, 2)],
            'message' => 'System event #' . (++$count),
            'cpu_usage' => rand(10, 90),
            'memory_usage' => rand(30, 85),
            'timestamp' => microtime(true)
        ];

        $res->sendEvent([
            'event' => 'system_update',
            'data' => $data
        ]);

        // Enviar heartbeat a cada 5 eventos
        if ($count % 5 === 0) {
            $res->sendHeartbeat();
        }

        sleep(2); // Update a cada 2 segundos
    }

    $res->endStream();
});

// Chat Simulation - Simulação de chat
$app->get('/chat/stream', function($req, $res) {
    $res->startStream('text/event-stream');

    $messages = [
        'Hello everyone!',
        'How is everyone doing?',
        'Great to see you all here',
        'This is a streaming chat demo',
        'Messages are sent in real-time',
        'Thanks for testing!',
        'Have a great day!'
    ];

    foreach ($messages as $index => $message) {
        $res->sendEvent([
            'event' => 'message',
            'data' => [
                'id' => $index + 1,
                'user' => 'User' . rand(1, 5),
                'message' => $message,
                'timestamp' => date('H:i:s')
            ]
        ]);

        sleep(3); // Mensagem a cada 3 segundos
        if (connection_aborted()) break;
    }

    $res->endStream();
});

// ========================================
// ROTAS DE INFORMAÇÃO
// ========================================

$app->get('/', function($req, $res) {
    return $res->json([
        'message' => 'Express-PHP Streaming Examples',
        'endpoints' => [
            'GET /events' => 'Server-Sent Events with progress updates',
            'GET /stream/data' => 'JSON streaming of large datasets',
            'GET /stream/file/:filename' => 'File streaming for downloads',
            'GET /live/feed' => 'Live data feed simulation',
            'GET /chat/stream' => 'Chat messages simulation'
        ],
        'features' => [
            'Server-Sent Events (SSE)',
            'JSON streaming',
            'File streaming',
            'Real-time data feeds',
            'Connection management',
            'Chunked transfer encoding'
        ],
        'usage' => [
            'Use EventSource in browser for SSE endpoints',
            'Check network tab to see streaming in action',
            'All endpoints support CORS for cross-origin access'
        ]
    ]);
});

// Página de teste HTML para demonstrar SSE
$app->get('/test', function($req, $res) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <title>Express-PHP Streaming Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        #events { border: 1px solid #ccc; padding: 10px; height: 300px; overflow-y: scroll; }
        .event { margin: 5px 0; padding: 5px; background: #f5f5f5; border-radius: 3px; }
        button { margin: 5px; padding: 10px; }
    </style>
</head>
<body>
    <h1>Express-PHP Streaming Test</h1>

    <button onclick="startEvents()">Start Events</button>
    <button onclick="startFeed()">Start Live Feed</button>
    <button onclick="startChat()">Start Chat</button>
    <button onclick="clearEvents()">Clear</button>

    <div id="events"></div>

    <script>
        let eventSource = null;

        function addEvent(text) {
            const div = document.createElement("div");
            div.className = "event";
            div.textContent = new Date().toLocaleTimeString() + " - " + text;
            document.getElementById("events").appendChild(div);
            document.getElementById("events").scrollTop = document.getElementById("events").scrollHeight;
        }

        function startEvents() {
            if (eventSource) eventSource.close();
            eventSource = new EventSource("/events");
            eventSource.onmessage = function(event) {
                addEvent("Event: " + event.data);
            };
            eventSource.addEventListener("complete", function(event) {
                addEvent("Completed: " + event.data);
                eventSource.close();
            });
        }

        function startFeed() {
            if (eventSource) eventSource.close();
            eventSource = new EventSource("/live/feed");
            eventSource.addEventListener("system_update", function(event) {
                const data = JSON.parse(event.data);
                addEvent(`${data.type}: ${data.message} (CPU: ${data.cpu_usage}%)`);
            });
        }

        function startChat() {
            if (eventSource) eventSource.close();
            eventSource = new EventSource("/chat/stream");
            eventSource.addEventListener("message", function(event) {
                const data = JSON.parse(event.data);
                addEvent(`${data.user}: ${data.message}`);
            });
        }

        function clearEvents() {
            document.getElementById("events").innerHTML = "";
            if (eventSource) eventSource.close();
        }
    </script>
</body>
</html>';

    return $res->html($html);
});

// Iniciar servidor
$app->listen(8000);
