<?php declare(strict_types=1);

namespace SunlightConsole\Command\Project;

use SunlightConsole\Command;

class DumpConfigCommand extends Command
{
    function getHelp(): string
    {
        return 'dump resolved project configuration';
    }

    function run(array $args): int
    {
        $projectConfig = $this->cli->getProjectConfig();

        $this->output->write($this->utils->encodeJsonPretty($projectConfig->toArray()));

        return 0;
    }
}
