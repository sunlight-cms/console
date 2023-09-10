<?php declare(strict_types=1);

namespace SunlightConsole\Command\Config;

use Sunlight\Util\ConfigurationFile;
use SunlightConsole\Command;

class DumpCommand extends Command
{
    function getHelp(): string
    {
        return 'dump config.php contents';
    }

    function run(array $args): int
    {
        $this->utils->ensureCmsClassesAvailable();

        $configPath = $this->cli->getProjectRoot() . '/config.php';

        if (!is_file($configPath)) {
            $this->output->write('The config.php file does not exist');

            return 1;
        }

        $config = new ConfigurationFile($configPath);
        
        $this->output->write($this->utils->dump($config->toArray()));

        return 0;
    }
}
