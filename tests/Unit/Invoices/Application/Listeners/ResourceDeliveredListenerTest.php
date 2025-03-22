<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\Listeners;

use Modules\Invoices\Application\Listeners\ResourceDeliveredListener;
use Modules\Invoices\Application\Services\InvoiceService;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use Modules\Notifications\Api\NotificationFacadeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class ResourceDeliveredListenerTest extends TestCase
{
    private InvoiceRepositoryInterface|MockObject $invoiceRepository;
    private NotificationFacadeInterface|MockObject $notificationFacade;
    private ResourceDeliveredListener $listener;

    protected function setUp(): void
    {
        $this->invoiceRepository = $this->createMock(InvoiceRepositoryInterface::class);
        $this->notificationFacade = $this->createMock(NotificationFacadeInterface::class);
        
        $invoiceService = new InvoiceService(
            $this->invoiceRepository,
            $this->notificationFacade
        );
        
        $this->listener = new ResourceDeliveredListener($invoiceService);
    }

    public function testHandleEvent(): void
    {
        // Arrange
        $resourceId = Uuid::uuid4();
        $event = new ResourceDeliveredEvent($resourceId);

        $this->invoiceRepository->expects($this->once())
            ->method('findById')
            ->with($this->callback(fn ($uuid) => $uuid->toString() === $resourceId->toString()))
            ->willReturn(null); // We only need to verify the call, not the actual behavior

        // Act
        $this->listener->handle($event);
        
        // Assert
        // Assertion is handled by the mock expectations above
    }
} 