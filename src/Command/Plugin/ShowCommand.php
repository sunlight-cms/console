<?php declare(strict_types=1);

namespace SunlightConsole\Command\Plugin;

use SunlightConsole\Command;
use SunlightConsole\Argument\ArgumentDefinition;

class ShowCommand extends Command
{
    function getHelp(): string
    {
        return 'show information about a plugin';
    }

    function defineArguments(): array
    {
        return [
            ArgumentDefinition::flag('object', 'dump the plugin object'),
            ArgumentDefinition::flag('options', 'dump the plugin options'),
            ArgumentDefinition::argument(0, 'plugin', 'plugin name or ID',  true),
        ];
    }

    function run(array $args): int
    {
        $this->utils->initCms($this->cli->getProjectRoot());

        $plugin = $this->utils->findPlugin($args['plugin']);

        $plugin !== null
            or $this->cli->fail('Could not find plugin "%s"', $args['plugin']);

        if (isset($args['object'])) {
            $this->output->write($this->utils->dump($plugin, 2));
        } elseif (isset($args['options'])) {
            $this->output->write($this->utils->dump($plugin->getOptions(), 4));
        } else {
            $this->output->write('ID: %s', $plugin->getId());
            $this->output->write('Type: %s', $plugin->getType());
            $this->output->write('Directory: %s', $plugin->getDirectory());
            $this->output->write('Implementation: %s', get_class($plugin));
            $this->output->write('Status: %s', $plugin->getStatus());
            $this->output->write('Errors: %d', count($plugin->getErrors()));
            
            foreach ($plugin->getErrors() as $error) {                
                $this->output->write('    %s', $error);
            }
        }

        return 0;
    }
}
