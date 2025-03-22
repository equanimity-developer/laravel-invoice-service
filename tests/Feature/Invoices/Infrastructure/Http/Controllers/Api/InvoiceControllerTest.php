<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Infrastructure\Http\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Invoices\Api\Dtos\InvoiceDto;
use Modules\Invoices\Api\InvoiceFacadeInterface;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Tests\TestCase;

final class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function testIndex(): void
    {
        // Arrange
        $invoices = [
            $this->createInvoiceDto(),
            $this->createInvoiceDto(),
        ];
        
        $this->mock(InvoiceFacadeInterface::class)
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
        
        $this->mock(InvoiceFacadeInterface::class)
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
        
        $this->mock(InvoiceFacadeInterface::class)
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
            'customer_name' => $invoiceDto->customerName,
            'customer_email' => $invoiceDto->customerEmail,
        ];
        
        $this->mock(InvoiceFacadeInterface::class)
            ->shouldReceive('createInvoice')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($requestData) {
                return $arg->customerName === $requestData['customer_name'] && 
                       $arg->customerEmail === $requestData['customer_email'];
            }))
            ->andReturn($invoiceDto);

        // Act
        $response = $this->postJson('/api/invoices', $requestData);

        // Assert
        $response->assertCreated()
            ->assertJson([
                'message' => 'Invoice successfully created',
                'data' => [
                    'id' => $invoiceDto->id,
                    'customerName' => $invoiceDto->customerName,
                    'customerEmail' => $invoiceDto->customerEmail,
                    'status' => $invoiceDto->status,
                ]
            ]);
    }

    private function createInvoiceDto(): InvoiceDto
    {
        return new InvoiceDto(
            $this->faker->uuid,
            StatusEnum::Draft->value,
            $this->faker->name,
            $this->faker->email,
            [],
            0
        );
    }
} 