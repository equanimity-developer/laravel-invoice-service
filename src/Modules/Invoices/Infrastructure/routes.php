<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Infrastructure\Http\Controllers\Api\InvoiceController;
use Modules\Invoices\Infrastructure\Http\Controllers\Api\ProductLineController;
use Modules\Invoices\Infrastructure\Http\Controllers\Api\SendInvoiceController;
use Ramsey\Uuid\Validator\GenericValidator;

Route::pattern('id', (new GenericValidator)->getPattern());

Route::prefix('api')->group(function (): void {
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('api.invoices.index');
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('api.invoices.show');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('api.invoices.store');
    Route::post('/invoices/{id}/product-lines', [ProductLineController::class, 'store'])->name('api.invoices.product-lines.store');
    Route::post('/invoices/{id}/send', SendInvoiceController::class)->name('api.invoices.send');
}); 