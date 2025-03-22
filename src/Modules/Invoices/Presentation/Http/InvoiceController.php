<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http;

use Illuminate\Http\JsonResponse;
use Modules\Invoices\Application\Services\InvoiceServiceInterface;
use Modules\Invoices\Presentation\Requests\AddProductLineRequest;
use Modules\Invoices\Presentation\Requests\CreateInvoiceRequest;
use Symfony\Component\HttpFoundation\Response;

final readonly class InvoiceController
{
    public function __construct(
        private InvoiceServiceInterface $invoiceService,
    ) {
    }

    public function index(): JsonResponse
    {
        $invoices = $this->invoiceService->getAllInvoices();

        return new JsonResponse($invoices);
    }

    public function show(string $id): JsonResponse
    {
        $invoice = $this->invoiceService->getInvoice($id);

        if ($invoice === null) {
            return new JsonResponse(
                ['error' => 'Invoice not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse($invoice);
    }

    public function store(CreateInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->invoiceService->createInvoice(
            $request->input('customer_name'),
            $request->input('customer_email')
        );

        return new JsonResponse($invoice, Response::HTTP_CREATED);
    }

    public function addProductLine(string $invoiceId, AddProductLineRequest $request): JsonResponse
    {
        $invoice = $this->invoiceService->addProductLine(
            $invoiceId,
            $request->input('name'),
            $request->integer('quantity'),
            $request->integer('unit_price')
        );

        if ($invoice === null) {
            return new JsonResponse(
                ['error' => 'Invoice not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse($invoice);
    }

    public function send(string $id): JsonResponse
    {
        $invoice = $this->invoiceService->sendInvoice($id);

        if ($invoice === null) {
            return new JsonResponse(
                ['error' => 'Invoice not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse($invoice);
    }
} 