<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Utils;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Utils\Arr;

class ArrTest extends TestCase
{
    public function testGet(): void
    {
        $array = [
            'user' => [
                'profile' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com'
                ],
                'settings' => [
                    'theme' => 'dark'
                ]
            ],
            'config' => [
                'debug' => true
            ]
        ];

        // Test simple key access
        $this->assertEquals(
            ['profile' => ['name' => 'John Doe', 'email' => 'john@example.com'], 'settings' => ['theme' => 'dark']],
            Arr::get($array, 'user')
        );
        $this->assertEquals(['debug' => true], Arr::get($array, 'config'));

        // Test dot notation access
        $this->assertEquals('John Doe', Arr::get($array, 'user.profile.name'));
        $this->assertEquals('john@example.com', Arr::get($array, 'user.profile.email'));
        $this->assertEquals('dark', Arr::get($array, 'user.settings.theme'));
        $this->assertEquals(true, Arr::get($array, 'config.debug'));

        // Test default values
        $this->assertEquals('default', Arr::get($array, 'nonexistent', 'default'));
        $this->assertEquals('default', Arr::get($array, 'user.nonexistent.key', 'default'));
        $this->assertNull(Arr::get($array, 'nonexistent'));

        // Test direct key exists (no dot notation needed)
        $array2 = ['user.profile' => 'direct_value'];
        $this->assertEquals('direct_value', Arr::get($array2, 'user.profile'));
    }

    public function testSet(): void
    {
        $array = [];

        // Test simple set
        Arr::set($array, 'name', 'John');
        $this->assertEquals(['name' => 'John'], $array);

        // Test dot notation set
        Arr::set($array, 'user.profile.name', 'Jane Doe');
        $this->assertEquals('Jane Doe', $array['user']['profile']['name']);

        // Test overwriting existing values
        Arr::set($array, 'user.profile.email', 'jane@example.com');
        $this->assertEquals('jane@example.com', $array['user']['profile']['email']);

        // Test deep nesting
        Arr::set($array, 'a.b.c.d.e', 'deep_value');
        $this->assertEquals('deep_value', $array['a']['b']['c']['d']['e']);

        // Test overwriting non-array values
        $array2 = ['key' => 'string_value'];
        Arr::set($array2, 'key.subkey', 'new_value');
        $this->assertEquals(['subkey' => 'new_value'], $array2['key']);
    }

    public function testHas(): void
    {
        $array = [
            'user' => [
                'profile' => [
                    'name' => 'John Doe',
                    'email' => null
                ]
            ],
            'config' => true,
            'empty_array' => []
        ];

        // Test simple key existence
        $this->assertTrue(Arr::has($array, 'user'));
        $this->assertTrue(Arr::has($array, 'config'));
        $this->assertTrue(Arr::has($array, 'empty_array'));
        $this->assertFalse(Arr::has($array, 'nonexistent'));

        // Test dot notation existence
        $this->assertTrue(Arr::has($array, 'user.profile.name'));
        $this->assertTrue(Arr::has($array, 'user.profile.email')); // null values should still return true
        $this->assertFalse(Arr::has($array, 'user.profile.age'));
        $this->assertFalse(Arr::has($array, 'user.nonexistent.key'));

        // Test direct key exists (no dot notation needed)
        $array2 = ['user.profile' => 'direct_value'];
        $this->assertTrue(Arr::has($array2, 'user.profile'));
    }

    public function testForget(): void
    {
        $array = [
            'user' => [
                'profile' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com'
                ],
                'settings' => [
                    'theme' => 'dark'
                ]
            ],
            'config' => true
        ];

        // Test simple key removal
        Arr::forget($array, 'config');
        $this->assertFalse(isset($array['config']));

        // Test dot notation removal
        Arr::forget($array, 'user.profile.email');
        $this->assertFalse(isset($array['user']['profile']['email']));
        $this->assertTrue(isset($array['user']['profile']['name'])); // Other keys should remain

        // Test removing entire nested structure
        Arr::forget($array, 'user.settings');
        $this->assertFalse(isset($array['user']['settings']));

        // Test removing non-existent keys (should not error)
        Arr::forget($array, 'nonexistent');
        Arr::forget($array, 'user.nonexistent.key');

        // Test removing from non-array path
        $array2 = ['key' => 'string_value'];
        Arr::forget($array2, 'key.subkey'); // Should not error
        $this->assertEquals('string_value', $array2['key']);
    }

    public function testDot(): void
    {
        $array = [
            'user' => [
                'profile' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com'
                ],
                'settings' => [
                    'theme' => 'dark'
                ]
            ],
            'config' => true,
            'empty_array' => [],
            'simple' => 'value'
        ];

        $expected = [
            'user.profile.name' => 'John Doe',
            'user.profile.email' => 'john@example.com',
            'user.settings.theme' => 'dark',
            'config' => true,
            'empty_array' => [],
            'simple' => 'value'
        ];

        $this->assertEquals($expected, Arr::dot($array));

        // Test with prepend
        $result = Arr::dot(['a' => ['b' => 'value']], 'prefix');
        $this->assertEquals(['prefix.a.b' => 'value'], $result);

        // Test empty array
        $this->assertEquals([], Arr::dot([]));
    }

    public function testUndot(): void
    {
        $flatArray = [
            'user.profile.name' => 'John Doe',
            'user.profile.email' => 'john@example.com',
            'user.settings.theme' => 'dark',
            'config' => true,
            'simple' => 'value'
        ];

        $expected = [
            'user' => [
                'profile' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com'
                ],
                'settings' => [
                    'theme' => 'dark'
                ]
            ],
            'config' => true,
            'simple' => 'value'
        ];

        $this->assertEquals($expected, Arr::undot($flatArray));

        // Test empty array
        $this->assertEquals([], Arr::undot([]));
    }

    public function testOnly(): void
    {
        $array = [
            'name' => 'John',
            'email' => 'john@example.com',
            'age' => 30,
            'city' => 'New York'
        ];

        // Test with array of keys
        $result = Arr::only($array, ['name', 'email']);
        $this->assertEquals(['name' => 'John', 'email' => 'john@example.com'], $result);

        // Test with single key as string - Note: func_get_args() behavior
        $result = Arr::only($array, ['name']); // Use array instead
        $this->assertEquals(['name' => 'John'], $result);

        // Test with non-existent keys
        $result = Arr::only($array, ['name', 'nonexistent']);
        $this->assertEquals(['name' => 'John'], $result);

        // Test with empty keys
        $result = Arr::only($array, []);
        $this->assertEquals([], $result);

        // Test with numeric keys
        $numericArray = [0 => 'first', 1 => 'second', 2 => 'third'];
        $result = Arr::only($numericArray, [0, 2]);
        $this->assertEquals([0 => 'first', 2 => 'third'], $result);

        // Test with invalid keys (non-string/int)
        $result = Arr::only($array, [['invalid'], null, true]);
        $this->assertEquals([], $result);

        // Test with non-array keys parameter
        $result = Arr::only($array, 'string_not_array');
        $this->assertEquals([], $result);
    }

    public function testExcept(): void
    {
        $array = [
            'name' => 'John',
            'email' => 'john@example.com',
            'age' => 30,
            'city' => 'New York'
        ];

        // Test with array of keys
        $result = Arr::except($array, ['age', 'city']);
        $this->assertEquals(['name' => 'John', 'email' => 'john@example.com'], $result);

        // Test with single key as string - Note: func_get_args() behavior
        $result = Arr::except($array, ['name']); // Use array instead
        $this->assertEquals(['email' => 'john@example.com', 'age' => 30, 'city' => 'New York'], $result);

        // Test with non-existent keys
        $result = Arr::except($array, ['nonexistent']);
        $this->assertEquals($array, $result);

        // Test with empty keys
        $result = Arr::except($array, []);
        $this->assertEquals($array, $result);

        // Test with numeric keys
        $numericArray = [0 => 'first', 1 => 'second', 2 => 'third'];
        $result = Arr::except($numericArray, [1]);
        $this->assertEquals([0 => 'first', 2 => 'third'], $result);

        // Test with invalid keys (non-string/int)
        $result = Arr::except($array, [['invalid'], null, true]);
        $this->assertEquals($array, $result);

        // Test with non-array keys parameter
        $result = Arr::except($array, 'string_not_array');
        $this->assertEquals($array, $result);
    }

    public function testFirst(): void
    {
        // Test with non-empty array
        $array = ['first', 'second', 'third'];
        $this->assertEquals('first', Arr::first($array));

        // Test with associative array
        $assocArray = ['a' => 'first', 'b' => 'second'];
        $this->assertEquals('first', Arr::first($assocArray));

        // Test with empty array
        $this->assertNull(Arr::first([]));
        $this->assertEquals('default', Arr::first([], 'default'));

        // Test with single element
        $this->assertEquals('only', Arr::first(['only']));
    }

    public function testLast(): void
    {
        // Test with non-empty array
        $array = ['first', 'second', 'third'];
        $this->assertEquals('third', Arr::last($array));

        // Test with associative array
        $assocArray = ['a' => 'first', 'b' => 'second'];
        $this->assertEquals('second', Arr::last($assocArray));

        // Test with empty array
        $this->assertNull(Arr::last([]));
        $this->assertEquals('default', Arr::last([], 'default'));

        // Test with single element
        $this->assertEquals('only', Arr::last(['only']));
    }

    public function testIsAssoc(): void
    {
        // Test associative arrays
        $this->assertTrue(Arr::isAssoc(['a' => 1, 'b' => 2]));
        $this->assertTrue(Arr::isAssoc([1 => 'first', 3 => 'third'])); // Non-sequential numeric keys
        $this->assertTrue(Arr::isAssoc(['name' => 'John', 0 => 'mixed']));

        // Test non-associative (indexed) arrays
        $this->assertFalse(Arr::isAssoc([1, 2, 3]));
        $this->assertFalse(Arr::isAssoc(['first', 'second', 'third']));
        $this->assertFalse(Arr::isAssoc([0 => 'first', 1 => 'second', 2 => 'third']));

        // Test empty array
        $this->assertFalse(Arr::isAssoc([]));

        // Test single element arrays
        $this->assertFalse(Arr::isAssoc(['single'])); // 0 => 'single'
        $this->assertTrue(Arr::isAssoc(['key' => 'value']));
    }

    public function testMergeRecursive(): void
    {
        $array1 = [
            'user' => [
                'name' => 'John',
                'settings' => [
                    'theme' => 'light',
                    'notifications' => true
                ]
            ],
            'config' => [
                'debug' => true
            ]
        ];

        $array2 = [
            'user' => [
                'email' => 'john@example.com',
                'settings' => [
                    'theme' => 'dark', // Override
                    'language' => 'en' // New key
                ]
            ],
            'app' => [
                'version' => '1.0'
            ]
        ];

        $expected = [
            'user' => [
                'name' => 'John',
                'email' => 'john@example.com',
                'settings' => [
                    'theme' => 'dark', // Overridden
                    'notifications' => true,
                    'language' => 'en' // Added
                ]
            ],
            'config' => [
                'debug' => true
            ],
            'app' => [
                'version' => '1.0'
            ]
        ];

        $this->assertEquals($expected, Arr::mergeRecursive($array1, $array2));

        // Test with non-array values being overridden
        $simple1 = ['key' => 'value1'];
        $simple2 = ['key' => 'value2'];
        $this->assertEquals(['key' => 'value2'], Arr::mergeRecursive($simple1, $simple2));

        // Test empty arrays
        $this->assertEquals($array1, Arr::mergeRecursive($array1, []));
        $this->assertEquals($array2, Arr::mergeRecursive([], $array2));
    }

    public function testMapWithKeys(): void
    {
        $array = ['a', 'b', 'c'];

        // Test mapping with new keys
        $result = Arr::mapWithKeys(
            $array,
            function ($value, $key) {
                return ['key_' . $key => strtoupper($value)];
            }
        );

        $expected = [
            'key_0' => 'A',
            'key_1' => 'B',
            'key_2' => 'C'
        ];

        $this->assertEquals($expected, $result);

        // Test with associative array
        $assocArray = ['first' => 'john', 'last' => 'doe'];
        $result = Arr::mapWithKeys(
            $assocArray,
            function ($value, $key) {
                return [$key . '_upper' => strtoupper($value)];
            }
        );

        $expected = [
            'first_upper' => 'JOHN',
            'last_upper' => 'DOE'
        ];

        $this->assertEquals($expected, $result);

        // Test callback returning multiple key-value pairs
        $result = Arr::mapWithKeys(
            ['a'],
            function ($value, $key) {
                return [
                    'key1_' . $key => $value . '1',
                    'key2_' . $key => $value . '2'
                ];
            }
        );

        $expected = [
            'key1_0' => 'a1',
            'key2_0' => 'a2'
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGroupBy(): void
    {
        // Test grouping by string key
        $users = [
            ['name' => 'John', 'role' => 'admin'],
            ['name' => 'Jane', 'role' => 'user'],
            ['name' => 'Bob', 'role' => 'admin'],
            ['name' => 'Alice', 'role' => 'user']
        ];

        $result = Arr::groupBy($users, 'role');
        $expected = [
            'admin' => [
                ['name' => 'John', 'role' => 'admin'],
                ['name' => 'Bob', 'role' => 'admin']
            ],
            'user' => [
                ['name' => 'Jane', 'role' => 'user'],
                ['name' => 'Alice', 'role' => 'user']
            ]
        ];

        $this->assertEquals($expected, $result);

        // Test grouping by callback
        $numbers = [1, 2, 3, 4, 5, 6];
        $result = Arr::groupBy(
            $numbers,
            function ($item) {
                return $item % 2 === 0 ? 'even' : 'odd';
            }
        );

        $expected = [
            'odd' => [1, 3, 5],
            'even' => [2, 4, 6]
        ];

        $this->assertEquals($expected, $result);

        // Test grouping with dot notation
        $data = [
            ['user' => ['type' => 'premium']],
            ['user' => ['type' => 'basic']],
            ['user' => ['type' => 'premium']]
        ];

        $result = Arr::groupBy($data, 'user.type');
        $expected = [
            'premium' => [
                ['user' => ['type' => 'premium']],
                ['user' => ['type' => 'premium']]
            ],
            'basic' => [
                ['user' => ['type' => 'basic']]
            ]
        ];

        $this->assertEquals($expected, $result);

        // Test grouping non-array items
        $mixed = ['string', 123, true];
        $result = Arr::groupBy($mixed, 'nonexistent_key');
        $expected = [
            'unknown' => ['string', 123, true]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testFlatten(): void
    {
        // Test unlimited depth flattening
        $array = [
            'a' => [
                'b' => [
                    'c' => 'value1',
                    'd' => 'value2'
                ]
            ],
            'e' => 'value3'
        ];

        $result = Arr::flatten($array);
        $expected = [
            'a.b.c' => 'value1',
            'a.b.d' => 'value2',
            'e' => 'value3'
        ];

        $this->assertEquals($expected, $result);

        // Test with depth limit
        $result = Arr::flatten($array, 1);
        $expected = [
            // Depth 1 means only flatten 1 level, but our data needs depth 2
            'a' => ['b' => ['c' => 'value1', 'd' => 'value2']],
            'e' => 'value3'
        ];

        $this->assertEquals($expected, $result);

        // Test with depth 2
        $result = Arr::flatten($array, 2);
        $expected = [
            'a.b' => ['c' => 'value1', 'd' => 'value2'], // Depth 2 still flattens to this level
            'e' => 'value3'
        ];

        $this->assertEquals($expected, $result);

        // Test empty array
        $this->assertEquals([], Arr::flatten([]));

        // Test flat array (no nesting)
        $flat = ['a' => 1, 'b' => 2];
        $this->assertEquals($flat, Arr::flatten($flat));
    }

    public function testChunk(): void
    {
        $array = ['a', 'b', 'c', 'd', 'e', 'f'];

        // Test chunking with preserved keys
        $result = Arr::chunk($array, 2);
        $expected = [
            [0 => 'a', 1 => 'b'],
            [2 => 'c', 3 => 'd'],
            [4 => 'e', 5 => 'f']
        ];

        $this->assertEquals($expected, $result);

        // Test chunking without preserved keys
        $result = Arr::chunk($array, 2, false);
        $expected = [
            ['a', 'b'],
            ['c', 'd'],
            ['e', 'f']
        ];

        $this->assertEquals($expected, $result);

        // Test chunking with uneven division
        $result = Arr::chunk($array, 4);
        $expected = [
            [0 => 'a', 1 => 'b', 2 => 'c', 3 => 'd'],
            [4 => 'e', 5 => 'f']
        ];

        $this->assertEquals($expected, $result);

        // Test with associative array
        $assocArray = ['name' => 'John', 'email' => 'john@example.com', 'age' => 30];
        $result = Arr::chunk($assocArray, 2);
        $expected = [
            ['name' => 'John', 'email' => 'john@example.com'],
            ['age' => 30]
        ];

        $this->assertEquals($expected, $result);

        // Test with invalid size
        $this->assertEquals([], Arr::chunk($array, 0));
        $this->assertEquals([], Arr::chunk($array, -1));

        // Test empty array
        $this->assertEquals([], Arr::chunk([], 2));
    }

    public function testShuffle(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5];

        $result = Arr::shuffle($array);

        // Test that all keys are preserved (but order may change)
        $originalKeys = array_keys($array);
        $resultKeys = array_keys($result);
        sort($originalKeys);
        sort($resultKeys);
        $this->assertEquals($originalKeys, $resultKeys);

        // Test that all values are preserved (but order may change)
        $originalValues = array_values($array);
        $resultValues = array_values($result);
        sort($originalValues);
        sort($resultValues);
        $this->assertEquals($originalValues, $resultValues);

        // Test that the function returns an array with same size
        $this->assertEquals(count($array), count($result));

        // Test empty array
        $this->assertEquals([], Arr::shuffle([]));

        // Test single element array
        $single = ['key' => 'value'];
        $this->assertEquals($single, Arr::shuffle($single));

        // Test numeric keys are preserved
        $numericArray = [0 => 'zero', 1 => 'one', 2 => 'two'];
        $result = Arr::shuffle($numericArray);

        $expectedKeys = [0, 1, 2];
        $resultKeys = array_keys($result);
        sort($expectedKeys);
        sort($resultKeys);
        $this->assertEquals($expectedKeys, $resultKeys);

        $expectedValues = ['zero', 'one', 'two'];
        $resultValues = array_values($result);
        sort($expectedValues);
        sort($resultValues);
        $this->assertEquals($expectedValues, $resultValues);
    }
}
