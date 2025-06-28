<?php

namespace Express\Logging;

/**
 * Sistema de logging para Express-PHP
 */
class Logger
{
    public const EMERGENCY = 0;
    public const ALERT = 1;
    public const CRITICAL = 2;
    public const ERROR = 3;
    public const WARNING = 4;
    public const NOTICE = 5;
    public const INFO = 6;
    public const DEBUG = 7;

    /**
     * @var array<int, string>
     */
    private static array $levels = [
        self::EMERGENCY => 'EMERGENCY',
        self::ALERT => 'ALERT',
        self::CRITICAL => 'CRITICAL',
        self::ERROR => 'ERROR',
        self::WARNING => 'WARNING',
        self::NOTICE => 'NOTICE',
        self::INFO => 'INFO',
        self::DEBUG => 'DEBUG'
    ];

    /**
     * @var array<LogHandlerInterface>
     */
    private array $handlers = [];
    private int $level = self::DEBUG;

    public function __construct(int $level = self::DEBUG)
    {
        $this->level = $level;
    }

    /**
     * Adiciona um handler
     */
    public function addHandler(LogHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }

    /**
     * Define o nível mínimo de log
     */
    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    /**
     * Registra uma mensagem de log
     * @param array<string, mixed> $context
     */
    public function log(int $level, string $message, array $context = []): void
    {
        if ($level > $this->level) {
            return;
        }

        $record = [
            'level' => $level,
            'level_name' => self::$levels[$level],
            'message' => $message,
            'context' => $context,
            'datetime' => new \DateTime(),
            'extra' => []
        ];

        foreach ($this->handlers as $handler) {
            $handler->handle($record);
        }
    }

    /**
     * Métodos de conveniência
     * @param array<string, mixed> $context
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function alert(string $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function notice(string $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }
}
