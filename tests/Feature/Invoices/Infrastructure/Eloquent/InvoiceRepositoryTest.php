<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Infrastructure\Eloquent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\ProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Infrastructure\Eloquent\InvoiceModel;
use Modules\Invoices\Infrastructure\Eloquent\InvoiceRepository;
use Modules\Invoices\Infrastructure\Eloquent\ProductLineModel;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

final class InvoiceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new InvoiceRepository();
    }

    public function testSaveStoresInvoiceInDatabase(): void
    {
        // Arrange
        $id = Uuid::uuid4();
        $customerName = 'Test Customer';
        $customerEmail = 'test@example.com';
        $invoice = Invoice::create($id, $customerName, $customerEmail);

        // Add a product line
        $productLineId = Uuid::uuid4();
        $productName = 'Test Product';
        $quantity = 2;
        $unitPrice = 1000;
        $productLine = new ProductLine($productLineId, $productName, $quantity, $unitPrice);
        $invoice->addProductLine($productLine);

        // Act
        $this->repository->save($invoice);

        // Assert
        $this->assertDatabaseHas('invoices', [
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'status' => StatusEnum::Draft->value,
        ]);

        $invoiceFromDb = InvoiceModel::where('customer_name', $customerName)
            ->where('customer_email', $customerEmail)
            ->where('status', StatusEnum::Draft->value)
            ->first();
        
        $this->assertNotNull($invoiceFromDb);
        
        $this->assertDatabaseHas('invoice_product_lines', [
            'invoice_id' => $invoiceFromDb->id,
            'name' => $productName,
            'quantity' => $quantity,
            'price' => $unitPrice,
        ]);
    }

    public function testFindByIdReturnsNullWhenInvoiceNotFound(): void
    {
        // Arrange
        $nonExistentId = Uuid::uuid4();

        // Act
        $result = $this->repository->findById($nonExistentId);

        // Assert
        $this->assertNull($result);
    }

    public function testFindByIdReturnsMappedInvoice(): void
    {
        // Arrange - Create invoice with product line
        $invoiceModel = new InvoiceModel();
        $invoiceModel->id = Uuid::uuid4()->toString();
        $invoiceModel->customer_name = 'Test Customer';
        $invoiceModel->customer_email = 'test@example.com';
        $invoiceModel->status = StatusEnum::Draft->value;
        $invoiceModel->save();
        
        $productLineModel = new ProductLineModel();
        $productLineModel->id = Uuid::uuid4()->toString();
        $productLineModel->invoice_id = $invoiceModel->id;
        $productLineModel->name = 'Test Product';
        $productLineModel->quantity = 2;
        $productLineModel->price = 1000;
        $productLineModel->save();

        // Act
        $invoice = $this->repository->findById(Uuid::fromString($invoiceModel->id));

        // Assert
        $this->assertNotNull($invoice);
        $this->assertEquals($invoiceModel->id, $invoice->id()->toString());
        $this->assertEquals($invoiceModel->customer_name, $invoice->customerName());
        $this->assertEquals($invoiceModel->customer_email, $invoice->customerEmail());
        $this->assertEquals(StatusEnum::Draft, $invoice->status());
        
        // Check product lines
        $this->assertCount(1, $invoice->productLines());
        $productLine = $invoice->productLines()[0];
        $this->assertEquals($productLineModel->id, $productLine->id()->toString());
        $this->assertEquals($productLineModel->name, $productLine->name());
        $this->assertEquals($productLineModel->quantity, $productLine->quantity());
        $this->assertEquals($productLineModel->price, $productLine->unitPrice());
        $this->assertEquals($productLineModel->quantity * $productLineModel->price, $productLine->totalPrice());
    }

    public function testFindByIdWithSendingStatus(): void
    {
        // Arrange
        $invoiceModel = new InvoiceModel();
        $invoiceModel->id = Uuid::uuid4()->toString();
        $invoiceModel->customer_name = 'Test Customer';
        $invoiceModel->customer_email = 'test@example.com';
        $invoiceModel->status = StatusEnum::Sending->value;
        $invoiceModel->save();
        
        $productLineModel = new ProductLineModel();
        $productLineModel->id = Uuid::uuid4()->toString();
        $productLineModel->invoice_id = $invoiceModel->id;
        $productLineModel->name = 'Test Product';
        $productLineModel->quantity = 2;
        $productLineModel->price = 1000;
        $productLineModel->save();

        // Act
        $invoice = $this->repository->findById(Uuid::fromString($invoiceModel->id));

        // Assert
        $this->assertNotNull($invoice);
        $this->assertEquals(StatusEnum::Sending, $invoice->status());
    }

    public function testFindByIdWithSentToClientStatus(): void
    {
        // Arrange
        $invoiceModel = new InvoiceModel();
        $invoiceModel->id = Uuid::uuid4()->toString();
        $invoiceModel->customer_name = 'Test Customer';
        $invoiceModel->customer_email = 'test@example.com';
        $invoiceModel->status = StatusEnum::SentToClient->value;
        $invoiceModel->save();
        
        $productLineModel = new ProductLineModel();
        $productLineModel->id = Uuid::uuid4()->toString();
        $productLineModel->invoice_id = $invoiceModel->id;
        $productLineModel->name = 'Test Product';
        $productLineModel->quantity = 2;
        $productLineModel->price = 1000;
        $productLineModel->save();

        // Act
        $invoice = $this->repository->findById(Uuid::fromString($invoiceModel->id));

        // Assert
        $this->assertNotNull($invoice);
        $this->assertEquals(StatusEnum::SentToClient, $invoice->status());
    }

    public function testFindAllReturnsEmptyArrayWhenNoInvoicesExist(): void
    {
        // Act
        $invoices = $this->repository->findAll();

        // Assert
        $this->assertIsArray($invoices);
        $this->assertEmpty($invoices);
    }

    public function testFindAllReturnsAllInvoices(): void
    {
        // Arrange - Create two invoices
        $invoice1 = new InvoiceModel();
        $invoice1->id = Uuid::uuid4()->toString();
        $invoice1->customer_name = 'Customer 1';
        $invoice1->customer_email = 'customer1@example.com';
        $invoice1->status = StatusEnum::Draft->value;
        $invoice1->save();
        
        $invoice2 = new InvoiceModel();
        $invoice2->id = Uuid::uuid4()->toString();
        $invoice2->customer_name = 'Customer 2';
        $invoice2->customer_email = 'customer2@example.com';
        $invoice2->status = StatusEnum::Sending->value;
        $invoice2->save();
        
        // Add product line to the second invoice
        $productLine = new ProductLineModel();
        $productLine->id = Uuid::uuid4()->toString();
        $productLine->invoice_id = $invoice2->id;
        $productLine->name = 'Test Product';
        $productLine->quantity = 2;
        $productLine->price = 1000;
        $productLine->save();

        // Act
        $invoices = $this->repository->findAll();

        // Assert
        $this->assertIsArray($invoices);
        $this->assertCount(2, $invoices);
        
        // Find our invoices in the result
        $foundInvoice1 = null;
        $foundInvoice2 = null;
        
        foreach ($invoices as $invoice) {
            if ($invoice->id()->toString() === $invoice1->id) {
                $foundInvoice1 = $invoice;
            } elseif ($invoice->id()->toString() === $invoice2->id) {
                $foundInvoice2 = $invoice;
            }
        }
        
        // Verify first invoice
        $this->assertNotNull($foundInvoice1);
        $this->assertEquals($invoice1->customer_name, $foundInvoice1->customerName());
        $this->assertEquals($invoice1->customer_email, $foundInvoice1->customerEmail());
        $this->assertEquals(StatusEnum::Draft, $foundInvoice1->status());
        $this->assertEmpty($foundInvoice1->productLines());
        
        // Verify second invoice
        $this->assertNotNull($foundInvoice2);
        $this->assertEquals($invoice2->customer_name, $foundInvoice2->customerName());
        $this->assertEquals($invoice2->customer_email, $foundInvoice2->customerEmail());
        $this->assertEquals(StatusEnum::Sending, $foundInvoice2->status());
        $this->assertCount(1, $foundInvoice2->productLines());
    }
} 