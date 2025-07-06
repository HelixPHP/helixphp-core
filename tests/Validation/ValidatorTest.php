<?php

namespace Helix\Tests\Validation;

use PHPUnit\Framework\TestCase;
use Helix\Validation\Validator;

class ValidatorTest extends TestCase
{
    public function testValidationPasses(): void
    {
        $data = [
            'name' => 'João',
            'email' => 'joao@teste.com',
            'age' => 25
        ];

        $rules = [
            'name' => 'required|string|min:2',
            'email' => 'required|email',
            'age' => 'integer|min:18'
        ];

        $validator = new Validator($rules);
        $this->assertTrue($validator->validate($data));
        $this->assertEmpty($validator->getErrors());
    }

    public function testValidationFails(): void
    {
        $data = [
            'name' => '',
            'email' => 'invalid-email',
            'age' => 15
        ];

        $rules = [
            'name' => 'required|string|min:2',
            'email' => 'required|email',
            'age' => 'integer|min:18'
        ];

        $validator = new Validator($rules);
        $this->assertFalse($validator->validate($data));
        $this->assertNotEmpty($validator->getErrors());
        $this->assertArrayHasKey('name', $validator->getErrors());
        $this->assertArrayHasKey('email', $validator->getErrors());
        $this->assertArrayHasKey('age', $validator->getErrors());
    }

    public function testCustomMessages(): void
    {
        $data = ['name' => ''];

        $rules = ['name' => 'required'];

        $messages = ['name.required' => 'Nome é obrigatório!'];

        $validator = new Validator($rules, $messages);
        $validator->validate($data);

        $errors = $validator->getErrors();
        $this->assertEquals('Nome é obrigatório!', $errors['name'][0]);
    }

    public function testFactoryMethod(): void
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'required|email'];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->validate($data));
    }

    public function testNumericValidation(): void
    {
        $validator = new Validator(['score' => 'numeric']);

        $this->assertTrue($validator->validate(['score' => '123']));
        $this->assertTrue($validator->validate(['score' => 123]));
        $this->assertTrue($validator->validate(['score' => 123.45]));
        $this->assertFalse($validator->validate(['score' => 'abc']));
    }

    public function testInValidation(): void
    {
        $validator = new Validator(['status' => 'in:active,inactive,pending']);

        $this->assertTrue($validator->validate(['status' => 'active']));
        $this->assertTrue($validator->validate(['status' => 'pending']));
        $this->assertFalse($validator->validate(['status' => 'unknown']));
    }

    public function testGetFirstError(): void
    {
        $data = ['name' => '', 'email' => 'invalid'];
        $rules = ['name' => 'required', 'email' => 'email'];

        $validator = new Validator($rules);
        $validator->validate($data);

        $firstError = $validator->getFirstError();
        $this->assertNotNull($firstError);
        $this->assertStringContainsString('obrigatório', $firstError);
    }
}
