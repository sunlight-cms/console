<?php declare(strict_types=1);

namespace SunlightConsole\Command\Cache;

use Sunlight\Composer\ComposerBridge;
use Sunlight\Core;
use SunlightConsole\Argument\ArgumentDefinition;
use SunlightConsole\Command;
use SunlightConsole\Util\CmsFacade;

class ClearCommand extends Command
{
    function getHelp(): string
    {
        return 'clear the cache';
    }

    protected function defineArguments(): array
    {
        return [
            ArgumentDefinition::flagOrOption('plugin', 'clear plugin cache (can pass specific plugin name or ID)'),
        ];
    }

    function run(CmsFacade $cms, array $args): int
    {
        if (isset($args['plugin'])) {
            $cms->init();

            if ($args['plugin'] === '') {
                $this->output->write('Clearing plugin cache');
                Core::$pluginManager->clearCache();
            } else {
                $plugin = $cms->findPlugin($args['plugin']);

                $plugin !== null
                    or $this->output->fail('Could not find plugin "%s"', $args['plugin']);

                $this->output->write('Clearing cache for plugin "%s"', $plugin->getId());
                $plugin->getCache()->clear();
            }
        } else {
            $this->output->write('Clearing cache');
            ComposerBridge::clearCache();
        }

        $this->output->write('Done');

        return 0;
    }
}
