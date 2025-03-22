<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Infrastructure\Http\Controllers\Api;

use Illuminate\Foundation\Testing\WithFaker;
use Modules\Invoices\Api\Dtos\InvoiceDto;
use Modules\Invoices\Api\InvoiceFacadeInterface;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidProductLineException;
use Tests\InMemoryDatabaseTrait;
use Tests\TestCase;

final class ProductLineControllerTest extends TestCase
{
    use InMemoryDatabaseTrait, WithFaker;
    
    public function testStore(): void
    {
        // Arrange
        $invoiceId = $this->faker->uuid;
        $invoiceDto = $this->createInvoiceDto($invoiceId);
        $requestData = [
            'product_name' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->numberBetween(100, 1000),
        ];
        
        $this->mock(InvoiceFacadeInterface::class)
            ->shouldReceive('addProductLine')
            ->once()
            ->with(
                $invoiceId,
                $requestData['product_name'],
                $requestData['quantity'],
                $requestData['unit_price']
            )
            ->andReturn($invoiceDto);

        // Act
        $response = $this->postJson("/api/invoices/{$invoiceId}/product-lines", $requestData);

        // Assert
        $response->assertCreated()
            ->assertJson([
                'message' => 'Product line successfully added',
                'data' => [
                    'id' => $invoiceDto->id,
                    'customerName' => $invoiceDto->customerName,
                    'customerEmail' => $invoiceDto->customerEmail,
                    'status' => $invoiceDto->status,
                ]
            ]);
    }
    
    public function testStoreReturnsNotFoundWhenInvoiceDoesNotExist(): void
    {
        // Arrange
        $invoiceId = $this->faker->uuid;
        $requestData = [
            'product_name' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->numberBetween(100, 1000),
        ];
        
        $this->mock(InvoiceFacadeInterface::class)
            ->shouldReceive('addProductLine')
            ->once()
            ->with(
                $invoiceId,
                $requestData['product_name'],
                $requestData['quantity'],
                $requestData['unit_price']
            )
            ->andReturn(null);

        // Act
        $response = $this->postJson("/api/invoices/{$invoiceId}/product-lines", $requestData);

        // Assert
        $response->assertNotFound()
            ->assertJson([
                'error' => 'Invoice not found'
            ]);
    }
    
    public function testStoreReturnsBadRequestWhenProductLineIsInvalid(): void
    {
        // Arrange
        $invoiceId = $this->faker->uuid;
        $errorMessage = 'Invalid product line: quantity must be positive';
        $requestData = [
            'product_name' => $this->faker->word,
            'quantity' => 1, // Valid quantity for validation, the mock will throw the exception
            'unit_price' => $this->faker->numberBetween(100, 1000),
        ];
        
        $this->mock(InvoiceFacadeInterface::class)
            ->shouldReceive('addProductLine')
            ->once()
            ->with(
                $invoiceId,
                $requestData['product_name'],
                $requestData['quantity'],
                $requestData['unit_price']
            )
            ->andThrow(new InvalidProductLineException($errorMessage));

        // Act
        $response = $this->postJson("/api/invoices/{$invoiceId}/product-lines", $requestData);

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'error' => $errorMessage
            ]);
    }
    
    private function createInvoiceDto(string $id): InvoiceDto
    {
        return new InvoiceDto(
            $id,
            StatusEnum::Draft->value,
            $this->faker->name,
            $this->faker->email,
            [],
            0
        );
    }
} 