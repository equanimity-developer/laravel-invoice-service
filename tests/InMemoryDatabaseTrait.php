<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

trait InMemoryDatabaseTrait
{
    use RefreshDatabase;

    /**
     * Define the test database connection.
     * 
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Set the database connection to in-memory SQLite
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Define the migrations for the test.
     * 
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->artisan('migrate');
        
        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');
        });
    }
} 