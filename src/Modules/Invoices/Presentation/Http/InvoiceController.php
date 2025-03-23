<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http;

use Illuminate\Http\JsonResponse;
use Modules\Invoices\Application\Services\InvoiceServiceInterface;
use Modules\Invoices\Domain\Translation\TranslatorInterface;
use Modules\Invoices\Presentation\Exceptions\InvoiceExceptionHandler;
use Modules\Invoices\Presentation\Requests\AddProductLineRequest;
use Modules\Invoices\Presentation\Requests\CreateInvoiceRequest;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class InvoiceController
{
    public function __construct(
        private InvoiceServiceInterface $invoiceService,
        private TranslatorInterface $translator,
        private InvoiceExceptionHandler $exceptionHandler,
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

        if (!$invoice) {
            return new JsonResponse(
                ['error' => $this->translator->translate('invoices.errors.not_found')],
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

        return new JsonResponse(
            [
                'invoice' => $invoice,
                'message' => $this->translator->translate('invoices.success.created'),
            ],
            Response::HTTP_CREATED
        );
    }

    public function addProductLine(string $id, AddProductLineRequest $request): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->addProductLine(
                $id,
                $request->input('product_name'),
                $request->integer('quantity'),
                $request->integer('unit_price')
            );

            if (!$invoice) {
                return new JsonResponse(
                    ['error' => $this->translator->translate('invoices.errors.not_found')],
                    Response::HTTP_NOT_FOUND
                );
            }

            return new JsonResponse([
                'invoice' => $invoice,
                'message' => $this->translator->translate('invoices.success.product_line_added'),
            ], Response::HTTP_CREATED);
        } catch (Throwable $e) {
            return $this->exceptionHandler->handle($e);
        }
    }

    public function send(string $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->sendInvoice($id);

            if (!$invoice) {
                return new JsonResponse(
                    ['error' => $this->translator->translate('invoices.errors.not_found')],
                    Response::HTTP_NOT_FOUND
                );
            }

            return new JsonResponse([
                'invoice' => $invoice,
                'message' => $this->translator->translate('invoices.success.sent'),
            ]);
        } catch (Throwable $e) {
            return $this->exceptionHandler->handle($e);
        }
    }
}
