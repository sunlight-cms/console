<?php declare(strict_types=1);

namespace SunlightConsole;

use SunlightConsole\DependencyInjection\ServiceDefinition;

class CommandInitializer
{
    function initialize(Command $command, ServiceDefinition $def): void
    {
        $command->setName($def->tags['console.command']['name']);
    }
}
