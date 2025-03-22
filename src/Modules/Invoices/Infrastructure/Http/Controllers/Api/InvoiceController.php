<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Invoices\Api\Dtos\CreateInvoiceRequest;
use Modules\Invoices\Api\InvoiceFacadeInterface;
use Modules\Invoices\Presentation\Requests\CreateInvoiceRequest as ValidatedCreateInvoiceRequest;

final class InvoiceController
{
    public function __construct(
        private InvoiceFacadeInterface $invoiceFacade,
    ) {
    }
    
    public function index(): JsonResponse
    {
        $invoices = $this->invoiceFacade->getAllInvoices();
        
        return new JsonResponse($invoices);
    }
    
    public function show(string $id): JsonResponse
    {
        $invoice = $this->invoiceFacade->getInvoice($id);
        
        if (!$invoice) {
            return new JsonResponse(
                ['error' => 'Invoice not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        
        return new JsonResponse($invoice);
    }
    
    public function store(ValidatedCreateInvoiceRequest $request): JsonResponse
    {
        $requestData = new CreateInvoiceRequest(
            $request->input('customer_name'),
            $request->input('customer_email'),
        );
        
        $invoice = $this->invoiceFacade->createInvoice($requestData);
        
        return new JsonResponse(
            ['message' => 'Invoice successfully created', 'data' => $invoice],
            Response::HTTP_CREATED
        );
    }
} 