<?php

declare(strict_types=1);

namespace Apiato\Core\Commands;

use Apiato\Core\Abstracts\Commands\ConsoleCommand;
use Apiato\Core\Foundation\Facades\Apiato;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Output\ConsoleOutput;

class ListTasksCommand extends ConsoleCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apiato:list:tasks {--withfilename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all Tasks in the Application.';

    public function __construct(protected ConsoleOutput $console)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        foreach (Apiato::getSectionNames() as $sectionName) {
            foreach (Apiato::getSectionContainerNames($sectionName) as $containerName) {
                $this->console->writeln(sprintf('<fg=yellow> [%s]</fg=yellow>', $containerName));

                $directory = base_path('app/Containers/' . $sectionName . '/' . $containerName . '/Tasks');

                if (File::isDirectory($directory)) {
                    $files = File::allFiles($directory);

                    foreach ($files as $action) {
                        // Get the file name as is
                        $fileName = $action->getFilename();
                        $originalFileName = $fileName;

                        // Remove the Task.php postfix from each file name
                        // Further, remove the `.php', if the file does not end on 'Task.php'
                        $fileName = str_replace(['Task.php', '.php'], '', $fileName);

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
