<?php declare(strict_types=1);

namespace SunlightConsole\Command\Plugin;

use SunlightConsole\Command;
use SunlightConsole\Argument\ArgumentDefinition;
use Sunlight\Core;

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

    function run(array $args): int
    {
        $this->utils->ensureCmsClassesAvailable();
        $this->utils->initCms($this->cli->getProjectRoot(), ['env' => Core::ENV_ADMIN]);
        
        // get plugin
        $plugin = $this->utils->findPlugin($args['plugin']);

        $plugin !== null
            or $this->cli->fail('Could not find plugin "%s"', $args['plugin']);

        // list actions?
        if (!isset($args['action'])) {
            $this->output->log('Listing actions for plugin "%s":', $args['plugin']);
            $this->output->log('');

            foreach ($plugin->getActions() as $name => $_) {
                $this->output->write($name);
            }

            return 0;
        }

        // get action
        $action = $plugin->getAction($args['action']);

        $action !== null
            or $this->cli->fail('Plugin action "%s" does not exist or is currently unavailable', $args['action']);

        // run
        $this->output->log('Running plugin action %s', get_class($action));
        $_POST['_plugin_action_confirmation'] = md5(get_class($action)); // fake action confirmation
        $result = $action->run();

        if ($result->hasMessages()) {
            foreach ($result->getMessages() as $message) {
                $this->output->write($this->utils->renderMessage($message));
            }
        } elseif ($result->hasOutput()) {
            $this->output->log('Plugin action has not returned any messages. Showing plaintext output below:');
            $this->output->log('');
            $this->output->write(trim($this->utils->htmlToPlaintext($result->getOutput())));
        } else {
            $this->output->log('Plugin action has not returned any messages or output');
        }

        if ($result->isComplete()) {
            Core::$pluginManager->clearCache();
        }

        return $result->getResult() === false ? 1 : 0;
    }
}
