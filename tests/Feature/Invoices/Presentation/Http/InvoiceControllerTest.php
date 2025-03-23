<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Presentation\Http;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Validation\ValidationException;
use Modules\Invoices\Application\Dtos\InvoiceDto;
use Modules\Invoices\Application\Services\InvoiceServiceInterface;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStatusTransitionException;
use Modules\Invoices\Domain\Exceptions\InvalidProductLineException;
use Symfony\Component\HttpFoundation\Response;
use Tests\InMemoryDatabaseTrait;
use Tests\TestCase;
use Mockery\MockInterface;
use Ramsey\Uuid\Uuid;

final class InvoiceControllerTest extends TestCase
{
    use InMemoryDatabaseTrait, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling([
            ValidationException::class
        ]);
    }

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
        $invoiceId = (string) Uuid::uuid4();

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
                'error' => 'invoices.errors.not_found'
            ]);
    }

    public function testStore(): void
    {
        // Arrange
        $invoiceDto = new InvoiceDto(
            'b91bf857-a726-3304-9af4-2395a5293b36',
            'draft',
            'Raymundo Balistreri DVM',
            'pedro12@hotmail.com',
            [],
            0
        );

        $requestData = [
            'customer_name' => 'Raymundo Balistreri DVM',
            'customer_email' => 'pedro12@hotmail.com',
        ];

        $this->mock(InvoiceServiceInterface::class, function (MockInterface $mock) use ($invoiceDto, $requestData) {
            $mock->shouldReceive('createInvoice')
                ->with($requestData['customer_name'], $requestData['customer_email'])
                ->once()
                ->andReturn($invoiceDto);
        });

        // Act
        $response = $this->postJson('/api/invoices', $requestData);

        // Assert
        $response->assertCreated()
            ->assertJson([
                'message' => 'invoices.success.created',
                'invoice' => [
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
            'customer_name' => '',
            'customer_email' => 'not-a-valid-email',
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
        $invoiceId = '7eb757ee-85c5-354c-9c9b-cf4655cc79e7';
        $requestData = [
            'product_name' => 'Product 1',
            'quantity' => 2,
            'unit_price' => 1000,
        ];

        $invoiceDto = new InvoiceDto(
            $invoiceId,
            'draft',
            'Mr. Graham Kovacek',
            'johnson.flossie@hotmail.com',
            [],
            0
        );

        $this->mock(InvoiceServiceInterface::class, function (MockInterface $mock) use ($invoiceDto, $invoiceId, $requestData) {
            $mock->shouldReceive('addProductLine')
                ->with(
                    $invoiceId,
                    $requestData['product_name'],
                    $requestData['quantity'],
                    $requestData['unit_price']
                )
                ->once()
                ->andReturn($invoiceDto);
        });

        // Act
        $response = $this->postJson("/api/invoices/{$invoiceId}/product-lines", $requestData);

        // Assert
        $response->assertCreated()
            ->assertJson([
                'message' => 'invoices.success.product_line_added',
                'invoice' => [
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
        $invoiceId = (string) Uuid::uuid4();
        $requestData = [
            'product_name' => 'Product 1',
            'quantity' => 2,
            'unit_price' => 1000,
        ];

        $this->mock(InvoiceServiceInterface::class, function (MockInterface $mock) use ($invoiceId, $requestData) {
            $mock->shouldReceive('addProductLine')
                ->with(
                    $invoiceId,
                    $requestData['product_name'],
                    $requestData['quantity'],
                    $requestData['unit_price']
                )
                ->once()
                ->andReturnNull();
        });

        // Act
        $response = $this->postJson("/api/invoices/{$invoiceId}/product-lines", $requestData);

        // Assert
        $response->assertNotFound()
            ->assertJson([
                'error' => 'invoices.errors.not_found'
            ]);
    }

    public function testAddProductLineValidatesRequest(): void
    {
        // Arrange
        $invoiceId = $this->faker->uuid;
        $requestData = [
            'product_name' => '',
            'quantity' => 0,
            'unit_price' => 0,
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
        $invoiceId = (string) Uuid::uuid4();
        $requestData = [
            'product_name' => 'Product 1',
            'quantity' => 2,
            'unit_price' => 1000,
        ];

        $this->mock(InvoiceServiceInterface::class, function (MockInterface $mock) use ($invoiceId, $requestData) {
            $mock->shouldReceive('addProductLine')
                ->with(
                    $invoiceId,
                    $requestData['product_name'],
                    $requestData['quantity'],
                    $requestData['unit_price']
                )
                ->once()
                ->andThrow(new InvalidProductLineException('invalid_product_lines'));
        });

        // Act
        $response = $this->postJson("/api/invoices/{$invoiceId}/product-lines", $requestData);

        // Assert
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testSendReturnsNotFoundWhenInvoiceDoesNotExist(): void
    {
        // Arrange
        $invoiceId = (string) Uuid::uuid4();

        $this->mock(InvoiceServiceInterface::class, function (MockInterface $mock) use ($invoiceId) {
            $mock->shouldReceive('sendInvoice')
                ->with($invoiceId)
                ->once()
                ->andReturnNull();
        });

        // Act
        $response = $this->postJson("/api/invoices/{$invoiceId}/send");

        // Assert
        $response->assertNotFound()
            ->assertJson([
                'error' => 'invoices.errors.not_found'
            ]);
    }

    public function testSendReturnsBadRequestWhenInvoiceCannotBeSent(): void
    {
        // Arrange
        $invoiceId = (string) Uuid::uuid4();

        $this->mock(InvoiceServiceInterface::class, function (MockInterface $mock) use ($invoiceId) {
            $mock->shouldReceive('sendInvoice')
                ->with($invoiceId)
                ->once()
                ->andThrow(new InvalidInvoiceStatusTransitionException('invalid_status_transition_send'));
        });

        // Act
        $response = $this->postJson("/api/invoices/{$invoiceId}/send");

        // Assert
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testSendReturnsBadRequestWhenInvoiceHasInvalidProductLines(): void
    {
        // Arrange
        $invoiceId = (string) Uuid::uuid4();

        $this->mock(InvoiceServiceInterface::class, function (MockInterface $mock) use ($invoiceId) {
            $mock->shouldReceive('sendInvoice')
                ->with($invoiceId)
                ->once()
                ->andThrow(new InvalidProductLineException('no_product_lines'));
        });

        // Act
        $response = $this->postJson("/api/invoices/{$invoiceId}/send");

        // Assert
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
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
}
