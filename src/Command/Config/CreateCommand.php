<?php declare(strict_types=1);

namespace SunlightConsole\Command\Config;

use SunlightConsole\Command;
use Sunlight\Util\ConfigurationFile;
use Sunlight\Util\StringGenerator;

class CreateCommand extends Command
{
    function getHelp(): string
    {
        return 'create config.php with default contents';
    }

    function run(array $args): int
    {
        $this->utils->ensureCmsClassesAvailable();

        $configPath = $this->cli->getProjectRoot() . '/config.php';
        $configTemplatePath = $this->cli->getProjectRoot() . '/system/config_template.php';

        if (is_file($configPath)) {
            $this->output->write('The config.php file already exists');

            return 0;
        }

        $config = new ConfigurationFile($configPath);

        foreach (require $configTemplatePath as $key => $value) {
            $config[$key] = $value;
        }

        $config['secret'] = StringGenerator::generateString(64);

        $config->save();
        $this->output->write('The config.php file has been created');

        return 0;
    }
}
