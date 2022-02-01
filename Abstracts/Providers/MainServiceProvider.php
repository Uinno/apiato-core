<?php

declare(strict_types=1);

namespace Apiato\Core\Abstracts\Providers;

use Apiato\Core\Loaders\AliasesLoaderTrait;
use Apiato\Core\Loaders\ProvidersLoaderTrait;
use Illuminate\Support\ServiceProvider as LaravelAppServiceProvider;

abstract class MainServiceProvider extends LaravelAppServiceProvider
{
    use AliasesLoaderTrait;
    use ProvidersLoaderTrait;

    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
    }

    /**
     * Register anything in the container.
     */
    public function register(): void
    {
        $this->loadServiceProviders();
        $this->loadAliases();
    }
}
