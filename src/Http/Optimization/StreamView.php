<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Optimization;

/**
 * Stream View for zero-copy file reading
 */
class StreamView
{
    private string $filePath;
    private int $offset;
    private int $length;
    /** @var resource|null */
    private $handle = null;

    public function __construct(
        string $filePath,
        int $offset = 0,
        ?int $length = null
    ) {
        $this->filePath = $filePath;
        $this->offset = $offset;
        $fileSize = filesize($filePath);
        if ($fileSize === false) {
            throw new \RuntimeException("Cannot get file size for: {$filePath}");
        }
        $this->length = $length ?? ($fileSize - $offset);
    }

    /**
     * Read method
     */
    public function read(?int $bytes = null): string|false
    {
        if (!$this->handle) {
            $resource = fopen($this->filePath, 'rb');
            if ($resource === false) {
                throw new \RuntimeException("Cannot open file: {$this->filePath}");
            }
            $this->handle = $resource;
            fseek($this->handle, $this->offset);
        }

        $bytes = $bytes ?? $this->length;
        $bytes = min($bytes, $this->length);
        $bytes = max(1, $bytes); // Ensure at least 1 byte for fread

        return fread($this->handle, $bytes);
    }

    /**
     * @param resource $destination
     */
    public function stream($destination, int $chunkSize = 8192): int
    {
        if (!is_resource($destination)) {
            throw new \InvalidArgumentException('Destination must be a valid resource');
        }

        $totalBytes = 0;
        $remaining = $this->length;

        while ($remaining > 0) {
            $chunk = $this->read(min($chunkSize, $remaining));
            if ($chunk === false || $chunk === '') {
                break;
            }

            $written = fwrite($destination, $chunk);
            if ($written === false) {
                throw new \RuntimeException('Failed to write to destination');
            }
            $totalBytes += $written;
            $remaining -= strlen($chunk);
        }

        return $totalBytes;
    }

    /**
     * __destruct method
     */
    public function __destruct()
    {
        if ($this->handle) {
            fclose($this->handle);
        }
    }
}
