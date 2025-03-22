<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Listeners;

use Modules\Invoices\Application\Services\InvoiceService;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;

final readonly class ResourceDeliveredListener
{
    public function __construct(
        private InvoiceService $invoiceService,
    ) {
    }

    public function handle(ResourceDeliveredEvent $event): void
    {
        $this->invoiceService->markAsSentToClient($event->resourceId);
    }
} 