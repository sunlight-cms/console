<?php declare(strict_types=1);

namespace SunlightConsole\Command;

use SunlightConsole\Argument\ArgumentDefinition;
use SunlightConsole\Cli;
use SunlightConsole\Command;
use SunlightConsole\Util\StringHelper;

class HelpCommand extends Command
{
    function getHelp(): string
    {
        return 'show help';
    }

    protected function defineArguments(): array
    {
        return [
            ArgumentDefinition::flag('short', 'list only command names and descriptions'),
            ArgumentDefinition::argument(0, 'command', 'get help for a specific command'),
        ];
    }

    function run(Cli $cli, StringHelper $stringHelper, array $args): int
    {
        // help for single command only?
        if (isset($args['command'])) {
            return $this->printCommandHelp($cli, $args['command']);
        }

        // usage
        $this->output->write('Usage: %s <command> [options] [args]', $_SERVER['PHP_SELF'] ?? 'console');
        $this->output->write('');
        $this->output->write('You can pass --help to any command to show help for it.');
        $this->output->write('');

        // list commands
        $this->output->write('Available commands:');
        $this->output->write('');

        $commandNames = $cli->getCommandNames();
        $commandNamePadding = $stringHelper->getMaxStringLength($commandNames);
        $commandNamePaddingStr = str_repeat(' ', $commandNamePadding);

        foreach ($commandNames as $index => $commandName) {
            if ($index > 0 && !isset($args['short'])) {
                $this->output->write('');
            }

            // command name and help
            /** @var Command */
            $command = $cli->getCommand($commandName);
            $this->output->write("%-{$commandNamePadding}s    %s", $commandName, $command->getHelp());

            // arguments
            if (!isset($args['short'])) {
                $this->printCommandArgs($command->getArguments(), $commandNamePaddingStr);
            }
        }

        return 0;
    }

    private function printCommandHelp(Cli $cli, string $commandName): int
    {
        $command = $cli->matchCommand($commandName);

        if ($command === null) {
            $this->output->write('Unknown command');

            return 1;
        }

        $formattedArgs = array_map(
            function (ArgumentDefinition $arg) { return $arg->format(); },
            $command->getArguments()
        );

        $this->output->write(ucfirst($command->getHelp()) . '.');
        $this->output->write('');
        $this->output->write(
            'Usage: %s %s%s',
            $_SERVER['PHP_SELF'] ?? 'console',
            $command->getName(),
            !empty($formattedArgs) ? ' ' . implode(' ', $formattedArgs) : ''
        );
        $this->output->write('');
        $this->printCommandArgs($command->getArguments(), '');

        return 0;
    }

    /**
     * @param array<array-key, ArgumentDefinition> $args
     */
    private function printCommandArgs(array $args, string $padding): void
    {
        if (empty($args)) {
            return;
        }

        $argNamePadding = 0;

        foreach ($args as $arg) {
            $argNamePadding = max($argNamePadding, strlen($arg->format()));
        }

        foreach ($args as $arg) {
            $formattedArgName = $arg->format();

            $this->output->write(
                '%s    %s%s    %s',
                $padding,
                $formattedArgName,
                str_repeat(' ', $argNamePadding - strlen($formattedArgName)),
                $arg->help
            );
        }
    }
}
