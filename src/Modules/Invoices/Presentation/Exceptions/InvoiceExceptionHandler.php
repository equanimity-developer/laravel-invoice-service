<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Exceptions;

use Illuminate\Http\JsonResponse;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStatusTransitionException;
use Modules\Invoices\Domain\Exceptions\InvalidProductLineException;
use Modules\Invoices\Domain\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class InvoiceExceptionHandler
{
    private const ERROR_CODE_TO_TRANSLATION_KEY = [
        'invalid_status_transition_send' => 'invoices.errors.invalid_status_transition_send',
        'invalid_status_transition_mark_sent' => 'invoices.errors.invalid_status_transition_mark_sent',
        'no_product_lines' => 'invoices.errors.no_product_lines',
        'invalid_product_lines' => 'invoices.errors.invalid_product_lines',
    ];

    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public function handle(Throwable $exception): JsonResponse
    {
        return match (true) {
            $exception instanceof InvalidInvoiceStatusTransitionException => $this->handleInvalidStatusTransition($exception),
            $exception instanceof InvalidProductLineException => $this->handleInvalidProductLine($exception),
            default => $this->handleGenericException(),
        };
    }

    private function handleInvalidStatusTransition(InvalidInvoiceStatusTransitionException $exception): JsonResponse
    {
        $errorCode = $exception->getMessage();
        $translationKey = self::ERROR_CODE_TO_TRANSLATION_KEY[$errorCode] ?? 'invoices.errors.invalid_status_transition';

        $message = $this->translator->translate(
            $translationKey,
            $translationKey === 'invoices.errors.invalid_status_transition' ? ['message' => $errorCode] : []
        );

        return new JsonResponse(
            ['error' => $message],
            Response::HTTP_BAD_REQUEST
        );
    }

    private function handleInvalidProductLine(InvalidProductLineException $exception): JsonResponse
    {
        $errorCode = $exception->getMessage();
        $translationKey = self::ERROR_CODE_TO_TRANSLATION_KEY[$errorCode] ?? 'invoices.errors.product_line';

        $message = $this->translator->translate(
            $translationKey,
            $translationKey === 'invoices.errors.product_line' ? ['message' => $errorCode] : []
        );

        return new JsonResponse(
            ['error' => $message],
            Response::HTTP_BAD_REQUEST
        );
    }

    private function handleGenericException(): JsonResponse
    {
        return new JsonResponse(
            ['error' => $this->translator->translate('invoices.errors.generic')],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
