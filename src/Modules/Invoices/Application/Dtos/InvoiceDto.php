<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Dtos;

final readonly class InvoiceDto
{
    public function __construct(
        public string $id,
        public string $status,
        public string $customerName,
        public string $customerEmail,
        public array $productLines,
        public int $totalPrice,
    ) {
    }
} 