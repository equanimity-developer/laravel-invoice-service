<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\Dtos;

use Modules\Invoices\Application\Dtos\ProductLineDto;
use PHPUnit\Framework\TestCase;

final class ProductLineDtoTest extends TestCase
{
    public function testProperties(): void
    {
        // Arrange
        $id = 'line-id';
        $name = 'Product Name';
        $quantity = 3;
        $unitPrice = 1000;
        $totalPrice = 3000;

        // Act
        $dto = new ProductLineDto(
            $id,
            $name,
            $quantity,
            $unitPrice,
            $totalPrice
        );

        // Assert
        $this->assertSame($id, $dto->id);
        $this->assertSame($name, $dto->name);
        $this->assertSame($quantity, $dto->quantity);
        $this->assertSame($unitPrice, $dto->unitPrice);
        $this->assertSame($totalPrice, $dto->totalPrice);
    }
} 