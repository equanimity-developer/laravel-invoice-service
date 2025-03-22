<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStatusTransitionException;
use Modules\Invoices\Domain\Exceptions\InvalidProductLineException;
use Ramsey\Uuid\UuidInterface;

final class Invoice
{
    /** @var ProductLine[] */
    private array $productLines = [];
    private int $totalPrice = 0;

    private function __construct(
        private readonly UuidInterface $id,
        private readonly string $customerName,
        private readonly string $customerEmail,
        private StatusEnum $status
    ) {
    }

    public static function create(
        UuidInterface $id,
        string $customerName,
        string $customerEmail
    ): self {
        return new self(
            $id,
            $customerName,
            $customerEmail,
            StatusEnum::Draft
        );
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function customerName(): string
    {
        return $this->customerName;
    }

    public function customerEmail(): string
    {
        return $this->customerEmail;
    }

    public function status(): StatusEnum
    {
        return $this->status;
    }

    /**
     * @return ProductLine[]
     */
    public function productLines(): array
    {
        return $this->productLines;
    }

    public function totalPrice(): int
    {
        return $this->totalPrice;
    }

    public function addProductLine(ProductLine $productLine): void
    {
        $this->productLines[] = $productLine;
        $this->recalculateTotalPrice();
    }

    private function recalculateTotalPrice(): void
    {
        $this->totalPrice = array_reduce(
            $this->productLines,
            static fn (int $carry, ProductLine $item): int => $carry + $item->totalPrice(),
            0
        );
    }

    public function send(): void
    {
        if ($this->status !== StatusEnum::Draft) {
            throw new InvalidInvoiceStatusTransitionException(
                'Invoice can only be sent if it is in draft status'
            );
        }

        if (empty($this->productLines)) {
            throw new InvalidProductLineException('Invoice must have at least one product line to be sent');
        }

        foreach ($this->productLines as $productLine) {
            if (!$productLine->isValid()) {
                throw new InvalidProductLineException(
                    'All product lines must have positive quantity and unit price'
                );
            }
        }

        $this->status = StatusEnum::Sending;
    }

    public function markAsSentToClient(): void
    {
        if ($this->status !== StatusEnum::Sending) {
            throw new InvalidInvoiceStatusTransitionException(
                'Invoice can only be marked as sent-to-client if it is in sending status'
            );
        }

        $this->status = StatusEnum::SentToClient;
    }
} 