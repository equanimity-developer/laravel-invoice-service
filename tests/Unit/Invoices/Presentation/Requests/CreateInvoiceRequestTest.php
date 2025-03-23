<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Presentation\Requests;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Modules\Invoices\Presentation\Requests\CreateInvoiceRequest;
use Tests\TestCase;

final class CreateInvoiceRequestTest extends TestCase
{
    use WithFaker;

    private CreateInvoiceRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFaker();
        $this->request = new CreateInvoiceRequest();
    }

    public function testValidationPassesWithValidData(): void
    {
        // Arrange
        $rules = $this->request->rules();
        $data = [
            'customer_name' => $this->faker->name,
            'customer_email' => $this->faker->email,
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function testValidationFailsWithEmptyCustomerName(): void
    {
        // Arrange
        $rules = $this->request->rules();
        $data = [
            'customer_name' => '',
            'customer_email' => $this->faker->email,
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('customer_name', $validator->errors()->toArray());
    }

    public function testValidationFailsWithMissingCustomerName(): void
    {
        // Arrange
        $rules = $this->request->rules();
        $data = [
            'customer_email' => $this->faker->email,
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('customer_name', $validator->errors()->toArray());
    }

    public function testValidationFailsWithInvalidEmail(): void
    {
        // Arrange
        $rules = $this->request->rules();
        $data = [
            'customer_name' => $this->faker->name,
            'customer_email' => 'not-a-valid-email',
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('customer_email', $validator->errors()->toArray());
    }

    public function testValidationFailsWithMissingEmail(): void
    {
        // Arrange
        $rules = $this->request->rules();
        $data = [
            'customer_name' => $this->faker->name,
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('customer_email', $validator->errors()->toArray());
    }
} 