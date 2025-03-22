<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Services;

use Modules\Invoices\Application\Dtos\InvoiceDto;

interface InvoiceServiceInterface
{
    public function createInvoice(string $customerName, string $customerEmail): InvoiceDto;
    
    public function getInvoice(string $id): ?InvoiceDto;
    
    public function getAllInvoices(): array;
    
    public function addProductLine(
        string $invoiceId,
        string $productName,
        int $quantity,
        int $unitPrice
    ): ?InvoiceDto;
    
    public function sendInvoice(string $id): ?InvoiceDto;
    
    public function markAsSentToClient(string $id): ?InvoiceDto;
} 