<?php declare(strict_types=1);

namespace SunlightConsole\Command\Config;

use SunlightConsole\Command;
use Sunlight\Util\ConfigurationFile;
use Sunlight\Util\StringGenerator;
use SunlightConsole\Project;
use SunlightConsole\Util\CmsFacade;

class CreateCommand extends Command
{
    function getHelp(): string
    {
        return 'create config.php with default contents';
    }

    function run(Project $project, CmsFacade $cms, array $args): int
    {
        $cms->ensureClassesAvailable();

        $configPath = $project->getRoot() . '/config.php';
        $configTemplatePath = $project->getRoot() . '/system/config_template.php';

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
