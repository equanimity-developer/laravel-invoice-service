<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Invoices\Infrastructure\Eloquent\InvoiceModel;
use Modules\Invoices\Infrastructure\Eloquent\ProductLineModel;
use Tests\TestCase;

final class ProductLineModelTest extends TestCase
{
    public function testTableName(): void
    {
        // Arrange
        $productLine = new ProductLineModel();
        
        // Act
        $tableName = $productLine->getTable();
        
        // Assert
        $this->assertEquals('invoice_product_lines', $tableName);
    }
    
    public function testFillableAttributes(): void
    {
        // Arrange
        $productLine = new ProductLineModel();
        
        // Act
        $fillable = $productLine->getFillable();
        
        // Assert
        $this->assertContains('invoice_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('price', $fillable);
        $this->assertContains('quantity', $fillable);
    }
    
    public function testHasUuidsTraitIsUsed(): void
    {
        // Arrange & Act
        $traits = class_uses_recursive(ProductLineModel::class);
        
        // Assert
        $this->assertContains('Illuminate\Database\Eloquent\Concerns\HasUuids', $traits);
    }
    
    public function testInvoiceRelationship(): void
    {
        // Arrange
        $productLine = new ProductLineModel();
        
        // Act
        $relation = $productLine->invoice();
        
        // Assert
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('invoice_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(InvoiceModel::class, $relation->getRelated());
    }
} 