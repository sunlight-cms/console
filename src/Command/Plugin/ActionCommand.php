<?php declare(strict_types=1);

namespace SunlightConsole\Command\Plugin;

use SunlightConsole\Command;
use SunlightConsole\Argument\ArgumentDefinition;
use Sunlight\Core;
use SunlightConsole\Util\CmsFacade;
use SunlightConsole\Util\Formatter;

class ActionCommand extends Command
{
    function getHelp(): string
    {
        return 'perform a plugin action (or list actions if no action is given)';
    }

    protected function defineArguments(): array
    {
        return [
            ArgumentDefinition::argument(0, 'plugin', 'plugin name or ID', true),
            ArgumentDefinition::argument(1, 'action', 'action name (e.g. "install", "enable", "disable", ...)'),
        ];
    }

    function run(CmsFacade $cms, Formatter $formatter, array $args): int
    {
        $cms->init(['env' => Core::ENV_ADMIN]);
        
        // get plugin
        $plugin = $cms->findPlugin($args['plugin']);

        $plugin !== null
            or $this->output->fail('Could not find plugin "%s"', $args['plugin']);

        // list actions?
        if (!isset($args['action'])) {
            $this->output->write('Listing actions for plugin "%s":', $args['plugin']);
            $this->output->write('');

            foreach ($plugin->getActions() as $name => $_) {
                $this->output->write($name);
            }

            return 0;
        }

        // get action
        $action = $plugin->getAction($args['action']);

        $action !== null
            or $this->output->fail('Plugin action "%s" does not exist or is currently unavailable', $args['action']);

        // run
        $this->output->write('Running plugin action %s', get_class($action));
        $_POST['_plugin_action_confirmation'] = md5(get_class($action)); // fake action confirmation
        $result = $action->run();

        if ($result->hasMessages()) {
            foreach ($result->getMessages() as $message) {
                $this->output->write($formatter->message($message));
            }
        } elseif ($result->hasOutput()) {
            $this->output->write('Plugin action has not returned any messages. Showing plaintext output below:');
            $this->output->write('');
            $this->output->write(trim($formatter->htmlAsPlaintext((string) $result->getOutput())));
        } else {
            $this->output->write('Plugin action has not returned any messages or output');
        }

        if ($result->isComplete()) {
            Core::$pluginManager->clearCache();
        }

        return $result->getResult() === false ? 1 : 0;
    }
}
