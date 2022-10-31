<?php

declare(strict_types=1);

namespace Apiato\Core\Commands;

use Apiato\Core\Abstracts\Commands\ConsoleCommand;
use Apiato\Core\Foundation\Facades\Apiato;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Output\ConsoleOutput;

class ListActionsCommand extends ConsoleCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apiato:list:actions {--withfilename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all Actions in the Application.';

    public function __construct(protected ConsoleOutput $console)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        foreach (Apiato::getSectionNames() as $sectionName) {
            foreach (Apiato::getSectionContainerNames($sectionName) as $containerName) {
                $this->console->writeln(sprintf('<fg=yellow> [%s]</fg=yellow>', $containerName));

                $directory = base_path('app/Containers/' . $sectionName . '/' . $containerName . '/Actions');

                if (File::isDirectory($directory)) {
                    $files = File::allFiles($directory);

                    foreach ($files as $action) {
                        // Get the file name as is
                        $fileName = $action->getFilename();
                        $originalFileName = $fileName;

                        // Remove the Action.php postfix from each file name
                        // Further, remove the `.php', if the file does not end on 'Action.php'
                        $fileName = str_replace(['Action.php', '.php'], '', $fileName);

                        // UnCamelize the word and replace it with spaces
                        $fileName = uncamelize($fileName);

                        // Check if flag exists
                        $includeFileName = '';

                        if ($this->option('withfilename')) {
                            $includeFileName = sprintf('<fg=red>(%s)</fg=red>', $originalFileName);
                        }

                        $this->console->writeln(sprintf('<fg=green>  - %s</fg=green>  %s', $fileName, $includeFileName));
                    }
                }
            }
        }
    }
}
