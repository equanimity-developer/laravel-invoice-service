<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Presentation\Requests;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Modules\Invoices\Presentation\Requests\AddProductLineRequest;
use Tests\TestCase;

final class AddProductLineRequestTest extends TestCase
{
    use WithFaker;

    private AddProductLineRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFaker();
        $this->request = new AddProductLineRequest();
    }

    public function testValidationPassesWithValidData(): void
    {
        // Arrange
        $rules = $this->request->rules();
        $data = [
            'product_name' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->numberBetween(100, 1000),
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function testValidationFailsWithEmptyProductName(): void
    {
        // Arrange
        $rules = $this->request->rules();
        $data = [
            'product_name' => '',
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->numberBetween(100, 1000),
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('product_name', $validator->errors()->toArray());
    }

    public function testValidationFailsWithMissingProductName(): void
    {
        // Arrange
        $rules = $this->request->rules();
        $data = [
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->numberBetween(100, 1000),
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('product_name', $validator->errors()->toArray());
    }

    public function testValidationFailsWithNegativeQuantity(): void
    {
        // Arrange
        $rules = $this->request->rules();
        $data = [
            'product_name' => $this->faker->word,
            'quantity' => -1,
            'unit_price' => $this->faker->numberBetween(100, 1000),
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('quantity', $validator->errors()->toArray());
    }

    public function testValidationFailsWithZeroQuantity(): void
    {
        // Arrange
        $rules = $this->request->rules();
        $data = [
            'product_name' => $this->faker->word,
            'quantity' => 0,
            'unit_price' => $this->faker->numberBetween(100, 1000),
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('quantity', $validator->errors()->toArray());
    }

    public function testValidationFailsWithMissingQuantity(): void
    {
        // Arrange
        $rules = $this->request->rules();
        $data = [
            'product_name' => $this->faker->word,
            'unit_price' => $this->faker->numberBetween(100, 1000),
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('quantity', $validator->errors()->toArray());
    }

    public function testValidationFailsWithNegativeUnitPrice(): void
    {
        // Arrange
        $rules = $this->request->rules();
        $data = [
            'product_name' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => -1,
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('unit_price', $validator->errors()->toArray());
    }

    public function testValidationFailsWithZeroUnitPrice(): void
    {
        // Arrange
        $rules = $this->request->rules();
        $data = [
            'product_name' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => 0,
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('unit_price', $validator->errors()->toArray());
    }

    public function testValidationFailsWithMissingUnitPrice(): void
    {
        // Arrange
        $rules = $this->request->rules();
        $data = [
            'product_name' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 10),
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('unit_price', $validator->errors()->toArray());
    }
} 