# Documentação do Streaming no Express-PHP

O Express-PHP agora oferece suporte completo para streaming de dados, permitindo o envio de respostas em tempo real e o processamento de grandes volumes de dados de forma eficiente.

## Índice

1. [Métodos de Streaming](#métodos-de-streaming)
2. [Streaming de Texto](#streaming-de-texto)
3. [Streaming de JSON](#streaming-de-json)
4. [Streaming de Arquivos](#streaming-de-arquivos)
5. [Server-Sent Events (SSE)](#server-sent-events-sse)
6. [Streaming de Recursos](#streaming-de-recursos)
7. [Configurações Avançadas](#configurações-avançadas)
8. [Exemplos Práticos](#exemplos-práticos)
9. [Boas Práticas](#boas-práticas)

## Métodos de Streaming

### `startStream(?string $contentType = null): self`

Inicia o modo streaming configurando os cabeçalhos necessários.

```php
$res->startStream('text/plain; charset=utf-8');
```

### `write(string $data, bool $flush = true): self`

Envia dados como stream.

```php
$res->write("Dados para enviar");
```

### `writeJson($data, bool $flush = true): self`

Envia dados JSON como stream.

```php
$res->writeJson(['id' => 1, 'message' => 'Hello']);
```

### `endStream(): self`

Finaliza o stream e limpa recursos.

```php
$res->endStream();
```

## Streaming de Texto

### Exemplo Básico

```php
Router::get('/stream/text', function ($req, $res) {
    $res->startStream('text/plain; charset=utf-8');

    for ($i = 1; $i <= 10; $i++) {
        $res->write("Chunk {$i}\n");
        sleep(1); // Simula processamento
    }

    $res->endStream();
});
```

### Streaming de Logs em Tempo Real

```php
Router::get('/logs/tail', function ($req, $res) {
    $res->startStream('text/plain; charset=utf-8');

    $logFile = '/var/log/app.log';
    $handle = fopen($logFile, 'r');

    // Posicionar no final do arquivo
    fseek($handle, 0, SEEK_END);

    while (true) {
        $line = fgets($handle);
        if ($line !== false) {
            $res->write($line);
        } else {
            usleep(100000); // 0.1 segundo
        }
    }

    fclose($handle);
});
```

## Streaming de JSON

### Array JSON Incremental

```php
Router::get('/data/stream', function ($req, $res) {
    $res->startStream('application/json; charset=utf-8');

    $res->write('['); // Início do array

    for ($i = 1; $i <= 100; $i++) {
        $data = [
            'id' => $i,
            'timestamp' => time(),
            'value' => rand(1, 1000)
        ];

        if ($i > 1) {
            $res->write(',');
        }

        $res->writeJson($data);
        usleep(50000); // 0.05 segundo
    }

    $res->write(']'); // Fim do array
    $res->endStream();
});
```

### JSONL (JSON Lines)

```php
Router::get('/data/jsonl', function ($req, $res) {
    $res->startStream('application/x-ndjson; charset=utf-8');

    for ($i = 1; $i <= 1000; $i++) {
        $data = [
            'record' => $i,
            'data' => "Item {$i}",
            'timestamp' => date('c')
        ];

        $res->writeJson($data);
        $res->write("\n"); // Nova linha para JSONL
    }

    $res->endStream();
});
```

## Streaming de Arquivos

### `streamFile(string $filePath, array $headers = []): self`

Envia um arquivo como stream.

```php
Router::get('/download/:filename', function ($req, $res) {
    $filename = $req->params['filename'];
    $filePath = "/uploads/{$filename}";

    if (!file_exists($filePath)) {
        $res->status(404)->json(['error' => 'Arquivo não encontrado']);
        return;
    }

    $headers = [
        'Content-Disposition' => "attachment; filename=\"{$filename}\""
    ];

    $res->streamFile($filePath, $headers);
});
```

### Streaming com Range Requests (Download Parcial)

```php
Router::get('/video/:id', function ($req, $res) {
    $videoPath = "/videos/{$req->params['id']}.mp4";

    if (!file_exists($videoPath)) {
        $res->status(404)->json(['error' => 'Vídeo não encontrado']);
        return;
    }

    $fileSize = filesize($videoPath);
    $start = 0;
    $end = $fileSize - 1;

    // Verificar se há Range header
    if (isset($_SERVER['HTTP_RANGE'])) {
        if (preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
            $start = intval($matches[1]);
            if (!empty($matches[2])) {
                $end = intval($matches[2]);
            }
        }

        $res->status(206); // Partial Content
        $res->header('Content-Range', "bytes {$start}-{$end}/{$fileSize}");
    }

    $res->header('Accept-Ranges', 'bytes');
    $res->header('Content-Length', (string)($end - $start + 1));
    $res->header('Content-Type', 'video/mp4');

    $handle = fopen($videoPath, 'rb');
    fseek($handle, $start);

    $res->startStream();

    $remaining = $end - $start + 1;
    while ($remaining > 0 && !feof($handle)) {
        $chunk = fread($handle, min(8192, $remaining));
        $res->write($chunk);
        $remaining -= strlen($chunk);
    }

    fclose($handle);
    $res->endStream();
});
```

## Server-Sent Events (SSE)

### `sendEvent($data, ?string $event = null, ?string $id = null, ?int $retry = null): self`

Envia dados como Server-Sent Events.

```php
Router::get('/events/live', function ($req, $res) {
    $res->sendEvent('Conexão estabelecida', 'connect', '1');

    for ($i = 1; $i <= 50; $i++) {
        $data = [
            'counter' => $i,
            'timestamp' => time(),
            'message' => "Update #{$i}"
        ];

        $res->sendEvent($data, 'update', (string)$i, 3000);
        sleep(1);
    }

    $res->sendEvent('Stream finalizado', 'disconnect');
});
```

### Chat em Tempo Real com SSE

```php
Router::get('/chat/events', function ($req, $res) {
    $res->sendEvent('Conectado ao chat', 'connect');

    // Simular mensagens de chat
    $messages = [
        ['user' => 'João', 'message' => 'Olá pessoal!'],
        ['user' => 'Maria', 'message' => 'Oi João!'],
        ['user' => 'Pedro', 'message' => 'Tudo bem?'],
    ];

    foreach ($messages as $i => $msg) {
        $res->sendEvent($msg, 'message', (string)($i + 1));
        sleep(2);
    }
});
```

### `sendHeartbeat(): self`

Envia um heartbeat para manter a conexão ativa.

```php
Router::get('/events/heartbeat', function ($req, $res) {
    for ($i = 1; $i <= 100; $i++) {
        $res->sendEvent(['counter' => $i], 'data');

        // Enviar heartbeat a cada 10 mensagens
        if ($i % 10 === 0) {
            $res->sendHeartbeat();
        }

        sleep(1);
    }
});
```

## Streaming de Recursos

### `streamResource($resource, ?string $contentType = null): self`

Streaming de um recurso PHP.

```php
Router::get('/process/output', function ($req, $res) {
    $command = 'tail -f /var/log/syslog';
    $process = popen($command, 'r');

    if (!$process) {
        $res->status(500)->json(['error' => 'Falha ao executar comando']);
        return;
    }

    $res->streamResource($process, 'text/plain; charset=utf-8');
    pclose($process);
});
```

### Streaming de URL Remota

```php
Router::get('/proxy/stream', function ($req, $res) {
    $url = $req->query['url'] ?? '';

    if (empty($url)) {
        $res->status(400)->json(['error' => 'URL é obrigatória']);
        return;
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Express-PHP Streaming Proxy'
        ]
    ]);

    $stream = fopen($url, 'r', false, $context);

    if (!$stream) {
        $res->status(404)->json(['error' => 'Falha ao abrir URL']);
        return;
    }

    $res->streamResource($stream);
    fclose($stream);
});
```

## Configurações Avançadas

### `setStreamBufferSize(int $size): self`

Define o tamanho do buffer para streaming.

```php
Router::get('/large-data', function ($req, $res) {
    // Buffer de 64KB para dados grandes
    $res->setStreamBufferSize(65536)
        ->startStream('application/octet-stream');

    // Gerar dados grandes
    for ($i = 0; $i < 1000; $i++) {
        $data = str_repeat('A', 1024); // 1KB de dados
        $res->write($data);
    }

    $res->endStream();
});
```

### `isStreaming(): bool`

Verifica se está em modo streaming.

```php
Router::get('/conditional-stream', function ($req, $res) {
    $useStream = $req->query['stream'] === 'true';

    if ($useStream) {
        $res->startStream('application/json');

        for ($i = 1; $i <= 10; $i++) {
            $res->writeJson(['item' => $i]);

            if ($res->isStreaming()) {
                sleep(1); // Delay apenas no streaming
            }
        }

        $res->endStream();
    } else {
        // Resposta normal
        $data = array_map(fn($i) => ['item' => $i], range(1, 10));
        $res->json($data);
    }
});
```

## Exemplos Práticos

### Dashboard em Tempo Real

```php
Router::get('/dashboard/metrics', function ($req, $res) {
    $res->sendEvent(['status' => 'connected'], 'connect');

    while (true) {
        $metrics = [
            'cpu_usage' => rand(10, 90),
            'memory_usage' => rand(20, 80),
            'disk_usage' => rand(30, 70),
            'timestamp' => time()
        ];

        $res->sendEvent($metrics, 'metrics');
        sleep(5); // Atualizar a cada 5 segundos
    }
});
```

### Export de Dados Grandes

```php
Router::get('/export/users/csv', function ($req, $res) {
    $res->header('Content-Type', 'text/csv; charset=utf-8')
        ->header('Content-Disposition', 'attachment; filename="users.csv"')
        ->startStream();

    // Cabeçalho CSV
    $res->write("ID,Nome,Email,Data de Cadastro\n");

    // Simular consulta paginada no banco
    $page = 0;
    $limit = 1000;

    do {
        $users = getUsersPaginated($page, $limit); // Função fictícia

        foreach ($users as $user) {
            $csv = sprintf(
                "%d,%s,%s,%s\n",
                $user['id'],
                escapeCsv($user['name']),
                escapeCsv($user['email']),
                $user['created_at']
            );
            $res->write($csv);
        }

        $page++;
    } while (count($users) === $limit);

    $res->endStream();
});

function escapeCsv($value) {
    return '"' . str_replace('"', '""', $value) . '"';
}
```

### Progress Streaming

```php
Router::get('/import/progress', function ($req, $res) {
    $total = 1000;

    $res->sendEvent(['total' => $total], 'start');

    for ($i = 1; $i <= $total; $i++) {
        // Simular processamento
        usleep(10000); // 0.01 segundo

        $progress = [
            'current' => $i,
            'total' => $total,
            'percentage' => round(($i / $total) * 100, 2),
            'status' => "Processando item {$i}"
        ];

        $res->sendEvent($progress, 'progress');
    }

    $res->sendEvent(['status' => 'completed'], 'finish');
});
```

## Boas Práticas

### 1. Gerenciamento de Conexões

```php
// Verificar se o cliente ainda está conectado
Router::get('/long-stream', function ($req, $res) {
    $res->startStream();

    for ($i = 1; $i <= 1000; $i++) {
        // Verificar se a conexão ainda existe
        if (connection_aborted()) {
            error_log("Cliente desconectou no item {$i}");
            break;
        }

        $res->write("Item {$i}\n");
        sleep(1);
    }

    $res->endStream();
});
```

### 2. Tratamento de Erros

```php
Router::get('/safe-stream', function ($req, $res) {
    try {
        $res->startStream('text/plain; charset=utf-8');

        // Código que pode falhar
        for ($i = 1; $i <= 100; $i++) {
            if ($i === 50) {
                throw new Exception("Erro simulado");
            }

            $res->write("Item {$i}\n");
        }

        $res->endStream();
    } catch (Exception $e) {
        if ($res->isStreaming()) {
            $res->write("ERRO: " . $e->getMessage() . "\n");
            $res->endStream();
        } else {
            $res->status(500)->json(['error' => $e->getMessage()]);
        }
    }
});
```

### 3. Otimização de Performance

```php
Router::get('/optimized-stream', function ($req, $res) {
    // Buffer maior para dados volumosos
    $res->setStreamBufferSize(32768); // 32KB

    $res->startStream('application/json');

    // Desabilitar timeout para streams longos
    set_time_limit(0);

    // Usar buffer interno para reduzir chamadas write
    $buffer = '';
    $bufferSize = 8192;

    for ($i = 1; $i <= 10000; $i++) {
        $data = json_encode(['id' => $i, 'data' => str_repeat('x', 100)]);
        $buffer .= $data . "\n";

        if (strlen($buffer) >= $bufferSize) {
            $res->write($buffer, true);
            $buffer = '';
        }
    }

    // Enviar buffer restante
    if (!empty($buffer)) {
        $res->write($buffer, true);
    }

    $res->endStream();
});
```

### 4. Monitoramento

```php
Router::get('/monitored-stream', function ($req, $res) {
    $startTime = microtime(true);
    $bytesSent = 0;

    $res->startStream('text/plain; charset=utf-8');

    for ($i = 1; $i <= 100; $i++) {
        $data = "Chunk {$i} - " . date('Y-m-d H:i:s') . "\n";
        $res->write($data);

        $bytesSent += strlen($data);

        // Log de progresso a cada 10 chunks
        if ($i % 10 === 0) {
            $elapsed = microtime(true) - $startTime;
            $rate = $bytesSent / $elapsed;

            error_log(sprintf(
                "Stream progress: %d/%d chunks, %.2f KB/s",
                $i, 100, $rate / 1024
            ));
        }

        sleep(1);
    }

    $totalTime = microtime(true) - $startTime;
    error_log("Stream completed in {$totalTime}s, {$bytesSent} bytes sent");

    $res->endStream();
});
```

## Considerações de Segurança

1. **Validação de Entrada**: Sempre valide parâmetros antes de usar em streams
2. **Limitação de Taxa**: Implemente rate limiting para streams longos
3. **Timeout**: Configure timeouts apropriados
4. **Autenticação**: Implemente autenticação para streams sensíveis
5. **Monitoramento**: Monitore uso de recursos durante streaming

## Conclusão

O suporte a streaming no Express-PHP permite criar aplicações mais eficientes e responsivas, especialmente para:

- Transferência de arquivos grandes
- Dados em tempo real
- Relatórios e exports
- Monitoramento e dashboards
- APIs de streaming de mídia

Use os exemplos e práticas descritas nesta documentação para implementar streaming de forma eficiente e segura em suas aplicações.
