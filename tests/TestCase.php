<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Databases the suite is allowed to touch.
     *
     * @var list<string>
     */
    private const ALLOWED_DATABASES = ['brgy_testing', ':memory:'];

    /**
     * Refuse to run against anything but the dedicated test database.
     *
     * This runs *before* `parent::setUp()` on purpose: `RefreshDatabase` hooks
     * into the parent's trait setup and migrates immediately, so a check made
     * afterwards would already have dropped the tables it was meant to protect.
     */
    protected function setUp(): void
    {
        $this->guardTestDatabase();

        parent::setUp();
    }

    /**
     * @throws RuntimeException when the configured database is not a test database
     */
    private function guardTestDatabase(): void
    {
        // Mirrors Illuminate\Support\Env precedence: $_SERVER, then $_ENV, then
        // getenv(). The application is not booted yet, so config() is unusable.
        $database = $_SERVER['DB_DATABASE']
            ?? $_ENV['DB_DATABASE']
            ?? (getenv('DB_DATABASE') ?: null);

        if (in_array($database, self::ALLOWED_DATABASES, true)) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Refusing to run tests against database [%s]: it is not one of [%s]. '
            .'PHPUnit\'s <env force="true"> only rewrites putenv()/$_ENV, so a real '
            .'DB_DATABASE in the process environment wins via $_SERVER. Run the suite '
            .'with `make test`, which passes the test overrides as real env vars.',
            $database ?? 'not set',
            implode(', ', self::ALLOWED_DATABASES),
        ));
    }
}
