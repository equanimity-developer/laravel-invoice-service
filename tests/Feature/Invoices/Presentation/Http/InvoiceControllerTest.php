<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Presentation\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Invoices\Application\Dtos\InvoiceDto;
use Modules\Invoices\Application\Dtos\ProductLineDto;
use Modules\Invoices\Application\Services\InvoiceServiceInterface;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStatusTransitionException;
use Modules\Invoices\Domain\Exceptions\InvalidProductLineException;
use Modules\Invoices\Presentation\Requests\AddProductLineRequest;
use Modules\Invoices\Presentation\Requests\CreateInvoiceRequest;
use Symfony\Component\HttpFoundation\Response;
use Tests\InMemoryDatabaseTrait;
use Tests\TestCase;
use Faker\Factory;
use Faker\Generator;

final class InvoiceControllerTest extends TestCase
{
    use InMemoryDatabaseTrait, WithFaker;

    public function testIndex(): void
    {
        // Arrange
        $invoices = [
            $this->createInvoiceDto(),
            $this->createInvoiceDto(),
        ];

        $this->mock(InvoiceServiceInterface::class)
            ->shouldReceive('getAllInvoices')
            ->once()
            ->andReturn($invoices);

        // Act
        $response = $this->getJson('/api/invoices');

        // Assert
        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'customerName',
                    'customerEmail',
                    'status',
                    'productLines',
                    'totalPrice'
                ]
            ]);
    }

    public function testShow(): void
    {
        // Arrange
        $invoiceDto = $this->createInvoiceDto();

        $this->mock(InvoiceServiceInterface::class)
            ->shouldReceive('getInvoice')
            ->once()
            ->with($invoiceDto->id)
            ->andReturn($invoiceDto);

        // Act
        $response = $this->getJson("/api/invoices/{$invoiceDto->id}");

        // Assert
        $response->assertOk()
            ->assertJson([
                'id' => $invoiceDto->id,
                'customerName' => $invoiceDto->customerName,
                'customerEmail' => $invoiceDto->customerEmail,
                'status' => $invoiceDto->status,
                'totalPrice' => $invoiceDto->totalPrice,
            ]);
    }

    public function testShowReturnsNotFoundWhenInvoiceDoesNotExist(): void
    {
        // Arrange
        $invoiceId = $this->faker->uuid;

        $this->mock(InvoiceServiceInterface::class)
            ->shouldReceive('getInvoice')
            ->once()
            ->with($invoiceId)
            ->andReturn(null);

        // Act
        $response = $this->getJson("/api/invoices/{$invoiceId}");

        // Assert
        $response->assertNotFound()
            ->assertJson([
                'error' => 'Invoice not found'
            ]);
    }

    public function testStore(): void
    {
        // Arrange
        $invoiceDto = $this->createInvoiceDto();
        $requestData = [
            'customer_name' => $this->faker->name,
            'customer_email' => $this->faker->email,
        ];

        $this->mock(InvoiceServiceInterface::class)
            ->shouldReceive('createInvoice')
            ->once()
            ->with($requestData['customer_name'], $requestData['customer_email'])
            ->andReturn($invoiceDto);

        // Act
        $response = $this->postJson('/api/invoices', $requestData);

        // Assert
        $response->assertCreated()
            ->assertJson([
                'message' => 'Invoice successfully created',
                'data' => [
                    'id' => $invoiceDto->id,
                    'status' => $invoiceDto->status,
                    'customerName' => $invoiceDto->customerName,
                    'customerEmail' => $invoiceDto->customerEmail,
                ]
            ]);
    }

    public function testStoreValidatesRequest(): void
    {
        // Arrange
        $requestData = [
            'customer_name' => '', // Empty name will fail validation
            'customer_email' => 'not-a-valid-email', // Invalid email
        ];

        // Act
        $response = $this->postJson('/api/invoices', $requestData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_name', 'customer_email']);
    }

    public function testAddProductLine(): void
    {
        // Arrange
        $invoiceId = $this->faker->uuid;
        $invoiceDto = $this->createInvoiceDto($invoiceId);
        $requestData = [
            'product_name' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->numberBetween(100, 1000),
        ];

        $this->mock(InvoiceServiceInterface::class)
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
                    'status' => $invoiceDto->status,
                    'customerName' => $invoiceDto->customerName,
                    'customerEmail' => $invoiceDto->customerEmail,
                ]
            ]);
    }

    public function testAddProductLineReturnsNotFoundWhenInvoiceDoesNotExist(): void
    {
        // Arrange
        $invoiceId = $this->faker->uuid;
        $requestData = [
            'product_name' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->numberBetween(100, 1000),
        ];

        $this->mock(InvoiceServiceInterface::class)
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

    public function testAddProductLineValidatesRequest(): void
    {
        // Arrange
        $invoiceId = $this->faker->uuid;
        $requestData = [
            'product_name' => '', // Empty name will fail validation
            'quantity' => 0, // Should be > 0
            'unit_price' => 0, // Should be > 0
        ];

        // Act
        $response = $this->postJson("/api/invoices/{$invoiceId}/product-lines", $requestData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_name', 'quantity', 'unit_price']);
    }

    public function testAddProductLineReturnsBadRequestWhenProductLineIsInvalid(): void
    {
        // Arrange
        $invoiceId = $this->faker->uuid;
        $errorMessage = 'Invalid product line: quantity must be positive';
        $requestData = [
            'product_name' => $this->faker->word,
            'quantity' => 1, // Valid for validation, but will trigger exception in service
            'unit_price' => $this->faker->numberBetween(100, 1000),
        ];
        
        $this->mock(InvoiceServiceInterface::class)
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

    public function testSend(): void
    {
        // Arrange
        $invoiceId = $this->faker->uuid;
        $invoiceDto = $this->createInvoiceDto($invoiceId, StatusEnum::Sending->value);

        $this->mock(InvoiceServiceInterface::class)
            ->shouldReceive('sendInvoice')
            ->once()
            ->with($invoiceId)
            ->andReturn($invoiceDto);

        // Act
        $response = $this->postJson("/api/invoices/{$invoiceId}/send");

        // Assert
        $response->assertOk()
            ->assertJson([
                'message' => 'Invoice has been sent successfully',
                'data' => [
                    'id' => $invoiceDto->id,
                    'customerName' => $invoiceDto->customerName,
                    'customerEmail' => $invoiceDto->customerEmail,
                    'status' => StatusEnum::Sending->value,
                ]
            ]);
    }

    public function testSendReturnsNotFoundWhenInvoiceDoesNotExist(): void
    {
        // Arrange
        $invoiceId = $this->faker->uuid;

        $this->mock(InvoiceServiceInterface::class)
            ->shouldReceive('sendInvoice')
            ->once()
            ->with($invoiceId)
            ->andReturn(null);

        // Act
        $response = $this->postJson("/api/invoices/{$invoiceId}/send");

        // Assert
        $response->assertNotFound()
            ->assertJson([
                'error' => 'Invoice not found'
            ]);
    }

    public function testSendReturnsBadRequestWhenInvoiceCannotBeSent(): void
    {
        // Arrange
        $invoiceId = $this->faker->uuid;
        $errorMessage = 'Cannot send invoice: invalid status transition';
        
        $this->mock(InvoiceServiceInterface::class)
            ->shouldReceive('sendInvoice')
            ->once()
            ->with($invoiceId)
            ->andThrow(new InvalidInvoiceStatusTransitionException($errorMessage));

        // Act
        $response = $this->postJson("/api/invoices/{$invoiceId}/send");

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'error' => $errorMessage
            ]);
    }

    public function testSendReturnsBadRequestWhenInvoiceHasInvalidProductLines(): void
    {
        // Arrange
        $invoiceId = $this->faker->uuid;
        $errorMessage = 'Cannot send invoice: no product lines';
        
        $this->mock(InvoiceServiceInterface::class)
            ->shouldReceive('sendInvoice')
            ->once()
            ->with($invoiceId)
            ->andThrow(new InvalidProductLineException($errorMessage));

        // Act
        $response = $this->postJson("/api/invoices/{$invoiceId}/send");

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'error' => $errorMessage
            ]);
    }

    private function createInvoiceDto(string $id = null, string $status = StatusEnum::Draft->value): InvoiceDto
    {
        $id = $id ?? $this->faker->uuid;

        return new InvoiceDto(
            $id,
            $status,
            $this->faker->name,
            $this->faker->email,
            [],
            0
        );
    }

    private function createProductLineDto(): ProductLineDto
    {
        return new ProductLineDto(
            $this->faker->uuid,
            $this->faker->word,
            $this->faker->numberBetween(1, 10),
            $this->faker->numberBetween(100, 1000),
            $this->faker->numberBetween(100, 10000)
        );
    }
}
