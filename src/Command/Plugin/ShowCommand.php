<?php declare(strict_types=1);

namespace SunlightConsole\Command\Plugin;

use SunlightConsole\Argument\ArgumentDefinition;
use SunlightConsole\Command;
use SunlightConsole\Util\CmsFacade;
use SunlightConsole\Util\Formatter;

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
            ArgumentDefinition::argument(0, 'plugin', 'plugin name or ID', true),
        ];
    }

    function run(CmsFacade $cms, Formatter $formatter, array $args): int
    {
        $cms->init();

        $plugin = $cms->findPlugin($args['plugin']);

        $plugin !== null
            or $this->output->fail('Could not find plugin "%s"', $args['plugin']);

        if (isset($args['object'])) {
            $this->output->write($formatter->dump($plugin, 2));
        } elseif (isset($args['options'])) {
            $this->output->write($formatter->dump($plugin->getOptions(), 4));
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
