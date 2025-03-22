<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Invoices\Api\InvoiceFacadeInterface;
use Modules\Invoices\Domain\Exceptions\InvalidProductLineException;
use Modules\Invoices\Presentation\Requests\AddProductLineRequest;

final class ProductLineController
{
    public function __construct(
        private InvoiceFacadeInterface $invoiceFacade,
    ) {
    }
    
    public function store(AddProductLineRequest $request, string $invoiceId): JsonResponse
    {
        try {
            $productLine = $this->invoiceFacade->addProductLine(
                $invoiceId,
                $request->input('product_name'),
                (int) $request->input('quantity'),
                (int) $request->input('unit_price')
            );
            
            if (!$productLine) {
                return new JsonResponse(
                    ['error' => 'Invoice not found'],
                    Response::HTTP_NOT_FOUND
                );
            }
            
            return new JsonResponse(
                ['message' => 'Product line successfully added', 'data' => $productLine],
                Response::HTTP_CREATED
            );
        } catch (InvalidProductLineException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
} 