<?php

declare(strict_types=1);

namespace Apiato\Core\Loaders;

use Apiato\Core\Foundation\Facades\Apiato;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * This class is different from other loaders as it is not called by AutoLoaderTrait
 * It is called "database/seeders/DatabaseSeeder.php", Laravel main seeder and only load seeder from
 * Containers (not from "app/Ship/seeders").
 */
trait SeederLoaderTrait
{
    /**
     * Default seeders directory for containers and port.
     */
    protected string $seedersPath = '/Data/Seeders';

    public function runLoadingSeeders(): void
    {
        $this->loadSeedersFromContainers();
    }

    private function loadSeedersFromContainers(): void
    {
        $seedersClasses = new Collection();

        $containersDirectories = [];

        foreach (Apiato::getSectionNames() as $sectionName) {
            foreach (Apiato::getSectionContainerNames($sectionName) as $containerName) {
                $containersDirectories[] = base_path('app/Containers/' . $sectionName . '/' . $containerName . $this->seedersPath);
            }
        }

        $seedersClasses       = $this->findSeedersClasses($containersDirectories, $seedersClasses);
        $orderedSeederClasses = $this->sortSeeders($seedersClasses);

        $this->loadSeeders($orderedSeederClasses);
    }

    private function findSeedersClasses(array $directories, $seedersClasses)
    {
        foreach ($directories as $directory) {
            if (File::isDirectory($directory)) {
                $files = File::allFiles($directory);

                foreach ($files as $seederClass) {
                    if (File::isFile($seederClass->getPath())) {

                        // Do not seed the classes now, just store them in a collection.
                        $seedersClasses->push(
                            Apiato::getClassFullNameFromFile(
                                $seederClass->getPathname()
                            )
                        );
                    }
                }
            }
        }

        return $seedersClasses;
    }

    private function sortSeeders($seedersClasses): Collection
    {
        $orderedSeederClasses = new Collection();

        if ($seedersClasses->isEmpty()) {
            return $orderedSeederClasses;
        }

        foreach ($seedersClasses as $key => $seederFullClassName) {
            // If the class full namespace contain '_' it means it needs to be seeded in order.
            if (str_contains($seederFullClassName, '_')) {
                // Move all the seeder classes that needs to be seeded in order to their own Collection.
                $orderedSeederClasses->push($seederFullClassName);
                // Delete the moved classes from the original collection.
                $seedersClasses->forget($key);
            }
        }

        // Sort the classes that needed to be ordered.
        // Get the order number form the end of each class name.
        $orderedSeederClasses = $orderedSeederClasses->sortBy(fn ($seederFullClassName): string => substr($seederFullClassName, strpos($seederFullClassName, '_') + 1));

        // Append the randomly ordered seeder classes to the end of the ordered seeder classes.
        foreach ($seedersClasses as $seederClass) {
            $orderedSeederClasses->push($seederClass);
        }

        return $orderedSeederClasses;
    }

    private function loadSeeders(Collection $seedersClasses): void
    {
        foreach ($seedersClasses as $seeder) {
            // Seed it with call.
            $seeder::WITH_TRANSACTIONS ? DB::transaction(fn () => $this->call($seeder)) : $this->call($seeder);
        }
    }
}
