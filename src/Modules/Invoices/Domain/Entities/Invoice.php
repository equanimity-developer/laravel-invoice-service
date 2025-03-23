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

    /**
     * Attempts to send the invoice to the customer.
     * 
     * Business rules applied:
     * - Invoice must be in draft status
     * - Invoice must have at least one product line
     * - All product lines must be valid (positive quantity and price)
     * 
     * @throws InvalidInvoiceStatusTransitionException When invoice is not in draft status
     * @throws InvalidProductLineException When invoice has no product lines or invalid product lines
     */
    public function send(): void
    {
        if ($this->status !== StatusEnum::Draft) {
            throw new InvalidInvoiceStatusTransitionException(
                'invalid_status_transition_send'
            );
        }

        if (empty($this->productLines)) {
            throw new InvalidProductLineException('no_product_lines');
        }

        foreach ($this->productLines as $productLine) {
            if (!$productLine->isValid()) {
                throw new InvalidProductLineException(
                    'invalid_product_lines'
                );
            }
        }

        $this->status = StatusEnum::Sending;
    }

    /**
     * Marks the invoice as sent to the client.
     * 
     * Business rules applied:
     * - Invoice must be in sending status
     * 
     * @throws InvalidInvoiceStatusTransitionException When invoice is not in sending status
     */
    public function markAsSentToClient(): void
    {
        if ($this->status !== StatusEnum::Sending) {
            throw new InvalidInvoiceStatusTransitionException(
                'invalid_status_transition_mark_sent'
            );
        }

        $this->status = StatusEnum::SentToClient;
    }
} 