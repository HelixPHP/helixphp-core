<?php

namespace Express\Exceptions;

use Exception;
use Throwable;

/**
 * Exceção base para erros HTTP.
 *
 * Representa erros HTTP com códigos de status específicos.
 */
class HttpException extends Exception
{
    /**
     * Código de status HTTP.
     * @var int
     */
    protected int $statusCode;

    /**
     * Headers HTTP adicionais.
     * @var array<string, string>
     */
    protected array $headers;

    /**
     * Construtor da exceção HTTP.
     *
     * @param int $statusCode Código de status HTTP
     * @param string $message Mensagem da exceção
     * @param array<string, string> $headers Headers adicionais
     * @param Throwable|null $previous Exceção anterior
     */
    public function __construct(
        int $statusCode = 500,
        string $message = '',
        array $headers = [],
        ?Throwable $previous = null
    ) {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        if (empty($message)) {
            $message = $this->getDefaultMessage($statusCode);
        }

        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Obtém o código de status HTTP.
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Obtém os headers HTTP.
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Define headers HTTP.
     *
     * @param array<string, string> $headers Headers a serem definidos
     * @return $this
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Adiciona um header HTTP.
     *
     * @param string $name Nome do header
     * @param string $value Valor do header
     * @return $this
     */
    public function addHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Obtém mensagem padrão para um código de status.
     *
     * @param int $statusCode Código de status
     * @return string
     */
    protected function getDefaultMessage(int $statusCode): string
    {
        $messages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Payload Too Large',
            414 => 'URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => 'I\'m a teapot',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            425 => 'Too Early',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            451 => 'Unavailable For Legal Reasons',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            508 => 'Loop Detected',
            510 => 'Not Extended',
            511 => 'Network Authentication Required'
        ];

        return $messages[$statusCode] ?? 'HTTP Error';
    }

    /**
     * Converte a exceção para array.
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'error' => true,
            'status' => $this->statusCode,
            'message' => $this->getMessage(),
            'headers' => $this->headers
        ];
    }

    /**
     * Converte a exceção para JSON.
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray()) ?: '{"error": true}';
    }
}
