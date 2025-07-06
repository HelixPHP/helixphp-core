<?php

namespace Helix\Tests\Support;

use PHPUnit\Framework\TestCase;
use Helix\Support\Str;

class StrTest extends TestCase
{
    public function testCamel(): void
    {
        $this->assertEquals('expressPhp', Str::camel('express_php'));
        $this->assertEquals('expressPhp', Str::camel('express-php'));
        $this->assertEquals('expressPhp', Str::camel('Express Php'));
    }

    public function testStudly(): void
    {
        $this->assertEquals('ExpressPhp', Str::studly('express_php'));
        $this->assertEquals('ExpressPhp', Str::studly('express-php'));
        $this->assertEquals('ExpressPhp', Str::studly('express php'));
    }

    public function testSnake(): void
    {
        $this->assertEquals('express_php', Str::snake('ExpressPhp'));
        $this->assertEquals('express_php', Str::snake('expressPhp'));
    }

    public function testKebab(): void
    {
        $this->assertEquals('express-php', Str::kebab('ExpressPhp'));
        $this->assertEquals('express-php', Str::kebab('expressPhp'));
    }

    public function testLimit(): void
    {
        $this->assertEquals('Hello...', Str::limit('Hello World', 5));
        $this->assertEquals('Hello World', Str::limit('Hello World', 20));
        $this->assertEquals('Hello***', Str::limit('Hello World', 5, '***'));
    }

    public function testRandom(): void
    {
        $random1 = Str::random(10);
        $random2 = Str::random(10);

        $this->assertEquals(10, strlen($random1));
        $this->assertEquals(10, strlen($random2));
        $this->assertNotEquals($random1, $random2);
    }

    public function testStartsWith(): void
    {
        $this->assertTrue(Str::startsWith('HelixPHP', 'Express'));
        $this->assertFalse(Str::startsWith('HelixPHP', 'PHP'));
    }

    public function testEndsWith(): void
    {
        $this->assertTrue(Str::endsWith('HelixPHP', 'PHP'));
        $this->assertFalse(Str::endsWith('HelixPHP', 'Express'));
    }

    public function testContains(): void
    {
        $this->assertTrue(Str::contains('HelixPHP Framework', 'PHP'));
        $this->assertFalse(Str::contains('HelixPHP Framework', 'Laravel'));
    }

    public function testUcfirst(): void
    {
        $this->assertEquals('Express', Str::ucfirst('express'));
        $this->assertEquals('ÁBCD', Str::ucfirst('áBCD'));
    }

    public function testTitle(): void
    {
        $this->assertEquals('Express Php Framework', Str::title('express php framework'));
    }

    public function testAscii(): void
    {
        $this->assertEquals('Joao', Str::ascii('João'));
        $this->assertEquals('cafe', Str::ascii('café'));
        $this->assertEquals('nao', Str::ascii('não'));
    }
}
