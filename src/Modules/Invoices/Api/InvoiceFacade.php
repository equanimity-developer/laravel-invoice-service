<?php

declare(strict_types=1);

namespace Modules\Invoices\Api;

use Modules\Invoices\Api\Dtos\CreateInvoiceRequest;
use Modules\Invoices\Api\Dtos\InvoiceDto;
use Modules\Invoices\Application\Services\InvoiceServiceInterface;

final readonly class InvoiceFacade implements InvoiceFacadeInterface
{
    public function __construct(
        private InvoiceServiceInterface $invoiceService,
    ) {
    }
    
    public function getAllInvoices(): array
    {
        return $this->invoiceService->getAllInvoices();
    }
    
    public function getInvoice(string $id): ?InvoiceDto
    {
        return $this->invoiceService->getInvoice($id);
    }
    
    public function createInvoice(CreateInvoiceRequest $request): InvoiceDto
    {
        return $this->invoiceService->createInvoice(
            $request->customerName,
            $request->customerEmail
        );
    }
    
    public function addProductLine(
        string $invoiceId,
        string $productName,
        int $quantity,
        int $unitPrice
    ): ?InvoiceDto {
        return $this->invoiceService->addProductLine(
            $invoiceId,
            $productName,
            $quantity,
            $unitPrice
        );
    }
    
    public function sendInvoice(string $id): ?InvoiceDto
    {
        return $this->invoiceService->sendInvoice($id);
    }
} 