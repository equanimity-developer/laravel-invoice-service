<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use RuntimeException;

final class InvalidInvoiceStatusTransitionException extends RuntimeException
{
} 