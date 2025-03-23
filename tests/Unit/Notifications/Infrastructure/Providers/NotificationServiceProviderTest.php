<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications\Infrastructure\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Modules\Notifications\Api\NotificationFacadeInterface;
use Modules\Notifications\Infrastructure\Providers\NotificationServiceProvider;
use PHPUnit\Framework\TestCase;

final class NotificationServiceProviderTest extends TestCase
{
    private NotificationServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new NotificationServiceProvider(app());
    }

    public function testItImplementsDeferrableProvider(): void
    {
        // Assert that the provider implements the DeferrableProvider interface
        $this->assertInstanceOf(DeferrableProvider::class, $this->provider);
    }

    public function testProvidesMethod(): void
    {
        // Get the provided services
        $providedServices = $this->provider->provides();

        // Assert that the provides method returns the expected array of services
        $this->assertIsArray($providedServices);
        $this->assertContains(NotificationFacadeInterface::class, $providedServices);
        $this->assertCount(1, $providedServices);
    }
} 