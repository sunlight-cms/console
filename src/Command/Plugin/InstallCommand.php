<?php declare(strict_types=1);

namespace SunlightConsole\Command\Plugin;

use SunlightConsole\Command;
use SunlightConsole\Argument\ArgumentDefinition;
use Sunlight\Core;
use Sunlight\Plugin\PluginArchive;
use Sunlight\Util\Filesystem;
use SunlightConsole\Util\CmsFacade;
use SunlightConsole\Util\FileDownloader;

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

    function run(CmsFacade $cms, FileDownloader $fileDownloader, array $args): int
    {
        $cms->init();
        
        if (isset($args['from-path'])) {
            $path = $args['from-path'];
        } elseif (isset($args['from-url'])) {
            $tmpFile = Filesystem::createTmpFile();
            $path = $tmpFile->getPathname();

            $fileDownloader->download($args['from-url'], $path);
        } else {
            $this->output->fail('Specify --from-path or --from-url');
        }

        $merge = isset($args['skip-existing']);
        $archive = new PluginArchive(Core::$pluginManager, $path);
        $plugins = $archive->extract($merge, $failedPlugins);

        foreach ($plugins as $plugin) {
            $this->output->write('Added %s', $plugin);
        }

        foreach ($failedPlugins as $plugin) {
            $this->output->write($merge ? 'Skipped %s' : 'Plugin %s already exists', $plugin);
        }

        if (empty($plugins)) {
            $this->output->write('No plugins added');

            return 1;
        }

        Core::$pluginManager->clearCache();

        return 0;
    }
}
