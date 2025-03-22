<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Presentation\Http\InvoiceController;
use Ramsey\Uuid\Validator\GenericValidator;

Route::pattern('id', (new GenericValidator)->getPattern());

Route::prefix('api')->group(function (): void {
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::post('/invoices/{id}/product-lines', [InvoiceController::class, 'addProductLine'])->name('invoices.product-lines.store');
    Route::post('/invoices/{id}/send', [InvoiceController::class, 'send'])->name('invoices.send');
}); 