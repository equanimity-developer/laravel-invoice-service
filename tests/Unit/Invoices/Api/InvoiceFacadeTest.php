<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Api;

use Mockery;
use Mockery\MockInterface;
use Modules\Invoices\Api\InvoiceFacade;
use Modules\Invoices\Application\Dtos\CreateInvoiceRequest;
use Modules\Invoices\Application\Dtos\InvoiceDto;
use Modules\Invoices\Application\Services\InvoiceServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class InvoiceFacadeTest extends TestCase
{
    private MockInterface $invoiceService;
    private InvoiceFacade $invoiceFacade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceService = Mockery::mock(InvoiceServiceInterface::class);
        $this->invoiceFacade = new InvoiceFacade($this->invoiceService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetAllInvoices(): void
    {
        // Arrange
        $invoiceDtos = [
            $this->createInvoiceDto(),
            $this->createInvoiceDto()
        ];
        
        $this->invoiceService
            ->shouldReceive('getAllInvoices')
            ->once()
            ->andReturn($invoiceDtos);

        // Act
        $result = $this->invoiceFacade->getAllInvoices();

        // Assert
        $this->assertSame($invoiceDtos, $result);
    }

    public function testGetInvoice(): void
    {
        // Arrange
        $invoiceId = 'test-id';
        $invoiceDto = $this->createInvoiceDto();
        
        $this->invoiceService
            ->shouldReceive('getInvoice')
            ->once()
            ->with($invoiceId)
            ->andReturn($invoiceDto);

        // Act
        $result = $this->invoiceFacade->getInvoice($invoiceId);

        // Assert
        $this->assertSame($invoiceDto, $result);
    }

    public function testGetInvoiceReturnsNullWhenInvoiceNotFound(): void
    {
        // Arrange
        $invoiceId = 'non-existent-id';
        
        $this->invoiceService
            ->shouldReceive('getInvoice')
            ->once()
            ->with($invoiceId)
            ->andReturn(null);

        // Act
        $result = $this->invoiceFacade->getInvoice($invoiceId);

        // Assert
        $this->assertNull($result);
    }

    public function testCreateInvoice(): void
    {
        // Arrange
        $request = new CreateInvoiceRequest(
            'Test Customer',
            'customer@example.com'
        );
        $invoiceDto = $this->createInvoiceDto();
        
        $this->invoiceService
            ->shouldReceive('createInvoice')
            ->once()
            ->with($request->customerName, $request->customerEmail)
            ->andReturn($invoiceDto);

        // Act
        $result = $this->invoiceFacade->createInvoice($request);

        // Assert
        $this->assertSame($invoiceDto, $result);
    }

    public function testAddProductLine(): void
    {
        // Arrange
        $invoiceId = 'test-id';
        $productName = 'Test Product';
        $quantity = 2;
        $unitPrice = 1000;
        $invoiceDto = $this->createInvoiceDto();
        
        $this->invoiceService
            ->shouldReceive('addProductLine')
            ->once()
            ->with($invoiceId, $productName, $quantity, $unitPrice)
            ->andReturn($invoiceDto);

        // Act
        $result = $this->invoiceFacade->addProductLine(
            $invoiceId,
            $productName,
            $quantity,
            $unitPrice
        );

        // Assert
        $this->assertSame($invoiceDto, $result);
    }

    public function testAddProductLineReturnsNullWhenInvoiceNotFound(): void
    {
        // Arrange
        $invoiceId = 'non-existent-id';
        $productName = 'Test Product';
        $quantity = 2;
        $unitPrice = 1000;
        
        $this->invoiceService
            ->shouldReceive('addProductLine')
            ->once()
            ->with($invoiceId, $productName, $quantity, $unitPrice)
            ->andReturn(null);

        // Act
        $result = $this->invoiceFacade->addProductLine(
            $invoiceId,
            $productName,
            $quantity,
            $unitPrice
        );

        // Assert
        $this->assertNull($result);
    }

    public function testSendInvoice(): void
    {
        // Arrange
        $invoiceId = 'test-id';
        $invoiceDto = $this->createInvoiceDto();
        
        $this->invoiceService
            ->shouldReceive('sendInvoice')
            ->once()
            ->with($invoiceId)
            ->andReturn($invoiceDto);

        // Act
        $result = $this->invoiceFacade->sendInvoice($invoiceId);

        // Assert
        $this->assertSame($invoiceDto, $result);
    }

    public function testSendInvoiceReturnsNullWhenInvoiceNotFound(): void
    {
        // Arrange
        $invoiceId = 'non-existent-id';
        
        $this->invoiceService
            ->shouldReceive('sendInvoice')
            ->once()
            ->with($invoiceId)
            ->andReturn(null);

        // Act
        $result = $this->invoiceFacade->sendInvoice($invoiceId);

        // Assert
        $this->assertNull($result);
    }

    private function createInvoiceDto(): InvoiceDto
    {
        return new InvoiceDto(
            'test-id',
            'draft',
            'Test Customer',
            'customer@example.com',
            [],
            0
        );
    }
} 