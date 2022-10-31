<?php

declare(strict_types=1);

namespace Apiato\Core\Loaders;

use Illuminate\Support\Facades\File;

trait MigrationsLoaderTrait
{
    public function loadMigrationsFromContainers($containerPath): void
    {
        $containerMigrationDirectory = $containerPath . '/Data/Migrations';

        $this->loadMigrations($containerMigrationDirectory);
    }

    public function loadMigrationsFromShip(): void
    {
        $shipMigrationDirectory = base_path('app/Ship/Migrations');
        $this->loadMigrations($shipMigrationDirectory);
    }

    private function loadMigrations($directory): void
    {
        if (File::isDirectory($directory)) {
            $this->loadMigrationsFrom($directory);
        }
    }
}
