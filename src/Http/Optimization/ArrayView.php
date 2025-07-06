<?php

declare(strict_types=1);

namespace Helix\Http\Optimization;

/**
 * Array View for zero-copy array operations
 * @implements \ArrayAccess<int, mixed>
 * @implements \Iterator<int, mixed>
 */
class ArrayView implements \ArrayAccess, \Iterator, \Countable
{
    private array $source;
    private int $offset;
    private int $length;
    private array $keys;
    private int $position = 0;

    public function __construct(array &$source, int $offset, ?int $length = null)
    {
        $this->source = &$source;
        $this->offset = max(0, $offset);
        $this->length = $length ?? (count($source) - $this->offset);

        $this->keys = array_slice(array_keys($source), $this->offset, $this->length);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->keys[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }
        $realKey = $this->keys[$offset];
        return $this->source[$realKey];
    }

    public function offsetSet($offset, $value): void
    {
        if ($this->offsetExists($offset)) {
            $realKey = $this->keys[$offset];
            $this->source[$realKey] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        if ($this->offsetExists($offset)) {
            $realKey = $this->keys[$offset];
            unset($this->source[$realKey]);
            array_splice($this->keys, (int)$offset, 1);
            $this->length--;
        }
    }

    public function current(): mixed
    {
        $key = $this->keys[$this->position];
        return $this->source[$key];
    }

    public function key(): mixed
    {
        return $this->keys[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return $this->position < count($this->keys);
    }

    public function count(): int
    {
        return count($this->keys);
    }
}
