<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Translation;

interface TranslatorInterface
{
    public function translate(string $key, array $parameters = []): string;
}
