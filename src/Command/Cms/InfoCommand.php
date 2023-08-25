<?php declare(strict_types=1);

namespace SunlightConsole\Command\Cms;

use Sunlight\Core;
use Sunlight\VersionChecker;
use SunlightConsole\Command;

class InfoCommand extends Command
{
    function getHelp(): string
    {
        return 'show information about the CMS';
    }

    function run(array $args): int
    {
        // check if classes are available
        if (!class_exists(Core::class)) {
            $this->output->write('Installed version: none');

            return 0;
        }

        // version info
        $this->output->write('Installed version: %s', Core::VERSION);
        $this->output->write('Distribution type: %s', Core::DIST);

        // latest version info
        if (is_file($this->cli->getProjectRoot() . '/config.php')) {
            $this->utils->initCms($this->cli->getProjectRoot());

            $versionData = VersionChecker::check();

            if ($versionData !== null) {
                $this->output->write('Latest version: %s', $versionData['latestVersion']);
            }
        }

        return 0;
    }
}
