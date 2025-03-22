<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Invoices\Api\InvoiceFacadeInterface;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStatusTransitionException;
use Modules\Invoices\Domain\Exceptions\InvalidProductLineException;

final class SendInvoiceController
{
    public function __construct(
        private InvoiceFacadeInterface $invoiceFacade,
    ) {
    }
    
    public function __invoke(string $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceFacade->sendInvoice($id);
            
            if (!$invoice) {
                return new JsonResponse(
                    ['error' => 'Invoice not found'],
                    Response::HTTP_NOT_FOUND
                );
            }
            
            return new JsonResponse([
                'message' => 'Invoice has been sent successfully',
                'data' => $invoice,
            ]);
        } catch (InvalidInvoiceStatusTransitionException|InvalidProductLineException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
} 