<?php

namespace Express\Events;

/**
 * Classe de evento
 */
class Event
{
    private string $name;
    private array $data;
    private bool $propagationStopped = false;

    public function __construct(string $name, array $data = [])
    {
        $this->name = $name;
        $this->data = $data;
    }

    /**
     * Retorna o nome do evento
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retorna os dados do evento
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Retorna um dado específico
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Define um dado
     */
    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Para a propagação do evento
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * Verifica se a propagação foi parada
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
