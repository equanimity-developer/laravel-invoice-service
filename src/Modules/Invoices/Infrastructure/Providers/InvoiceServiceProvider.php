<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Providers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Modules\Invoices\Application\Listeners\ResourceDeliveredListener;
use Modules\Invoices\Application\Services\InvoiceService;
use Modules\Invoices\Application\Services\InvoiceServiceInterface;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\Translation\TranslatorInterface;
use Modules\Invoices\Infrastructure\Eloquent\InvoiceRepository;
use Modules\Invoices\Infrastructure\Translation\LaravelTranslator;
use Modules\Invoices\Presentation\Exceptions\InvoiceExceptionHandler;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use Modules\Notifications\Api\NotificationFacadeInterface;

final class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TranslatorInterface::class, LaravelTranslator::class);

        $this->app->singleton(InvoiceRepositoryInterface::class, InvoiceRepository::class);

        $this->app->singleton(InvoiceService::class, function ($app) {
            return new InvoiceService(
                $app->make(InvoiceRepositoryInterface::class),
                $app->make(NotificationFacadeInterface::class),
                $app->make(TranslatorInterface::class)
            );
        });

        $this->app->singleton(InvoiceServiceInterface::class, InvoiceService::class);

        $this->app->singleton(InvoiceExceptionHandler::class, function ($app) {
            return new InvoiceExceptionHandler(
                $app->make(TranslatorInterface::class)
            );
        });

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
    }
}
