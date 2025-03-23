<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Eloquent;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\ProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final readonly class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function save(Invoice $invoice): void
    {
        $invoiceModel = InvoiceModel::updateOrCreate(
            ['id' => $invoice->id()->toString()],
            [
                'customer_name' => $invoice->customerName(),
                'customer_email' => $invoice->customerEmail(),
                'status' => $invoice->status()->value,
            ]
        );

        $invoiceModel->productLines()->delete();

        foreach ($invoice->productLines() as $productLine) {
            $invoiceModel->productLines()->create([
                'id' => $productLine->id()->toString(),
                'name' => $productLine->name(),
                'price' => $productLine->unitPrice(),
                'quantity' => $productLine->quantity(),
            ]);
        }
    }

    public function findById(UuidInterface $id): ?Invoice
    {
        $invoiceModel = InvoiceModel::with('productLines')->find($id->toString());

        if (!$invoiceModel) {
            return null;
        }

        return $this->mapToDomainEntity($invoiceModel);
    }

    public function findAll(): array
    {
        $invoiceModels = InvoiceModel::with('productLines')->get();

        return $invoiceModels->map(fn (InvoiceModel $model) => $this->mapToDomainEntity($model))->all();
    }

    private function mapToDomainEntity(InvoiceModel $model): Invoice
    {
        $invoice = Invoice::create(
            Uuid::fromString($model->id),
            $model->customer_name,
            $model->customer_email
        );

        if ($model->status === StatusEnum::Sending->value) {
            $this->addProductLinesToInvoice($invoice, $model);
            $invoice->send();
        } elseif ($model->status === StatusEnum::SentToClient->value) {
            $this->addProductLinesToInvoice($invoice, $model);
            $invoice->send();
            $invoice->markAsSentToClient();
        } else {
            $this->addProductLinesToInvoice($invoice, $model);
        }

        return $invoice;
    }

    private function addProductLinesToInvoice(Invoice $invoice, InvoiceModel $model): void
    {
        foreach ($model->productLines as $productLineModel) {
            $productLine = new ProductLine(
                Uuid::fromString($productLineModel->id),
                $productLineModel->name,
                $productLineModel->quantity,
                $productLineModel->price
            );

            $invoice->addProductLine($productLine);
        }
    }
} 