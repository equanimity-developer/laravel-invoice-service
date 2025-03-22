<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Translation;

use Modules\Invoices\Domain\Translation\TranslatorInterface;

final class LaravelTranslator implements TranslatorInterface
{
    public function translate(string $key, array $parameters = []): string
    {
        return __($key, $parameters);
    }
}
