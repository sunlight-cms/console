<?php declare(strict_types=1);

namespace SunlightConsole\Command\Project;

use SunlightConsole\Command;
use SunlightConsole\JsonObject;
use SunlightConsole\Project;

class DumpConfigCommand extends Command
{
    function getHelp(): string
    {
        return 'dump resolved project configuration';
    }

    function run(Project $project, array $args): int
    {
        $this->output->write(JsonObject::fromData($project->getConfig()->toArray())->encode());

        return 0;
    }
}
