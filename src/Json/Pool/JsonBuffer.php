<?php

declare(strict_types=1);

namespace PivotPHP\Core\Json\Pool;

/**
 * High-performance JSON buffer with memory optimization
 *
 * Provides efficient buffer management for JSON operations with
 * automatic expansion and reuse capabilities.
 *
 * @package PivotPHP\Core\Json\Pool
 * @since 1.1.1
 */
class JsonBuffer
{
    private string $buffer = '';
    private int $capacity;
    private int $position = 0;
    private bool $finalized = false;

    public function __construct(int $initialCapacity = 4096)
    {
        $this->capacity = $initialCapacity;
        $this->buffer = str_repeat(' ', $initialCapacity);
    }

    /**
     * Append string data to buffer
     */
    public function append(string $data): void
    {
        $dataLength = strlen($data);
        $requiredLength = $this->position + $dataLength;

        if ($requiredLength > $this->capacity) {
            $this->expand($requiredLength);
        }

        // Use substr_replace for in-place modification
        $this->buffer = substr_replace($this->buffer, $data, $this->position, $dataLength);
        $this->position += $dataLength;
    }

    /**
     * Append JSON-encoded value to buffer
     */
    public function appendJson(mixed $value, int $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE): void
    {
        $json = json_encode($value, $flags);
        if ($json === false) {
            throw new \InvalidArgumentException('Failed to encode value as JSON: ' . json_last_error_msg());
        }

        $this->append($json);
    }

    /**
     * Finalize buffer and return complete JSON string
     */
    public function finalize(): string
    {
        if (!$this->finalized) {
            $this->buffer = substr($this->buffer, 0, $this->position);
            $this->finalized = true;
        }

        return $this->buffer;
    }

    /**
     * Reset buffer for reuse
     */
    public function reset(): void
    {
        $this->position = 0;
        $this->finalized = false;
        // Don't reallocate, just reset position for performance
    }

    /**
     * Get buffer capacity
     */
    public function getCapacity(): int
    {
        return $this->capacity;
    }

    /**
     * Get current buffer size (used bytes)
     */
    public function getSize(): int
    {
        return $this->position;
    }

    /**
     * Get current buffer utilization percentage
     */
    public function getUtilization(): float
    {
        return $this->capacity > 0 ? ($this->position / $this->capacity) * 100 : 0;
    }

    /**
     * Check if buffer has available space
     */
    public function hasSpace(int $requiredBytes): bool
    {
        return ($this->position + $requiredBytes) <= $this->capacity;
    }

    /**
     * Get remaining available space in bytes
     */
    public function getRemainingSpace(): int
    {
        return $this->capacity - $this->position;
    }

    /**
     * Expand buffer capacity when needed
     */
    private function expand(int $requiredCapacity): void
    {
        $newCapacity = max($this->capacity * 2, $requiredCapacity);
        $newBuffer = str_repeat(' ', $newCapacity);
        $newBuffer = substr_replace($newBuffer, $this->buffer, 0, $this->capacity);

        $this->buffer = $newBuffer;
        $this->capacity = $newCapacity;
    }
}
