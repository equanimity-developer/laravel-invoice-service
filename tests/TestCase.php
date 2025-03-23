<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mockery\MockInterface;
use Modules\Invoices\Domain\Translation\TranslatorInterface;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock translator to always return keys for testing
        $this->mock(TranslatorInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('translate')
                ->withAnyArgs()
                ->andReturnUsing(function (string $key) {
                    return $key;
                });
        });
    }
}
