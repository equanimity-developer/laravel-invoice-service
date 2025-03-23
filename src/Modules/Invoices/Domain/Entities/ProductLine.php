<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Exceptions\InvalidProductLineException;
use Ramsey\Uuid\UuidInterface;

final readonly class ProductLine
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private int $quantity,
        private int $unitPrice
    ) {
        if ($quantity <= 0) {
            throw new InvalidProductLineException('Quantity must be greater than zero');
        }

        if ($unitPrice <= 0) {
            throw new InvalidProductLineException('Unit price must be greater than zero');
        }
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function unitPrice(): int
    {
        return $this->unitPrice;
    }

    public function totalPrice(): int
    {
        return $this->quantity * $this->unitPrice;
    }

    public function isValid(): bool
    {
        return $this->quantity > 0 && $this->unitPrice > 0;
    }
} 