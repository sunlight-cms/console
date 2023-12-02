<?php declare(strict_types=1);

namespace SunlightConsole\Command\Plugin;

use Sunlight\Core;
use Sunlight\Plugin\PluginArchive;
use Sunlight\Util\Filesystem;
use SunlightConsole\Argument\ArgumentDefinition;
use SunlightConsole\Cms\CmsFacade;
use SunlightConsole\Command;
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
            ArgumentDefinition::option('mode', 'all-or-nothing|skip-existing|overwrite-existing (defaults to all-or-nothing)'),
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

        $modes = [
            'all-or-nothing' => PluginArchive::MODE_ALL_OR_NOTHING,
            'skip-existing' => PluginArchive::MODE_SKIP_EXISTING,
            'overwrite-existing' => PluginArchive::MODE_OVERWRITE_EXISTING,
        ];

        $mode = $modes[$args['mode'] ?? 'all-or-nothing'] ?? null;

        $mode !== null
            or $this->output->fail('Invalid mode');

        $archive = new PluginArchive(Core::$pluginManager, $path);
        $plugins = $archive->extract($mode, $failedPlugins);

        foreach ($plugins as $plugin) {
            $this->output->write('Added %s', $plugin);
        }

        foreach ($failedPlugins as $plugin) {
            $this->output->write('Skipped %s', $plugin);
        }

        if (empty($plugins)) {
            $this->output->write('No plugins added');

            return 1;
        }

        Core::$pluginManager->clearCache();

        return 0;
    }
}
