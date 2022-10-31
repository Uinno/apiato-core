<?php

declare(strict_types=1);

namespace Apiato\Core\Abstracts\Tests\PhpUnit;

use Apiato\Core\Traits\HashIdTrait;
use Apiato\Core\Traits\TestCaseTrait;
use Apiato\Core\Traits\TestsTraits\PhpUnit\TestsAuthHelperTrait;
use Apiato\Core\Traits\TestsTraits\PhpUnit\TestsMockHelperTrait;
use Apiato\Core\Traits\TestsTraits\PhpUnit\TestsRequestHelperTrait;
use Apiato\Core\Traits\TestsTraits\PhpUnit\TestsResponseHelperTrait;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;

abstract class TestCase extends LaravelTestCase
{
    use HashIdTrait;
    use LazilyRefreshDatabase;
    use TestCaseTrait;
    use TestsAuthHelperTrait;
    use TestsMockHelperTrait;
    use TestsRequestHelperTrait;
    use TestsResponseHelperTrait;

    /**
     * The base URL to use while testing the application.
     */
    protected string $baseUrl;

    /**
     * Seed the DB on migrations
     */
    protected bool $seed = true;

    /**
     * Setup the test environment, before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Reset the test environment, after each test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Refresh the in-memory database.
     * Overridden refreshTestDatabase Trait.
     */
    protected function refreshInMemoryDatabase(): void
    {
        // Migrate the database and seed the database
        $this->artisan('migrate', $this->migrateUsing());

        // Install Passport Client for Testing
        $this->setupPassportOAuth2();

        $this->app[Kernel::class]->setArtisan(null);
    }

    /**
     * Refresh a conventional test database.
     * Overridden refreshTestDatabase Trait.
     */
    protected function refreshTestDatabase(): void
    {
        if (!RefreshDatabaseState::$migrated) {
            // Migrate the database and seed the database
            $this->artisan('migrate:fresh', $this->migrateFreshUsing());
            $this->setupPassportOAuth2();

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }
}
