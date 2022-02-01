<?php

declare(strict_types=1);

namespace Apiato\Core\Commands;

use Apiato\Core\Abstracts\Commands\ConsoleCommand;
use Apiato\Core\Foundation\Apiato;

class GetApiatoVersionCommand extends ConsoleCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apiato';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the current Apiato version.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info(Apiato::VERSION);
    }
}
