<?php

declare(strict_types=1);

namespace Apiato\Core\Generator\Commands;

use Apiato\Core\Generator\GeneratorCommand;
use Apiato\Core\Generator\Interfaces\ComponentsGenerator;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class RequestGenerator extends GeneratorCommand implements ComponentsGenerator
{
    /**
     * User required/optional inputs expected to be passed while calling the command.
     * This is a replacement of the `getArguments` function "which reads whenever it's called".
     */
    public array $inputs = [
        ['ui', null, InputOption::VALUE_OPTIONAL, 'The user-interface to generate the Request for.'],
        ['stub', null, InputOption::VALUE_OPTIONAL, 'The stub file to load for this generator.'],
        ['transporter', null, InputOption::VALUE_OPTIONAL, 'Create a corresponding Transporter for this Request'],
        ['transportername', null, InputOption::VALUE_OPTIONAL, 'The name of the Transporter to be assigned'],
    ];

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'apiato:generate:request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Request class';

    /**
     * The type of class being generated.
     */
    protected string $fileType = 'Request';

    /**
     * The structure of the file path.
     */
    protected string $pathStructure = '{section-name}/{container-name}/UI/{user-interface}/Requests/*';

    /**
     * The structure of the file name.
     */
    protected string $nameStructure = '{file-name}';

    /**
     * The name of the stub file.
     */
    protected string $stubName = 'requests/generic.stub';

    public function getUserInputs(): array
    {
        $ui          = Str::lower($this->checkParameterOrChoice('ui', 'Select the UI for the controller', ['API', 'WEB'], 0));
        $stub        = $this->option('stub');

        // Load a new stub-file if generating container otherwise use generic
        $this->stubName = $stub ? 'requests/' . Str::lower($stub) . '.stub' : $this->stubName;
        $tableName      = Str::lower(Str::snake(Pluralizer::plural($this->containerName)));

        $transporter = $this->checkParameterOrConfirm('transporter', 'Would you like to create a corresponding Transporter for this Request?', true);

        $transporterClass     = '';
        $transporterNamespace = '';

        if ($transporter) {
            $transporterName = $this->checkParameterOrAsk('transportername', 'Enter the Name of the corresponding Transporter to be assigned');

            $transporterClass     = $transporterName;
            $transporterNamespace = 'App\\Containers\\' . $this->sectionName . '\\' . $this->containerName . '\\Data\\Transporters\\' . $transporterName;

            // Now create the Transporter
            $this->call('apiato:generate:transporter', [
                '--section'   => $this->sectionName,
                '--container' => $this->containerName,
                '--file'      => $transporterName,
                '--stub'      => $stub,
            ]);
        }

        return [
            'path-parameters' => [
                'section-name'   => $this->sectionName,
                'container-name' => $this->containerName,
                'user-interface' => Str::upper($ui),
            ],
            'stub-parameters' => [
                '_section-name'        => Str::lower($this->sectionName),
                'section-name'         => $this->sectionName,
                '_container-name'      => Str::lower($this->containerName),
                'container-name'       => $this->containerName,
                'class-name'           => $this->fileName,
                'user-interface'       => Str::upper($ui),
                'transporterClass'     => $transporterClass,
                'transporterNamespace' => $transporterNamespace,
                'table-name'           => $tableName,
                'container-name-lower' => Str::lower($this->containerName),
            ],
            'file-parameters' => [
                'file-name' => $this->fileName,
            ],
        ];
    }
}
