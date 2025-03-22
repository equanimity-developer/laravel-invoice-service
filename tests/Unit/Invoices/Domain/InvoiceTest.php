<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain;

use Illuminate\Foundation\Testing\WithFaker;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStatusTransitionException;
use Modules\Invoices\Domain\Exceptions\InvalidProductLineException;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\ProductLine;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class InvoiceTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        $this->setUpFaker();
    }

    public function testCreateInvoice(): void
    {
        // Arrange
        $id = Uuid::uuid4();
        $customerName = $this->faker->name();
        $customerEmail = $this->faker->email();
        
        // Act
        $invoice = Invoice::create(
            $id,
            $customerName,
            $customerEmail
        );

        // Assert
        $this->assertEquals(StatusEnum::Draft, $invoice->status());
        $this->assertEmpty($invoice->productLines());
    }

    public function testCanAddProductLine(): void
    {
        // Arrange
        $invoice = Invoice::create(
            Uuid::uuid4(),
            $this->faker->name(),
            $this->faker->email()
        );

        $productLine = new ProductLine(
            Uuid::uuid4(),
            $this->faker->word(),
            $this->faker->numberBetween(1, 10),
            $this->faker->numberBetween(100, 10000)
        );

        // Act
        $invoice->addProductLine($productLine);

        // Assert
        $this->assertCount(1, $invoice->productLines());
        $this->assertEquals($productLine->quantity() * $productLine->unitPrice(), $invoice->totalPrice());
    }

    public function testCannotSendInvoiceWithoutProductLines(): void
    {
        // Arrange
        $invoice = Invoice::create(
            Uuid::uuid4(),
            $this->faker->name(),
            $this->faker->email()
        );
        
        $this->expectException(InvalidProductLineException::class);

        // Act
        $invoice->send();

        // Assert is handled by expectException
    }

    public function testCannotSendInvoiceWithInvalidProductLines(): void
    {
        // Arrange
        $invoice = Invoice::create(
            Uuid::uuid4(),
            $this->faker->name(),
            $this->faker->email()
        );

        // Act & Assert
        $this->expectException(InvalidProductLineException::class);
        
        // This will throw an exception during construction due to invalid quantity
        $productLine = new ProductLine(
            Uuid::uuid4(),
            $this->faker->word(),
            0, // Invalid quantity
            $this->faker->numberBetween(100, 10000)
        );
        
        // This line won't be reached because an exception is thrown above
        $invoice->addProductLine($productLine);
    }

    public function testCanSendInvoiceWithValidProductLines(): void
    {
        // Arrange
        $invoice = Invoice::create(
            Uuid::uuid4(),
            $this->faker->name(),
            $this->faker->email()
        );

        $productLine = new ProductLine(
            Uuid::uuid4(),
            $this->faker->word(),
            $this->faker->numberBetween(1, 10),
            $this->faker->numberBetween(100, 10000)
        );

        $invoice->addProductLine($productLine);
        
        // Act
        $invoice->send();

        // Assert
        $this->assertEquals(StatusEnum::Sending, $invoice->status());
    }

    public function testCanMarkAsSentToClient(): void
    {
        // Arrange
        $invoice = Invoice::create(
            Uuid::uuid4(),
            $this->faker->name(),
            $this->faker->email()
        );

        $productLine = new ProductLine(
            Uuid::uuid4(),
            $this->faker->word(),
            $this->faker->numberBetween(1, 10),
            $this->faker->numberBetween(100, 10000)
        );

        $invoice->addProductLine($productLine);
        $invoice->send();
        
        // Act
        $invoice->markAsSentToClient();

        // Assert
        $this->assertEquals(StatusEnum::SentToClient, $invoice->status());
    }

    public function testCannotMarkAsSentToClientIfNotInSendingState(): void
    {
        // Arrange
        $invoice = Invoice::create(
            Uuid::uuid4(),
            $this->faker->name(),
            $this->faker->email()
        );
        
        $this->expectException(InvalidInvoiceStatusTransitionException::class);

        // Act
        $invoice->markAsSentToClient();

        // Assert is handled by expectException
    }

    public function testCannotSendInvoiceIfNotInDraftState(): void
    {
        // Arrange
        $invoice = Invoice::create(
            Uuid::uuid4(),
            $this->faker->name(),
            $this->faker->email()
        );

        $productLine = new ProductLine(
            Uuid::uuid4(),
            $this->faker->word(),
            $this->faker->numberBetween(1, 10),
            $this->faker->numberBetween(100, 10000)
        );

        $invoice->addProductLine($productLine);
        $invoice->send();
        
        $this->expectException(InvalidInvoiceStatusTransitionException::class);

        // Act
        $invoice->send(); // Second send should fail

        // Assert is handled by expectException
    }
} 