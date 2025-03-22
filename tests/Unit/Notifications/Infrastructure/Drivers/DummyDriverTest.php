<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications\Infrastructure\Drivers;

use Illuminate\Foundation\Testing\WithFaker;
use Modules\Notifications\Infrastructure\Drivers\DummyDriver;
use PHPUnit\Framework\TestCase;

final class DummyDriverTest extends TestCase
{
    use WithFaker;

    private DummyDriver $driver;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFaker();
        $this->driver = new DummyDriver();
    }
    
    public function testSendAlwaysReturnsTrue(): void
    {
        // Arrange
        $toEmail = $this->faker->email;
        $subject = $this->faker->sentence;
        $message = $this->faker->paragraph;
        $reference = $this->faker->uuid;
        
        // Act
        $result = $this->driver->send($toEmail, $subject, $message, $reference);
        
        // Assert
        $this->assertTrue($result);
    }
} 