<?php declare(strict_types=1);

namespace SunlightConsole\Command\Plugin;

use SunlightConsole\Command;
use Sunlight\Core;

class ListCommand extends Command
{
    function getHelp(): string
    {
        return 'list all plugins';
    }

    function run(array $args): int
    {
        $this->utils->initCms($this->cli->getProjectRoot());
        
        $plugins = Core::$pluginManager->getPlugins();
        $allPlugins = $plugins->map + $plugins->inactiveMap;
        $pluginNamePadding = $this->utils->getMaxStringLength(array_keys($allPlugins));

        foreach ($allPlugins as $id => $plugin) {
            $this->output->write("%-{$pluginNamePadding}s    %s", $id, $plugin->getStatus());
        }

        return 0;
    }
}
