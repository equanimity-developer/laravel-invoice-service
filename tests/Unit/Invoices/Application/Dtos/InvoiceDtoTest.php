<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\Dtos;

use Modules\Invoices\Application\Dtos\InvoiceDto;
use Modules\Invoices\Application\Dtos\ProductLineDto;
use PHPUnit\Framework\TestCase;

final class InvoiceDtoTest extends TestCase
{
    public function testProperties(): void
    {
        // Arrange
        $id = 'test-id';
        $status = 'draft';
        $customerName = 'Test Customer';
        $customerEmail = 'customer@example.com';
        $productLines = [
            new ProductLineDto('line-1', 'Product 1', 2, 1000, 2000),
            new ProductLineDto('line-2', 'Product 2', 1, 500, 500),
        ];
        $totalPrice = 2500;

        // Act
        $dto = new InvoiceDto(
            $id,
            $status,
            $customerName,
            $customerEmail,
            $productLines,
            $totalPrice
        );

        // Assert
        $this->assertSame($id, $dto->id);
        $this->assertSame($status, $dto->status);
        $this->assertSame($customerName, $dto->customerName);
        $this->assertSame($customerEmail, $dto->customerEmail);
        $this->assertSame($productLines, $dto->productLines);
        $this->assertSame($totalPrice, $dto->totalPrice);
    }
} 