<?php declare(strict_types=1);

namespace SunlightConsole\Argument;

class ArgumentDefinition
{
    /** @var int|null */
    public $index;
    /** @var string */
    public $name;
    /** @var string */
    public $help;
    /** @var bool|null */
    public $acceptsValue;
    /** @var bool */
    public $required;

    private function __construct(?int $index, string $name, string $help, ?bool $acceptsValue, bool $required)
    {
        $this->index = $index;
        $this->name = $name;
        $this->help = $help;
        $this->acceptsValue = $acceptsValue;
        $this->required = $required;
    }

    static function flag(string $name, string $help): self
    {
        return new self(null, $name, $help, false, false);
    }

    static function flagOrOption(string $name, string $help): self
    {
        return new self(null, $name, $help, null, false);
    }

    static function option(string $name, string $help, bool $required = false): self
    {
        return new self(null, $name, $help, true, $required);
    }

    static function argument(int $index, string $name, string $help, bool $required = false): self
    {
        return new self($index, $name, $help, true, $required);
    }

    /**
     * @return array-key
     */
    function getArrayKey()
    {
        return $this->index ?? $this->name;
    }

    function isOption(): bool
    {
        return $this->index === null;
    }

    function isArgument(): bool
    {
        return $this->index !== null;
    }

    function format(): string
    {
        if ($this->isOption()) {
            return
                (!$this->required ? '[' : '')
                . '--'
                . (
                    $this->acceptsValue !== false
                        ? "{$this->name}=" . ($this->acceptsValue === true ? '<value>' : '[value]')
                        : $this->name
                )
                . (!$this->required ? ']' : '');
        }


        return $this->required ? "<{$this->name}>" : "[{$this->name}]";
    }
}
