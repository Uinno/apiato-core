<?php

declare(strict_types=1);

namespace Apiato\Core\Generator\Commands;

use Apiato\Core\Generator\GeneratorCommand;
use Apiato\Core\Generator\Interfaces\ComponentsGenerator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SeederGenerator extends GeneratorCommand implements ComponentsGenerator
{
    /**
     * @var string
     */
    public const FORMAT_TIME = 'Y_m_d_His';

    /**
     * User required/optional inputs expected to be passed while calling the command.
     * This is a replacement of the `getArguments` function "which reads whenever it's called".
     */
    public array $inputs = [
    ];

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'apiato:generate:seeder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Seeder class';

    /**
     * The type of class being generated.
     */
    protected string $fileType = 'Seeder';

    /**
     * The structure of the file path.
     */
    protected string $pathStructure = '{section-name}/{container-name}/Data/Seeders/*';

    /**
     * The structure of the file name.
     */
    protected string $nameStructure = '{file-name}';

    /**
     * The name of the stub file.
     */
    protected string $stubName = 'seeder.stub';

    private ?string $fileParametersDate = null;

    public function getUserInputs(): ?array
    {
        // Now we need to check if there already exists a "seeder file" for this container!
        // We therefore search for a file that is named "Order_xxxx_xx_xx_xxxxxx_ClassName"
        $exists = false;

        $folder = $this->parsePathStructure($this->pathStructure, [
            'section-name'   => $this->sectionName,
            'container-name' => $this->containerName,
        ]);
        $folder = $this->getFilePath($folder);
        $folder = rtrim($folder, $this->parsedFileName . '.' . $this->getDefaultFileExtension());

        $seederName = sprintf('%sSeeder.%s', $this->containerName, $this->getDefaultFileExtension());

        // Get the content of this folder
        $files = File::allFiles($folder);
        foreach ($files as $file) {
            if (Str::endsWith($file->getFilename(), $seederName)) {
                $exists = true;
            }
        }

        if ($exists) {
            // There exists a basic seeder file for this container
            return null;
        }

        return [
            'path-parameters' => [
                'section-name'   => $this->sectionName,
                'container-name' => $this->containerName,
            ],
            'stub-parameters' => [
                '_section-name'   => Str::lower($this->sectionName),
                'section-name'    => $this->sectionName,
                '_container-name' => Str::lower($this->containerName),
                'container-name'  => $this->containerName,
                'class-name'      => $this->fileName,
            ],
            'file-parameters' => [
                'file-name' => $this->fileName,
            ],
        ];
    }

    /**
     * Get the default file name for this component to be generated.
     */
    public function getDefaultFileName(): string
    {
        return sprintf('Order_%s_%sSeeder', $this->getDate(), $this->containerName);
    }

    private function getDate(): string
    {
        if ($this->fileParametersDate === null) {
            $this->fileParametersDate = now()->format(self::FORMAT_TIME);
        }

        return $this->fileParametersDate;
    }
}
