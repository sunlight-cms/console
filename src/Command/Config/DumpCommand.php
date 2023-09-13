<?php declare(strict_types=1);

namespace SunlightConsole\Command\Config;

use Sunlight\Util\ConfigurationFile;
use SunlightConsole\Command;
use SunlightConsole\Project;
use SunlightConsole\Util\CmsFacade;
use SunlightConsole\Util\Formatter;

class DumpCommand extends Command
{
    function getHelp(): string
    {
        return 'dump config.php contents';
    }

    function run(Project $project, CmsFacade $cms, Formatter $formatter, array $args): int
    {
        $cms->ensureClassesAvailable();

        $configPath = $project->getRoot() . '/config.php';

        if (!is_file($configPath)) {
            $this->output->write('The config.php file does not exist');

            return 1;
        }

        $config = new ConfigurationFile($configPath);
        
        $this->output->write($formatter->dump($config->toArray()));

        return 0;
    }
}
