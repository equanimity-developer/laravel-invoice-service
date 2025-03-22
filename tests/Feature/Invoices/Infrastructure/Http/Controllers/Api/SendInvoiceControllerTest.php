<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Infrastructure\Http\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Invoices\Api\Dtos\InvoiceDto;
use Modules\Invoices\Api\InvoiceFacadeInterface;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStatusTransitionException;
use Modules\Invoices\Domain\Exceptions\InvalidProductLineException;
use Tests\TestCase;

final class SendInvoiceControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function testInvokeSuccess(): void
    {
        // Arrange
        $invoiceId = $this->faker->uuid;
        $invoiceDto = $this->createInvoiceDto($invoiceId, StatusEnum::Sending->value);
        
        $this->mock(InvoiceFacadeInterface::class)
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

    public function testInvokeReturnsNotFoundWhenInvoiceDoesNotExist(): void
    {
        // Arrange
        $invoiceId = $this->faker->uuid;
        
        $this->mock(InvoiceFacadeInterface::class)
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

    public function testInvokeReturnsBadRequestWhenInvoiceCannotBeSent(): void
    {
        // Arrange
        $invoiceId = $this->faker->uuid;
        $errorMessage = 'Cannot send invoice: invalid status transition';
        
        $this->mock(InvoiceFacadeInterface::class)
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

    public function testInvokeReturnsBadRequestWhenInvoiceHasInvalidProductLines(): void
    {
        // Arrange
        $invoiceId = $this->faker->uuid;
        $errorMessage = 'Cannot send invoice: no product lines';
        
        $this->mock(InvoiceFacadeInterface::class)
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
    
    private function createInvoiceDto(string $id, string $status = StatusEnum::Draft->value): InvoiceDto
    {
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