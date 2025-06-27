<?php

declare(strict_types=1);

namespace Express\Http\Psr7\Factory;

use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\StreamInterface;
use Express\Http\Psr7\UploadedFile;

/**
 * PSR-17 Uploaded File Factory implementation
 */
class UploadedFileFactory implements UploadedFileFactoryInterface
{
    /**
     * Create a new uploaded file.
     */
    public function createUploadedFile(
        StreamInterface $stream,
        int $size = null,
        int $error = \UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null
    ): UploadedFileInterface {
        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }
}
