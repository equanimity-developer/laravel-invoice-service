<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application;

use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Modules\Invoices\Api\Dtos\InvoiceDto;
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
        // Arrange
        $customerName = $this->faker->name();
        $customerEmail = $this->faker->email();

        $this->invoiceRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Invoice $invoice) use ($customerName, $customerEmail) {
                return $invoice->customerName() === $customerName
                    && $invoice->customerEmail() === $customerEmail
                    && $invoice->status() === StatusEnum::Draft;
            }));

        // Act
        $invoiceDto = $this->invoiceService->createInvoice($customerName, $customerEmail);

        // Assert
        $this->assertEquals($customerName, $invoiceDto->customerName);
        $this->assertEquals($customerEmail, $invoiceDto->customerEmail);
        $this->assertEquals(StatusEnum::Draft->value, $invoiceDto->status);
        $this->assertEmpty($invoiceDto->productLines);
        $this->assertEquals(0, $invoiceDto->totalPrice);
    }

    public function testGetInvoice(): void
    {
        // Arrange
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

        // Act
        $invoiceDto = $this->invoiceService->getInvoice($id->toString());

        // Assert
        $this->assertNotNull($invoiceDto);
        $this->assertEquals($id->toString(), $invoiceDto->id);
        $this->assertEquals($customerName, $invoiceDto->customerName);
        $this->assertEquals($customerEmail, $invoiceDto->customerEmail);
        $this->assertEquals(StatusEnum::Draft->value, $invoiceDto->status);
        $this->assertCount(1, $invoiceDto->productLines);
        $this->assertEquals($productName, $invoiceDto->productLines[0]->name);
        $this->assertEquals($quantity * $unitPrice, $invoiceDto->totalPrice);
    }

    public function testGetInvoiceReturnsNullWhenInvoiceNotFound(): void
    {
        // Arrange
        $id = Uuid::uuid4();

        $this->invoiceRepository->method('findById')
            ->with($this->callback(fn ($uuid) => $uuid->toString() === $id->toString()))
            ->willReturn(null);

        // Act
        $invoiceDto = $this->invoiceService->getInvoice($id->toString());

        // Assert
        $this->assertNull($invoiceDto);
    }

    public function testGetAllInvoices(): void
    {
        // Arrange
        $invoice1 = Invoice::create(Uuid::uuid4(), $this->faker->name(), $this->faker->email());
        $invoice2 = Invoice::create(Uuid::uuid4(), $this->faker->name(), $this->faker->email());

        $this->invoiceRepository->method('findAll')
            ->willReturn([$invoice1, $invoice2]);

        // Act
        $invoiceDtos = $this->invoiceService->getAllInvoices();

        // Assert
        $this->assertCount(2, $invoiceDtos);
        $this->assertEquals($invoice1->id()->toString(), $invoiceDtos[0]->id);
        $this->assertEquals($invoice2->id()->toString(), $invoiceDtos[1]->id);
    }

    public function testAddProductLine(): void
    {
        // Arrange
        $id = Uuid::uuid4();
        $customerName = $this->faker->name();
        $customerEmail = $this->faker->email();
        $productName = $this->faker->word();
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->numberBetween(100, 1000);

        // Create a real invoice
        $invoice = Invoice::create($id, $customerName, $customerEmail);

        $this->invoiceRepository->method('findById')
            ->with($this->callback(fn ($uuid) => $uuid->toString() === $id->toString()))
            ->willReturn($invoice);

        $this->invoiceRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Invoice $savedInvoice) use ($productName) {
                return count($savedInvoice->productLines()) === 1 
                    && $savedInvoice->productLines()[0]->name() === $productName;
            }));

        // Act
        $invoiceDto = $this->invoiceService->addProductLine(
            $id->toString(),
            $productName,
            $quantity,
            $unitPrice
        );

        // Assert
        $this->assertNotNull($invoiceDto);
        $this->assertCount(1, $invoiceDto->productLines);
        $this->assertEquals($productName, $invoiceDto->productLines[0]->name);
        $this->assertEquals($quantity, $invoiceDto->productLines[0]->quantity);
        $this->assertEquals($unitPrice, $invoiceDto->productLines[0]->unitPrice);
        $this->assertEquals($quantity * $unitPrice, $invoiceDto->productLines[0]->totalPrice);
        $this->assertEquals($quantity * $unitPrice, $invoiceDto->totalPrice);
    }

    public function testAddProductLineReturnsNullWhenInvoiceNotFound(): void
    {
        // Arrange
        $id = Uuid::uuid4();
        $productName = $this->faker->word();
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->numberBetween(100, 1000);

        $this->invoiceRepository->method('findById')
            ->with($this->callback(fn ($uuid) => $uuid->toString() === $id->toString()))
            ->willReturn(null);

        $this->invoiceRepository->expects($this->never())->method('save');

        // Act
        $invoiceDto = $this->invoiceService->addProductLine(
            $id->toString(),
            $productName,
            $quantity,
            $unitPrice
        );

        // Assert
        $this->assertNull($invoiceDto);
    }

    public function testSendInvoice(): void
    {
        // Arrange
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

        // Act
        $invoiceDto = $this->invoiceService->sendInvoice($id->toString());

        // Assert
        $this->assertNotNull($invoiceDto);
        $this->assertEquals(StatusEnum::Sending->value, $invoiceDto->status);
    }

    public function testSendInvoiceReturnsNullWhenInvoiceNotFound(): void
    {
        // Arrange
        $id = Uuid::uuid4();

        $this->invoiceRepository->method('findById')
            ->with($this->callback(fn ($uuid) => $uuid->toString() === $id->toString()))
            ->willReturn(null);

        $this->invoiceRepository->expects($this->never())->method('save');
        $this->notificationFacade->expects($this->never())->method('notify');

        // Act
        $invoiceDto = $this->invoiceService->sendInvoice($id->toString());

        // Assert
        $this->assertNull($invoiceDto);
    }

    public function testMarkAsSentToClient(): void
    {
        // Arrange
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

        // Act
        $result = $this->invoiceService->markAsSentToClient($id->toString());

        // Assert
        $this->assertNotNull($result);
        $this->assertInstanceOf(InvoiceDto::class, $result);
        // Additional assertions on mock expectations are implicit
    }

    public function testMarkAsSentToClientDoesNothingWhenInvoiceNotFound(): void
    {
        // Arrange
        $id = Uuid::uuid4();

        $this->invoiceRepository->method('findById')
            ->with($this->callback(fn ($uuid) => $uuid->toString() === $id->toString()))
            ->willReturn(null);

        $this->invoiceRepository->expects($this->never())->method('save');

        // Act
        $result = $this->invoiceService->markAsSentToClient($id->toString());

        // Assert
        $this->assertNull($result);
        // Additional assertions on mock expectations are implicit
    }
} 