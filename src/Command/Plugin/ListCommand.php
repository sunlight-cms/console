<?php declare(strict_types=1);

namespace SunlightConsole\Command\Plugin;

use Sunlight\Core;
use SunlightConsole\Command;
use SunlightConsole\Util\CmsFacade;
use SunlightConsole\Util\StringHelper;

class ListCommand extends Command
{
    function getHelp(): string
    {
        return 'list all plugins';
    }

    function run(CmsFacade $cms, StringHelper $stringHelper, array $args): int
    {
        $cms->init();

        $plugins = Core::$pluginManager->getPlugins();
        $allPlugins = $plugins->map + $plugins->inactiveMap;
        $pluginNamePadding = $stringHelper->getMaxStringLength(array_keys($allPlugins));

        foreach ($allPlugins as $id => $plugin) {
            $this->output->write("%-{$pluginNamePadding}s    %s", $id, $plugin->getStatus());
        }

        return 0;
    }
}
