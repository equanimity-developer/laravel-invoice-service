<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application;

use Illuminate\Foundation\Testing\WithFaker;
use Modules\Invoices\Application\Services\InvoiceService;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\ProductLine;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class InvoiceServiceTest extends TestCase
{
    use WithFaker;

    private InvoiceRepositoryInterface|MockObject $invoiceRepository;
    private NotificationFacadeInterface|MockObject $notificationFacade;
    private InvoiceService $invoiceService;

    protected function setUp(): void
    {
        $this->setUpFaker();

        $this->invoiceRepository = $this->createMock(InvoiceRepositoryInterface::class);
        $this->notificationFacade = $this->createMock(NotificationFacadeInterface::class);

        $this->invoiceService = new InvoiceService(
            $this->invoiceRepository,
            $this->notificationFacade
        );
    }

    public function testCreateInvoice(): void
    {
        $customerName = $this->faker->name();
        $customerEmail = $this->faker->email();

        $this->invoiceRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Invoice $invoice) use ($customerName, $customerEmail) {
                return $invoice->customerName() === $customerName
                    && $invoice->customerEmail() === $customerEmail
                    && $invoice->status() === StatusEnum::Draft;
            }));

        $invoiceDto = $this->invoiceService->createInvoice($customerName, $customerEmail);

        $this->assertEquals($customerName, $invoiceDto->customerName);
        $this->assertEquals($customerEmail, $invoiceDto->customerEmail);
        $this->assertEquals(StatusEnum::Draft->value, $invoiceDto->status);
        $this->assertEmpty($invoiceDto->productLines);
        $this->assertEquals(0, $invoiceDto->totalPrice);
    }

    public function testSendInvoice(): void
    {
        $id = Uuid::uuid4();
        $customerName = $this->faker->name();
        $customerEmail = $this->faker->email();
        $productName = $this->faker->word();
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->numberBetween(100, 1000);

        // Create a real invoice with product line
        $invoice = Invoice::create($id, $customerName, $customerEmail);
        $productLine = new ProductLine(Uuid::uuid4(), $productName, $quantity, $unitPrice);
        $invoice->addProductLine($productLine);

        $this->invoiceRepository->method('findById')
            ->with($this->callback(fn ($uuid) => $uuid->toString() === $id->toString()))
            ->willReturn($invoice);

        $this->invoiceRepository->expects($this->once())->method('save');

        $this->notificationFacade->expects($this->once())
            ->method('notify')
            ->with($this->callback(function (NotifyData $data) use ($id, $customerEmail) {
                return $data->resourceId->toString() === $id->toString()
                    && $data->toEmail === $customerEmail;
            }));

        $invoiceDto = $this->invoiceService->sendInvoice($id->toString());

        $this->assertNotNull($invoiceDto);
        $this->assertEquals(StatusEnum::Sending->value, $invoiceDto->status);
    }

    public function testMarkAsSentToClient(): void
    {
        $id = Uuid::uuid4();
        $customerName = $this->faker->name();
        $customerEmail = $this->faker->email();
        $productName = $this->faker->word();
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->numberBetween(100, 1000);

        // Create a real invoice with product line and send it
        $invoice = Invoice::create($id, $customerName, $customerEmail);
        $productLine = new ProductLine(Uuid::uuid4(), $productName, $quantity, $unitPrice);
        $invoice->addProductLine($productLine);
        $invoice->send();

        $this->invoiceRepository->method('findById')
            ->with($this->callback(fn ($uuid) => $uuid->toString() === $id->toString()))
            ->willReturn($invoice);

        $this->invoiceRepository->expects($this->once())->method('save')
            ->with($this->callback(function (Invoice $savedInvoice) {
                return $savedInvoice->status() === StatusEnum::SentToClient;
            }));

        $this->invoiceService->markAsSentToClient($id);
    }
} 