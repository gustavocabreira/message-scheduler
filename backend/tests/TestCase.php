<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp(); // boots app + runs RefreshDatabase on default sqlite connection

        $this->configureTenancyConnections();
        $this->migrateLandlord();
    }

    /**
     * Override the landlord and tenant connections to use isolated
     * SQLite in-memory databases, independent of the .env configuration.
     */
    private function configureTenancyConnections(): void
    {
        $sqliteConfig = [
            'driver'              => 'sqlite',
            'database'            => ':memory:',
            'prefix'              => '',
            'foreign_key_constraints' => true,
        ];

        foreach (['landlord', 'tenant'] as $connection) {
            config(["database.connections.{$connection}" => $sqliteConfig]);
            DB::purge($connection);
        }
    }

    /**
     * Run landlord migrations on the freshly configured in-memory connection.
     * Must run after configureTenancyConnections() so the connection is SQLite.
     */
    private function migrateLandlord(): void
    {
        $this->artisan('migrate', [
            '--path'  => 'database/migrations/landlord',
            '--force' => true,
        ]);
    }
}
