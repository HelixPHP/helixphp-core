<?php

namespace PivotPHP\Core\Tests\Support;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Support\Arr;

class ArrTest extends TestCase
{
    private array $testArray;

    protected function setUp(): void
    {
        $this->testArray = [
            'user' => [
                'name' => 'João',
                'profile' => [
                    'age' => 30,
                    'city' => 'São Paulo'
                ]
            ],
            'settings' => [
                'theme' => 'dark'
            ]
        ];
    }

    public function testGet(): void
    {
        $this->assertEquals('João', Arr::get($this->testArray, 'user.name'));
        $this->assertEquals(30, Arr::get($this->testArray, 'user.profile.age'));
        $this->assertEquals('São Paulo', Arr::get($this->testArray, 'user.profile.city'));
        $this->assertEquals('default', Arr::get($this->testArray, 'missing.key', 'default'));
    }

    public function testSet(): void
    {
        $array = [];
        Arr::set($array, 'user.name', 'Maria');
        Arr::set($array, 'user.profile.age', 25);

        $this->assertEquals('Maria', $array['user']['name']);
        $this->assertEquals(25, $array['user']['profile']['age']);
    }

    public function testHas(): void
    {
        $this->assertTrue(Arr::has($this->testArray, 'user.name'));
        $this->assertTrue(Arr::has($this->testArray, 'user.profile.city'));
        $this->assertFalse(Arr::has($this->testArray, 'user.email'));
        $this->assertFalse(Arr::has($this->testArray, 'missing.key'));
    }

    public function testForget(): void
    {
        $array = $this->testArray;
        Arr::forget($array, 'user.profile.age');

        $this->assertFalse(isset($array['user']['profile']['age']));
        $this->assertTrue(isset($array['user']['profile']['city']));
    }

    public function testFlatten(): void
    {
        $flattened = Arr::flatten($this->testArray);

        $expected = [
            'user.name' => 'João',
            'user.profile.age' => 30,
            'user.profile.city' => 'São Paulo',
            'settings.theme' => 'dark'
        ];

        $this->assertEquals($expected, $flattened);
    }

    public function testOnly(): void
    {
        $result = Arr::only($this->testArray, ['user']);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayNotHasKey('settings', $result);
    }

    public function testExcept(): void
    {
        $result = Arr::except($this->testArray, ['settings']);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayNotHasKey('settings', $result);
    }

    public function testChunk(): void
    {
        $array = ['a', 'b', 'c', 'd', 'e'];
        $chunks = Arr::chunk($array, 2);

        $this->assertCount(3, $chunks);
        $this->assertEquals([0 => 'a', 1 => 'b'], $chunks[0]);
        $this->assertEquals([2 => 'c', 3 => 'd'], $chunks[1]);
        $this->assertEquals([4 => 'e'], $chunks[2]);
    }

    public function testIsAssoc(): void
    {
        $this->assertTrue(Arr::isAssoc(['name' => 'João', 'age' => 30]));
        $this->assertFalse(Arr::isAssoc(['a', 'b', 'c']));
        $this->assertFalse(Arr::isAssoc([0 => 'a', 1 => 'b', 2 => 'c']));
    }

    public function testShuffle(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        $shuffled = Arr::shuffle($array);

        $this->assertCount(3, $shuffled);
        $this->assertArrayHasKey('a', $shuffled);
        $this->assertArrayHasKey('b', $shuffled);
        $this->assertArrayHasKey('c', $shuffled);
        $this->assertEquals(1, $shuffled['a']);
        $this->assertEquals(2, $shuffled['b']);
        $this->assertEquals(3, $shuffled['c']);
    }
}
