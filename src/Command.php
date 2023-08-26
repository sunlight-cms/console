<?php declare(strict_types=1);

namespace SunlightConsole;

use SunlightConsole\Argument\ArgumentDefinition;

abstract class Command
{
    /** @var Cli */
    protected $cli;
    /** @var Utils */
    protected $utils;
    /** @var Output */
    protected $output;
    /** @var string */
    private $name;
    /** @var array<array-key, ArgumentDefinition>|null */
    private $argMap;

    function __construct(Cli $cli, Utils $utils, Output $output, string $name)
    {
        $this->cli = $cli;
        $this->utils = $utils;
        $this->output = $output;
        $this->name = $name;
    }

    final function getName(): string
    {
        return $this->name;
    }

    abstract function getHelp(): string;

    /**
     * @return array<array-key, ArgumentDefinition>
     */
    final function getArguments(): array
    {
        if ($this->argMap === null) {
            $this->argMap = [];

            foreach ($this->defineArguments() as $arg) {
                $this->argMap[$arg->getArrayKey()] = $arg;
            }
        }

        return $this->argMap;
    }

    /**
     * @return ArgumentDefinition[]
     */
    protected function defineArguments(): array
    {
        return [];
    }

    /**
     * @param array<string, string> $args
     */
    abstract function run(array $args): int;
}
