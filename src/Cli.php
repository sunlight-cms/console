<?php declare(strict_types=1);

namespace SunlightConsole;

use SunlightConsole\Argument\ArgumentParser;
use SunlightConsole\DependencyInjection\Container;

class Cli
{
    /** @var Container */
    private $container;
    /** @var CommandLoader */
    private $commandLoader;
    /** @var Output */
    private $output;
    /** @var array<string, string>|null */
    private $commands;

    function __construct(Container $container, CommandLoader $commandLoader, Output $output)
    {
        $this->container = $container;
        $this->commandLoader = $commandLoader;
        $this->output = $output;
    }

    function run(array $args): int
    {
        try {
            // get command
            $command = $this->matchCommand($args[0] ?? 'help');

            if ($command === null) {
                $this->output->fail('Unknown command');
            }

            // handle --help
            for ($i = 1; isset($args[$i]); ++$i) {
                if ($args[$i] === '--help') {
                    $command = $this->container->get(Command\HelpCommand::class);
                    $args = ['help', $args[0]];
                }
            }

            // parse arguments
            $commandArgs = (new ArgumentParser())->parse($command->getArguments(), array_slice($args, 1));

            // run command
            return $this->container->call([$command, 'run'], ['args' => $commandArgs]);
        } catch (\Throwable $e) {
            do {
                $this->output->log("ERROR: %s\n(%s:%d)", $e->getMessage(), $e->getFile(), $e->getLine());
            } while ($e = $e->getPrevious());

            return 1;
        }
    }

    /**
     * @return array<string, string> name => service ID
     */
    function getCommands(): array
    {
        return $this->commands ?? ($this->commands = $this->commandLoader->load());
    }

    /**
     * @return string[]
     */
    function getCommandNames(): array
    {
        return array_keys($this->getCommands());
    }

    function getCommand(string $name): ?Command
    {
        $id = $this->getCommands()[$name] ?? null;

        if ($id === null) {
            return null;
        }

        return $this->container->get($id);
    }

    function matchCommand(string $name): ?Command
    {
        $command = $this->getCommand($name);

        if ($command !== null) {
            return $command;
        }

        if (strpbrk($name, '*?[]') !== false) {
            return null;
        }

        $pattern = str_replace('.', '*.', $name) . '*';

        $matchingCommands = array_keys(
            array_filter(
                $this->getCommands(),
                function (string $commandName) use ($pattern) {
                    return fnmatch($pattern, $commandName, FNM_NOESCAPE | FNM_CASEFOLD);
                },
                ARRAY_FILTER_USE_KEY
            )
        );

        return count($matchingCommands) === 1 ? $this->getCommand(current($matchingCommands)) : null;
    }
}
