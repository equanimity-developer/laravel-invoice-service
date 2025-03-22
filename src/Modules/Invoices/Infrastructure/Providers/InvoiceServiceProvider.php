<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Providers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Modules\Invoices\Application\Listeners\ResourceDeliveredListener;
use Modules\Invoices\Application\Services\InvoiceService;
use Modules\Invoices\Application\Services\InvoiceServiceInterface;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Invoices\Infrastructure\Eloquent\InvoiceRepository;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use Modules\Notifications\Api\NotificationFacadeInterface;

final class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InvoiceRepositoryInterface::class, InvoiceRepository::class);

        $this->app->singleton(InvoiceService::class, function ($app) {
            return new InvoiceService(
                $app->make(InvoiceRepositoryInterface::class),
                $app->make(NotificationFacadeInterface::class)
            );
        });

        $this->app->singleton(InvoiceServiceInterface::class, InvoiceService::class);

        $this->app->singleton(ResourceDeliveredListener::class, function ($app) {
            return new ResourceDeliveredListener(
                $app->make(InvoiceServiceInterface::class)
            );
        });
    }

    public function boot(): void
    {
        $this->app->make(Dispatcher::class)->listen(
            ResourceDeliveredEvent::class,
            [ResourceDeliveredListener::class, 'handle']
        );
        
        // In DDD architecture, the Presentation layer should handle all external API endpoints,
        // not the Infrastructure layer. The routes are now defined in the Presentation layer.
        // $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
