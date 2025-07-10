<?php

namespace PivotPHP\Core\Tests\Json\Pool;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Json\Pool\JsonBuffer;

class JsonBufferTest extends TestCase
{
    public function testBufferCreation(): void
    {
        $buffer = new JsonBuffer(1024);

        $this->assertEquals(1024, $buffer->getCapacity());
        $this->assertEquals(0, $buffer->getSize());
        $this->assertEquals(0, $buffer->getUtilization());
        $this->assertTrue($buffer->hasSpace(500));
    }

    public function testAppendString(): void
    {
        $buffer = new JsonBuffer(1024);
        $buffer->append('{"test":');
        $buffer->append('"value"}');

        $result = $buffer->finalize();
        $this->assertEquals('{"test":"value"}', $result);
        $this->assertEquals(16, $buffer->getSize()); // Corrected expected size
    }

    public function testAppendJson(): void
    {
        $buffer = new JsonBuffer(1024);
        $data = ['name' => 'test', 'value' => 123];

        $buffer->appendJson($data);
        $result = $buffer->finalize();

        $this->assertEquals('{"name":"test","value":123}', $result);
    }

    public function testBufferReset(): void
    {
        $buffer = new JsonBuffer(1024);
        $buffer->append('some data');

        $this->assertEquals(9, $buffer->getSize());

        $buffer->reset();

        $this->assertEquals(0, $buffer->getSize());
        $this->assertEquals(0, $buffer->getUtilization());
        $this->assertTrue($buffer->hasSpace(100)); // Fixed test assertion
    }

    public function testBufferExpansion(): void
    {
        $buffer = new JsonBuffer(10); // Small initial capacity
        $largeData = str_repeat('x', 100);

        $buffer->append($largeData);

        $this->assertGreaterThanOrEqual(100, $buffer->getCapacity());
        $this->assertEquals(100, $buffer->getSize());
    }

    public function testUtilizationCalculation(): void
    {
        $buffer = new JsonBuffer(100);
        $buffer->append('12345'); // 5 bytes

        $this->assertEquals(5.0, $buffer->getUtilization());
        $this->assertEquals(95, $buffer->getRemainingSpace());
    }

    public function testInvalidJsonEncoding(): void
    {
        $buffer = new JsonBuffer(1024);

        // Test with invalid UTF-8 sequence
        $this->expectException(\InvalidArgumentException::class);
        $buffer->appendJson("\xB1\x31");
    }

    public function testComplexJsonStructure(): void
    {
        $buffer = new JsonBuffer(1024);

        $complexData = [
            'users' => [
                ['id' => 1, 'name' => 'João'],
                ['id' => 2, 'name' => 'Maria']
            ],
            'metadata' => [
                'total' => 2,
                'page' => 1
            ]
        ];

        $buffer->appendJson($complexData);
        $result = $buffer->finalize();

        // Verify that the JSON is valid
        $decoded = json_decode($result, true);
        $this->assertEquals($complexData, $decoded);
    }

    public function testMultipleAppendOperations(): void
    {
        $buffer = new JsonBuffer(1024);

        $buffer->append('{"users":[');
        $buffer->appendJson(['id' => 1, 'name' => 'User1']);
        $buffer->append(',');
        $buffer->appendJson(['id' => 2, 'name' => 'User2']);
        $buffer->append(']}');

        $result = $buffer->finalize();
        $expected = '{"users":[{"id":1,"name":"User1"},{"id":2,"name":"User2"}]}';

        $this->assertEquals($expected, $result);
    }
}
