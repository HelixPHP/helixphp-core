<?php

/**
 * This file contains PHPDoc annotations for PSR-7 v1.x compatibility
 *
 * When using PSR-7 v1.x, return types are not declared in the interface,
 * but we can still provide type information through PHPDoc for IDE support
 * and static analysis tools.
 */

namespace PivotPHP\Core\Http\Psr7;

/**
 * @method string getProtocolVersion()
 * @method MessageInterface withProtocolVersion(string $version)
 * @method array getHeaders()
 * @method bool hasHeader(string $name)
 * @method array getHeader(string $name)
 * @method string getHeaderLine(string $name)
 * @method MessageInterface withHeader(string $name, $value)
 * @method MessageInterface withAddedHeader(string $name, $value)
 * @method MessageInterface withoutHeader(string $name)
 * @method StreamInterface getBody()
 * @method MessageInterface withBody(StreamInterface $body)
 */
trait Psr7V1CompatibilityTrait
{
}
