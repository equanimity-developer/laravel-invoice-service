<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Dtos;

final readonly class CreateInvoiceRequest
{
    public function __construct(
        public string $customerName,
        public string $customerEmail,
    ) {
    }
} 