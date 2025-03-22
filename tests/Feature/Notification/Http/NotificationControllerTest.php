<?php

declare(strict_types=1);

namespace Tests\Feature\Notification\Http;

use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        $this->setUpFaker();

        parent::setUp();
    }

    #[DataProvider('hookActionProvider')]
    public function testHook(string $action): void
    {
        // Arrange
        $uri = route('notification.hook', [
            'action' => $action,
            'reference' => $this->faker->uuid,
        ]);

        // Act & Assert
        $this->getJson($uri)->assertOk();
    }

    public function testInvalid(): void
    {
        // Arrange
        $params = [
            'action' => 'dummy',
            'reference' => $this->faker->numberBetween(),
        ];
        $uri = route('notification.hook', $params);

        // Act & Assert
        $this->getJson($uri)->assertNotFound();
    }

    public static function hookActionProvider(): array
    {
        return [
            ['delivered'],
            ['dummy'],
        ];
    }
}
