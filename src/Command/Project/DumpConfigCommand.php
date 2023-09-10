<?php declare(strict_types=1);

namespace SunlightConsole\Command\Project;

use SunlightConsole\Command;
use SunlightConsole\JsonObject;

class DumpConfigCommand extends Command
{
    function getHelp(): string
    {
        return 'dump resolved project configuration';
    }

    function run(array $args): int
    {
        $projectConfig = $this->cli->getProjectConfig();

        $this->output->write(JsonObject::fromData($projectConfig->toArray())->encode());

        return 0;
    }
}
