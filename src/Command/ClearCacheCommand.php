<?php declare(strict_types=1);

namespace SunlightConsole\Command;

use SunlightConsole\Command;
use SunlightConsole\Argument\ArgumentDefinition;
use Sunlight\Core;
use Sunlight\Composer\ComposerBridge;

class ClearCacheCommand extends Command
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

    function run(array $args): int
    {
        if (isset($args['plugin'])) {
            $this->utils->initCms($this->cli->getProjectRoot());

            if ($args['plugin'] === '') {
                $this->output->write('Clearing plugin cache');
                Core::$pluginManager->clearCache();
            } else {
                $plugin = $this->utils->findPlugin($args['plugin']);

                $plugin !== null
                    or $this->cli->fail('Could not find plugin "%s"', $args['plugin']);

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
