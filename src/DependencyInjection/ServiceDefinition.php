<?php declare(strict_types=1);

namespace SunlightConsole\DependencyInjection;

class ServiceDefinition
{
    /** @var string */
    public $id;
    /** @var class-string|null */
    public $class;
    /** @var callable|string|null */
    public $factory;
    /** @var array */
    public $args = [];
    /** @var array<array{method: string, args: array}> */
    public $calls = [];
    /** @var array<array{callback: callable|string, args: array}> */
    public $initializers = [];
    /** @var array<string, mixed> */
    public $tags = [];
    /** @var string|null */
    public $parent;
    /** @var bool */
    public $abstract = false;
    /** @var bool */
    public $autowire = true;

    static function service(string $id): self
    {
        $def = new self();
        $def->id = $id;

        return $def;
    }

    static function base(string $id): self
    {
        $def = self::service($id);
        $def->abstract = true;

        return $def;
    }

    /**
     * @param class-string $class
     * @return $this
     */
    function class(?string $class): self
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return $this
     */
    function factory(?string $factory): self
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * @return $this
     */
    function args(...$args): self
    {
        $this->args = $args;

        return $this;
    }

    /**
     * @return $this
     */
    function call(string $method, ...$args): self
    {
        $this->calls[] = ['method' => $method, 'args' => $args];

        return $this;
    }

   /**
     * @return $this
     */
    function init(string $callback, ...$args): self
    {
        $this->initializers[] = ['callback' => $callback, 'args' => $args];

        return $this;
    }

    /**
     * @return $this
     */
    function tag(string $tag, $value = true): self
    {
        $this->tags[$tag] = $value;

        return $this;
    }

    /**
     * @return $this
     */
    function extend(string $id): self
    {
        $this->parent = $id;

        return $this;
    }

    /**
     * @return $this
     */
    function noAutowire(): self
    {
        $this->autowire = false;

        return $this;
    }
}
