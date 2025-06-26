<?php

namespace Express\Services;

/**
 * Classe Response constrói e envia a resposta HTTP.
 * Permite definir status, cabeçalhos e corpo da resposta em diferentes formatos.
 *
 * @property int $statusCode Código de status HTTP.
 * @property array $headers Cabeçalhos da resposta.
 * @property string $body Corpo da resposta.
 */
class Response
{
  /**
   * Código de status HTTP.
   * @var int
   */
    private $statusCode = 200;
  /**
   * Cabeçalhos da resposta.
   * @var array<string, mixed>
   */
    private $headers = [];
  /**
   * Corpo da resposta.
   * @var string
   */
    private $body = '';

  /**
   * Define o status HTTP da resposta.
   * @param int $code Código de status.
   * @return $this
   */
    public function status($code)
    {
        $this->statusCode = $code;
        http_response_code($this->statusCode);
        return $this;
    }
  /**
   * Define um cabeçalho na resposta.
   * @param string $name Nome do cabeçalho.
   * @param string $value Valor do cabeçalho.
   * @return $this
   */
    public function header($name, $value)
    {
        $this->headers[$name] = $value;
        header("{$name}: {$value}");
        return $this;
    }

  /**
   * Obtém os cabeçalhos da resposta.
   * @return array<string, mixed>
   */
    public function getHeaders(): array
    {
        return $this->headers;
    }
  /**
   * Envia resposta em formato JSON.
   * @param mixed $data Dados a serem enviados.
   * @return $this
   */
    public function json($data)
    {
        $this->header('Content-Type', 'application/json; charset=utf-8');
        $encoded = json_encode($data);
        $this->body = $encoded !== false ? $encoded : '{}';
        echo $this->body;
        return $this;
    }
  /**
   * Envia resposta em texto puro.
   * @param string $text Texto a ser enviado.
   * @return $this
   */
    public function text($text)
    {
        $this->header('Content-Type', 'text/plain; charset=utf-8');
        $this->body = $text;
        echo $this->body;
        return $this;
    }
  /**
   * Envia resposta em HTML.
   * @param string $html HTML a ser enviado.
   * @return $this
   */
    public function html($html)
    {
        $this->header('Content-Type', 'text/html; charset=utf-8');
        $this->body = $html;
        echo $this->body;
        return $this;
    }

  /**
   * Indica se a resposta está sendo enviada como stream.
   * @var bool
   */
    private $isStreaming = false;

  /**
   * Buffer size para streaming (em bytes).
   * @var int
   */
    private $streamBufferSize = 8192;

  /**
   * Define o buffer size para streaming.
   * @param int $size Tamanho do buffer em bytes.
   * @return $this
   */
    public function setStreamBufferSize(int $size): self
    {
        $this->streamBufferSize = $size;
        return $this;
    }

  /**
   * Inicia o modo streaming configurando os cabeçalhos necessários.
   * @param string|null $contentType Tipo de conteúdo (opcional).
   * @return $this
   */
    public function startStream(?string $contentType = null): self
    {
        $this->isStreaming = true;

        // Configurar cabeçalhos para streaming
        $this->header('Cache-Control', 'no-cache');
        $this->header('Connection', 'keep-alive');

        if ($contentType) {
            $this->header('Content-Type', $contentType);
        }

        // Desabilitar output buffering para streaming em tempo real apenas se não estamos em teste
        if (!defined('PHPUNIT_TESTSUITE') && ob_get_level()) {
            ob_end_flush();
        }

        return $this;
    }

  /**
   * Envia dados como stream.
   * @param string $data Dados a serem enviados.
   * @param bool $flush Se deve fazer flush imediatamente.
   * @return $this
   */
    public function write(string $data, bool $flush = true): self
    {
        echo $data;

        if ($flush && !defined('PHPUNIT_TESTSUITE')) {
            if (ob_get_level()) {
                ob_flush();
            }
            flush();
        }

        return $this;
    }

  /**
   * Envia dados JSON como stream.
   * @param mixed $data Dados a serem enviados em JSON.
   * @param bool $flush Se deve fazer flush imediatamente.
   * @return $this
   */
    public function writeJson($data, bool $flush = true): self
    {
        $json = json_encode($data);
        if ($json === false) {
            error_log('JSON encoding failed: ' . json_last_error_msg() . '. Input data: ' . var_export($data, true));
            $json = '{}';
        }

        return $this->write($json, $flush);
    }

  /**
   * Envia um arquivo como stream.
   * @param string $filePath Caminho para o arquivo.
   * @param array<string, string> $headers Cabeçalhos adicionais.
   * @return $this
   * @throws \InvalidArgumentException Se o arquivo não existir ou não for legível.
   */
    public function streamFile(string $filePath, array $headers = []): self
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException("File not found or not readable: {$filePath}");
        }

        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        // Configurar cabeçalhos
        $this->header('Content-Type', $mimeType);
        $this->header('Content-Length', (string)$fileSize);
        $this->header('Accept-Ranges', 'bytes');

        // Adicionar cabeçalhos personalizados
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }

        $this->startStream();

        // Abrir arquivo e enviar em chunks
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new \InvalidArgumentException("Unable to open file: {$filePath}");
        }

        while (!feof($handle)) {
            $bufferSize = max(1, $this->streamBufferSize);
            $chunk = fread($handle, $bufferSize);
            if ($chunk !== false) {
                $this->write($chunk, true);
            }
        }

        fclose($handle);
        return $this;
    }

  /**
   * Envia dados de um recurso como stream.
   * @param resource $resource Recurso a ser transmitido.
   * @param string|null $contentType Tipo de conteúdo.
   * @return $this
   * @throws \InvalidArgumentException Se o recurso não for válido.
   */
    public function streamResource($resource, ?string $contentType = null): self
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException("Invalid resource provided");
        }

        $this->startStream($contentType);

        while (!feof($resource)) {
            $bufferSize = max(1, $this->streamBufferSize);
            $chunk = fread($resource, $bufferSize);
            if ($chunk !== false) {
                $this->write($chunk, true);
            }
        }

        return $this;
    }

  /**
   * Envia dados como Server-Sent Events (SSE).
   * @param mixed $data Dados a serem enviados.
   * @param string|null $event Nome do evento (opcional).
   * @param string|null $id ID do evento (opcional).
   * @param int|null $retry Tempo de retry em milissegundos (opcional).
   * @return $this
   */
    public function sendEvent($data, ?string $event = null, ?string $id = null, ?int $retry = null): self
    {
        if (!$this->isStreaming) {
            $this->startStream('text/event-stream');
        }

        $output = '';

        if ($id !== null) {
            $output .= "id: {$id}\n";
        }

        if ($event !== null) {
            $output .= "event: {$event}\n";
        }

        if ($retry !== null) {
            $output .= "retry: {$retry}\n";
        }

        // Converter dados para string
        if (is_array($data) || is_object($data)) {
            $dataString = json_encode($data);
            if ($dataString === false) {
                $dataString = '[json encoding failed]';
            }
        } else {
            $dataString = (string)$data;
        }

        // Dividir dados em múltiplas linhas se necessário
        $lines = explode("\n", $dataString);
        foreach ($lines as $line) {
            $output .= "data: {$line}\n";
        }

        $output .= "\n"; // Linha em branco para finalizar o evento

        return $this->write($output, true);
    }

  /**
   * Envia um evento de heartbeat (ping) para manter a conexão SSE ativa.
   * @return $this
   */
    public function sendHeartbeat(): self
    {
        return $this->write(": heartbeat\n\n", true);
    }

  /**
   * Finaliza o stream e limpa recursos.
   * @return $this
   */
    public function endStream(): self
    {
        if ($this->isStreaming) {
            $this->isStreaming = false;

            if (!defined('PHPUNIT_TESTSUITE') && ob_get_level()) {
                ob_end_flush();
            }
            if (!defined('PHPUNIT_TESTSUITE')) {
                flush();
            }
        }

        return $this;
    }

  /**
   * Verifica se a resposta está em modo streaming.
   * @return bool
   */
    public function isStreaming(): bool
    {
        return $this->isStreaming;
    }
}
