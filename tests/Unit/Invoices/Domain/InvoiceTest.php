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
        $invoice = Invoice::create(
            Uuid::uuid4(),
            $this->faker->name(),
            $this->faker->email()
        );

        $this->assertEquals(StatusEnum::Draft, $invoice->status());
        $this->assertEmpty($invoice->productLines());
    }

    public function testCanAddProductLine(): void
    {
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

        $this->assertCount(1, $invoice->productLines());
        $this->assertEquals($productLine->quantity() * $productLine->unitPrice(), $invoice->totalPrice());
    }

    public function testCannotSendInvoiceWithoutProductLines(): void
    {
        $this->expectException(InvalidProductLineException::class);

        $invoice = Invoice::create(
            Uuid::uuid4(),
            $this->faker->name(),
            $this->faker->email()
        );

        $invoice->send();
    }

    public function testCannotSendInvoiceWithInvalidProductLines(): void
    {
        $this->expectException(InvalidProductLineException::class);

        $invoice = Invoice::create(
            Uuid::uuid4(),
            $this->faker->name(),
            $this->faker->email()
        );

        $productLine = new ProductLine(
            Uuid::uuid4(),
            $this->faker->word(),
            0, // Invalid quantity
            $this->faker->numberBetween(100, 10000)
        );

        $invoice->addProductLine($productLine);
        $invoice->send();
    }

    public function testCanSendInvoiceWithValidProductLines(): void
    {
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

        $this->assertEquals(StatusEnum::Sending, $invoice->status());
    }

    public function testCanMarkAsSentToClient(): void
    {
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
        $invoice->markAsSentToClient();

        $this->assertEquals(StatusEnum::SentToClient, $invoice->status());
    }

    public function testCannotMarkAsSentToClientIfNotInSendingState(): void
    {
        $this->expectException(InvalidInvoiceStatusTransitionException::class);

        $invoice = Invoice::create(
            Uuid::uuid4(),
            $this->faker->name(),
            $this->faker->email()
        );

        $invoice->markAsSentToClient();
    }

    public function testCannotSendInvoiceIfNotInDraftState(): void
    {
        $this->expectException(InvalidInvoiceStatusTransitionException::class);

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
        $invoice->send(); // Second send should fail
    }
} 