<?php

declare(strict_types=1);

namespace Apiato\Core\Loaders;

use Illuminate\Support\Facades\File;

trait ConfigsLoaderTrait
{
    public function loadConfigsFromShip(): void
    {
        $portConfigsDirectory = base_path('app/Ship/Configs');

        $this->loadConfigs($portConfigsDirectory);
    }

    private function loadConfigs(string $configFolder): void
    {
        if (File::isDirectory($configFolder)) {
            $files = File::files($configFolder);

            foreach ($files as $file) {
                $name = File::name((string)$file);
                $path = sprintf('%s/%s.php', $configFolder, $name);

                $this->mergeConfigFrom($path, $name);
            }
        }
    }

    public function loadConfigsFromContainers(string $containerPath): void
    {
        $containerConfigsDirectory = $containerPath . '/Configs';
        $this->loadConfigs($containerConfigsDirectory);
    }
}
