<?php declare(strict_types=1);

namespace SunlightConsole\Config;

use Kuria\Options\Option;
use SunlightConsole\DependencyInjection\ServiceDefinition;

class ServiceConfig extends ConfigObject
{
    /** @var class-string|null */
    public $class;
    /** @var callable|string|null */
    public $factory;
    /** @var array */
    public $args;
    /** @var array<array{method: string, args: array}> */
    public $calls;
    /** @var array<array{callback: callable|string, args: array}> */
    public $initializers;
    /** @var bool */
    public $autowire;

    function toDefinition(string $id): ServiceDefinition
    {
        $def = ServiceDefinition::service($id);
        $def->class = $this->class;
        $def->factory = $this->factory;
        $def->args = $this->args;
        $def->calls = $this->calls;
        $def->initializers = $this->initializers;
        $def->autowire = $this->autowire;

        return $def;
    }

    protected static function getDefinition(): array
    {
        return [
            Option::string('class')->notEmpty()->default(null),
            Option::any('factory')->default(null),
            Option::array('args')->default([]),
            Option::nodeList(
                'calls',
                Option::string('method'),
                Option::array('args')->default([]),
            ),
            Option::nodeList(
                'initializers',
                Option::any('callback'),
                Option::array('args')->default([]),
            ),
            Option::bool('autowire')->default(true),
        ];
    }
}
