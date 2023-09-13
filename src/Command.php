<?php declare(strict_types=1);

namespace SunlightConsole;

use SunlightConsole\Argument\ArgumentDefinition;

/**
 * @method int run()
 */
abstract class Command
{
    /** @var Output */
    protected $output;
    /** @var string */
    private $name;
    /** @var array<array-key, ArgumentDefinition>|null */
    private $argMap;

    function __construct(Output $output)
    {
        $this->output = $output;
    }

    final function setName(string $name): void
    {
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
}
