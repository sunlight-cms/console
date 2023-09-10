<?php declare(strict_types=1);

namespace SunlightConsole\Command\Plugin;

use SunlightConsole\Command;
use SunlightConsole\Argument\ArgumentDefinition;
use Sunlight\Core;
use Sunlight\Plugin\PluginArchive;
use Sunlight\Util\Filesystem;

class InstallCommand extends Command
{
    function getHelp(): string
    {
        return 'install plugin from a ZIP file or an URL';
    }

    protected function defineArguments(): array
    {
        return [
            ArgumentDefinition::option('from-path', 'path to a plugin ZIP file'),
            ArgumentDefinition::option('from-url', 'plugin ZIP download URL'),
            ArgumentDefinition::flag('skip-existing', 'skip existing plugins'),
        ];
    }

    function run(array $args): int
    {
        $this->utils->initCms($this->cli->getProjectRoot());
        
        if (isset($args['from-path'])) {
            $path = $args['from-path'];
        } elseif (isset($args['from-url'])) {
            $tmpFile = Filesystem::createTmpFile();
            $path = $tmpFile->getPathname();

            $this->output->log('Downloading %s', $args['from-url']);
            $this->utils->downloadFile($args['from-url'], $path);
        } else {
            $this->cli->fail('Specify --from-path or --from-url');
        }

        $merge = isset($args['skip-existing']);
        $archive = new PluginArchive(Core::$pluginManager, $path);
        $plugins = $archive->extract($merge, $failedPlugins);

        foreach ($plugins as $plugin) {
            $this->output->log('Added %s', $plugin);
        }

        foreach ($failedPlugins as $plugin) {
            $this->output->log($merge ? 'Skipped %s' : 'Plugin %s already exists', $plugin);
        }

        if (empty($plugins)) {
            $this->output->log('No plugins added');

            return 1;
        }

        Core::$pluginManager->clearCache();

        return 0;
    }
}
