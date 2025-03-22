<?php

declare(strict_types=1);

namespace Tests\Unit\Notification\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use Modules\Notifications\Application\Services\NotificationService;
use PHPUnit\Framework\TestCase;

final class NotificationServiceTest extends TestCase
{
    use WithFaker;

    private Dispatcher $dispatcher;

    private NotificationService $notificationService;

    protected function setUp(): void
    {
        $this->setUpFaker();

        $this->dispatcher = $this->createMock(Dispatcher::class);
        $this->notificationService = new NotificationService($this->dispatcher);
    }

    public function testDelivered(): void
    {
        // Arrange
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ResourceDeliveredEvent::class));
        $uuid = $this->faker->uuid();

        // Act
        $this->notificationService->delivered($uuid);

        // Assert
        // Assertion is handled by the mock expectations above
    }
}
