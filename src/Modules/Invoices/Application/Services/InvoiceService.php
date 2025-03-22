<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Services;

use Modules\Invoices\Application\Dtos\InvoiceDto;
use Modules\Invoices\Application\Dtos\ProductLineDto;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\ProductLine;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class InvoiceService implements InvoiceServiceInterface
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private NotificationFacadeInterface $notificationFacade,
    ) {
    }

    public function createInvoice(string $customerName, string $customerEmail): InvoiceDto
    {
        $id = Uuid::uuid4();
        $invoice = Invoice::create($id, $customerName, $customerEmail);
        
        $this->invoiceRepository->save($invoice);
        
        return $this->mapToDto($invoice);
    }
    
    public function getInvoice(string $id): ?InvoiceDto
    {
        $invoice = $this->invoiceRepository->findById(Uuid::fromString($id));
        
        if (!$invoice) {
            return null;
        }
        
        return $this->mapToDto($invoice);
    }
    
    /**
     * @return InvoiceDto[]
     */
    public function getAllInvoices(): array
    {
        $invoices = $this->invoiceRepository->findAll();
        
        return array_map(fn (Invoice $invoice): InvoiceDto => $this->mapToDto($invoice), $invoices);
    }
    
    public function addProductLine(
        string $invoiceId, 
        string $productName, 
        int $quantity, 
        int $unitPrice
    ): ?InvoiceDto {
        $invoice = $this->invoiceRepository->findById(Uuid::fromString($invoiceId));
        
        if (!$invoice) {
            return null;
        }
        
        $productLine = new ProductLine(
            Uuid::uuid4(),
            $productName,
            $quantity,
            $unitPrice
        );
        
        $invoice->addProductLine($productLine);
        
        $this->invoiceRepository->save($invoice);
        
        return $this->mapToDto($invoice);
    }
    
    public function sendInvoice(string $invoiceId): ?InvoiceDto
    {
        $invoice = $this->invoiceRepository->findById(Uuid::fromString($invoiceId));
        
        if (!$invoice) {
            return null;
        }
        
        $invoice->send();
        
        // Send email notification
        $this->notificationFacade->notify(
            new NotifyData(
                resourceId: $invoice->id(),
                toEmail: $invoice->customerEmail(),
                subject: "Invoice #{$invoice->id()->toString()}",
                message: "Dear {$invoice->customerName()}, your invoice has been sent.",
            )
        );
        
        $this->invoiceRepository->save($invoice);
        
        return $this->mapToDto($invoice);
    }
    
    public function markAsSentToClient(string $id): ?InvoiceDto
    {
        $resourceId = Uuid::fromString($id);
        $invoice = $this->invoiceRepository->findById($resourceId);
        
        if (!$invoice) {
            return null;
        }
        
        $invoice->markAsSentToClient();
        
        $this->invoiceRepository->save($invoice);
        
        return $this->mapToDto($invoice);
    }
    
    private function mapToDto(Invoice $invoice): InvoiceDto
    {
        $productLineDtos = array_map(
            fn (ProductLine $productLine): ProductLineDto => new ProductLineDto(
                $productLine->id()->toString(),
                $productLine->name(),
                $productLine->quantity(),
                $productLine->unitPrice(),
                $productLine->totalPrice(),
            ),
            $invoice->productLines()
        );
        
        return new InvoiceDto(
            $invoice->id()->toString(),
            $invoice->status()->value,
            $invoice->customerName(),
            $invoice->customerEmail(),
            $productLineDtos,
            $invoice->totalPrice()
        );
    }
} 