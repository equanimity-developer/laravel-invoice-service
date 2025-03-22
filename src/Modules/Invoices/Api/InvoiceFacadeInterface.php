<?php

declare(strict_types=1);

namespace Modules\Invoices\Api;

use Modules\Invoices\Api\Dtos\CreateInvoiceRequest;
use Modules\Invoices\Api\Dtos\InvoiceDto;

interface InvoiceFacadeInterface
{
    public function getAllInvoices(): array;
    
    public function getInvoice(string $id): ?InvoiceDto;
    
    public function createInvoice(CreateInvoiceRequest $request): InvoiceDto;
    
    public function addProductLine(
        string $invoiceId,
        string $productName,
        int $quantity,
        int $unitPrice
    ): ?InvoiceDto;
    
    public function sendInvoice(string $id): ?InvoiceDto;
} 