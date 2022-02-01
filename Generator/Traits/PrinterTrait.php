<?php

declare(strict_types=1);

namespace Apiato\Core\Generator\Traits;

trait PrinterTrait
{
    public function printStartedMessage(string $containerName, string $fileName): void
    {
        $this->printInfoMessage('> Generating (' . $fileName . ') in (' . $containerName . ') Container.');
    }

    public function printInfoMessage(string $message): void
    {
        $this->info($message);
    }

    public function printFinishedMessage(string $type): void
    {
        $this->printInfoMessage($type . ' generated successfully.');
    }

    public function printErrorMessage(string $message): void
    {
        $this->error($message);
    }
}
