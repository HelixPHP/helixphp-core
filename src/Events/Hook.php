<?php

declare(strict_types=1);

namespace PivotPHP\Core\Events;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Hook System for PivotPHP
 *
 * Provides a flexible hook system for extensions to register
 * listeners and modify application behavior.
 */
class Hook implements StoppableEventInterface
{
    /**
     * Hook name/identifier
     */
    protected string $name;

    /**
     * Hook data/payload
     *
     * @var mixed
     */
    protected mixed $data;

    /**
     * Additional context
     *
     * @var array<string, mixed>
     */
    protected array $context;

    /**
     * Whether the event propagation is stopped
     */
    protected bool $propagationStopped = false;

    /**
     * Create new hook event
     *
     * @param mixed $data
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $name,
        mixed $data = null,
        array $context = []
    ) {
        $this->name = $name;
        $this->data = $data;
        $this->context = $context;
    }

    /**
     * Get hook name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get hook data
     *
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Set hook data
     *
     * @param mixed $data
     */
    public function setData(mixed $data): void
    {
        $this->data = $data;
    }

    /**
     * Get context
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set context value
     *
     * @param mixed $value
     */
    public function setContext(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }

    /**
     * Get context value
     *
     * @param mixed $default
     * @return mixed
     */
    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * Stop event propagation
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * Check if propagation is stopped
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Create a filter hook (modifies data)
     *
     * @param mixed $data
     * @param array<string, mixed> $context
     */
    public static function filter(
        string $name,
        mixed $data,
        array $context = []
    ): self {
        return new self($name, $data, $context);
    }

    /**
     * Create an action hook (performs actions)
     *
     * @param array<string, mixed> $context
     */
    public static function action(string $name, array $context = []): self
    {
        return new self($name, null, $context);
    }
}
