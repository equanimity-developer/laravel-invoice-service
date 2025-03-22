<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Api\Dtos;

use Modules\Invoices\Application\Dtos\CreateInvoiceRequest;
use PHPUnit\Framework\TestCase;

final class CreateInvoiceRequestTest extends TestCase
{
    public function testProperties(): void
    {
        // Arrange
        $customerName = 'Test Customer';
        $customerEmail = 'test@example.com';
        
        // Act
        $request = new CreateInvoiceRequest($customerName, $customerEmail);
        
        // Assert
        $this->assertEquals($customerName, $request->customerName);
        $this->assertEquals($customerEmail, $request->customerEmail);
    }
} 