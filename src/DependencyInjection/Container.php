<?php declare(strict_types=1);

namespace SunlightConsole\DependencyInjection;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    /** @var array<string, object> service ID => instance */
    private $services = [];
    /** @var array<string, ServiceDefinition> */
    private $definitions = [];
    /** @var array<string, array<string, mixed>> tag => service ID => value */
    private $tagged = [];
    /** @var array<string, true> */
    private $instMap = [];

    function __construct()
    {
        $this->services[__CLASS__] = $this;
    }

    /**
     * Check if a service is defined
     */
    function has(string $id): bool
    {
        return isset($this->services[$id]) || isset($this->definitions[$id]);
    }

    /**
     * Get service object
     *
     * @template T
     * @param class-string<T>|string $id
     * @throws ContainerException on failure
     * @throws ServiceNotFoundException if the service is not defined
     * @return T|object
     */
    function get(string $id)
    {
        return $this->services[$id] ?? ($this->services[$id] = $this->instantiate($id));
    }

    /**
     * Get tagged service IDs
     *
     * @return array<string, mixed> service ID => value
     */
    function getTagged(string $tag): array
    {
        return $this->tagged[$tag] ?? [];
    }

    /**
     * Define a service object directly
     *
     * @param object $service
     */
    function set(string $id, $service): void
    {
        $this->services[$id] = $service;
    }

    /**
     * Register service definitions
     */
    function define(ServiceDefinition ...$definitions): void
    {
        foreach ($definitions as $def) {
            $this->definitions[$def->id] = $def;

            if (!empty($def->tags)) {
                foreach ($def->tags as $tag => $value) {
                    $this->tagged[$tag][$def->id] = $value;
                }
            }
        }
    }

    /**
     * Call the given callback with autowired arguments
     *
     * @param callable $callback
     * @throws ContainerException on failure
     */
    function call($callback, array $args, array $context = [])
    {
        return $callback(...$this->autowireArguments($callback, $args, $context));
    }

    /**
     * Resolve the given arguments using autowiring
     *
     * @param callable $callback
     * @throws ContainerException on failure
     * @return list<mixed>
     */
    function autowireArguments($callback, array $args, array $context = [], int $offset = 0): array
    {
        return $this->resolveArgs(
            new \ReflectionFunction(\Closure::fromCallable($callback)),
            $args,
            $context,
            true,
            false,
            $offset
        );
    }

    /**
     * @return object
     */
    private function instantiate(string $id)
    {
        $def = $this->resolveDefinition($id);

        if ($def->abstract) {
            throw new ContainerException(sprintf('Cannot instantiate abstract service "%s"', $id));
        }

        $context = [
            Container::class => $this,
            ServiceDefinition::class => $def,
        ];

        if (isset($this->instMap[$id])) {
            throw new ContainerException(sprintf('Circular dependency detected: %s->%s', implode('->', array_keys($this->instMap)), $id));
        }

        $this->instMap[$id] = true;

        try {
            try {
                if (isset($def->factory)) {
                    $callback = $this->resolveCallback($def->factory);
                    $args = $this->resolveArgs(new \ReflectionFunction($callback), $def->args, $context, $def->autowire);

                    $service = $callback(...$args);
                } else {
                    /** @var class-string */
                    $class = $def->class ?? $id;

                    if (method_exists($class, '__construct')) {
                        $args = $this->resolveArgs(new \ReflectionMethod($class, '__construct'), $def->args, [], $def->autowire);
                    } else {
                        if (!empty($def->args)) {
                            throw new ContainerException('Cannot pass arguments to a class without a constructor');
                        }

                        $args = [];
                    }

                    $service = new $class(...$args);
                }
            } catch (\Throwable $e) {
                throw new ContainerException(sprintf('Error while instantiating service "%s": %s', $id, $e->getMessage()), 0, $e);
            }

            foreach ($def->calls as $call) {
                try {
                    $args = $this->resolveArgs(new \ReflectionMethod($service, $call['method']), $call['args'], $context, $def->autowire);

                    $service->{$call['method']}(...$args);
                } catch (\Throwable $e) {
                    throw new ContainerException(sprintf('Error while calling %s(#%d) on service "%s"', $call['method'], count($call['args']), $id));
                }
            }

            foreach ($def->initializers as $index => $init) {
                try {
                    $callback = $this->resolveCallback($init['callback']);
                    $args = $this->resolveArgs(new \ReflectionFunction($callback), $init['args'], $context, $def->autowire, true, 1);

                    $callback($service, ...$args);
                } catch (\Throwable $e) {
                    throw new ContainerException(sprintf(
                        'Error while calling initializer %s on service "%s"',
                        is_string($init['callback']) ? sprintf('"%s"', $init['callback']) : sprintf('#%d', $index),
                        $id
                    ));
                }
            }

            return $service;
        } finally {
            unset($this->instMap[$id]);
        }
    }

    private function getDefinition(string $id): ServiceDefinition
    {
        $def = $this->definitions[$id] ?? null;

        if ($def === null) {
            throw new ServiceNotFoundException(sprintf('Service "%s" is not defined', $id));
        }

        return $def;
    }

    private function resolveDefinition(string $id): ServiceDefinition
    {
        $def = $this->getDefinition($id);

        if ($def->parent !== null) {
            $def = clone $def;
            $parentId = $def->parent;

            while ($parentId !== null) {
                $parent = $this->getDefinition($parentId);

                $def->class = $def->class ?? $parent->class;
                $def->factory = $def->factory ?? $parent->factory;
                $def->tags += $parent->tags;

                if (!empty($parent->args)) {
                    $def->args = array_merge($parent->args, $def->args);
                }

                if (!empty($parent->calls)) {
                    array_unshift($def->calls, ...$parent->calls);
                }

                if (!empty($parent->initializers)) {
                    array_unshift($def->initializers, ...$parent->initializers);
                }

                $def->autowire = $parent->autowire;

                $parentId = $parent->parent;
            }
        }

        return $def;
    }

    /**
     * @return list<mixed>
     */
    private function resolveArgs(
        \ReflectionFunctionAbstract $reflection,
        array $args,
        array $context,
        bool $autowire,
        bool $resolveValues = true,
        int $offset = 0
    ): array {
        $resolved = [];
        $unmatched = [];

        foreach ($args as $argKey => $_) {
            $unmatched[$argKey] = true;
        }

        $params = $reflection->getParameters();

        for ($i = $offset; isset($params[$i]); ++$i) {
            // by name or index
            /** @psalm-suppress RedundantCondition */
            array_key_exists($argKey = $params[$i]->name, $args)
                || array_key_exists($argKey = $i - $offset, $args)
                || ($argKey = null);

            if ($argKey !== null) {
                $resolved[] = $resolveValues ? $this->resolveArgValue($args[$argKey]) : $args[$argKey];
                unset($unmatched[$argKey]);
                continue;
            }

            // by class
            if (
                ($paramType = $params[$i]->getType()) instanceof \ReflectionNamedType
                && !$paramType->isBuiltin()
            ) {
                $class = $paramType->getName();

                // from context
                if (isset($context[$class])) {
                    $resolved[] = $context[$class];
                    continue;
                }

                // autowiring
                if ($autowire) {
                    if ($this->has($class)) {
                        $resolved[] = $this->get($class);
                        continue;
                    } elseif (!$params[$i]->isOptional()) {
                        throw new ContainerException(sprintf('Cannot autowire argument #%d ($%s)', $i + 1, $params[$i]->name));
                    }
                }
            }

            // missing argument
            if ($params[$i]->isOptional()) {
                break;
            }

            throw new ContainerException(sprintf('Missing argument #%d ($%s)', $i + 1, $params[$i]->name));
        }

        if (!empty($unmatched)) {
            throw new ContainerException(sprintf('Unknown or duplicate arguments: %s', implode(', ', array_keys($unmatched))));
        }

        return $resolved;
    }
    private function resolveArgValue($value)
    {
        if (is_string($value) && ($value[0] ?? null) === '@') {
            $value = $this->resolveServiceRef($value);
        }

        return $value;
    }

    private function resolveServiceRef(string $arg)
    {
        $doubleColonPos = strpos($arg, '::', 1);

        if ($doubleColonPos !== false) {
            // service method call
            return $this->get(substr($arg, 1, $doubleColonPos - 1))
                ->{substr($arg, $doubleColonPos + 2)}();
        }

        // service object
        return $this->get(substr($arg, 1));
    }

    private function resolveCallback($callback): \Closure
    {
        if (is_string($callback) && ($callback[0] ?? null) === '@') {
            if (($doubleColonPos = strpos($callback, '::', 1)) !== false) {
                // service method
                return \Closure::fromCallable([
                    $this->get(substr($callback, 1, $doubleColonPos - 1)),
                    substr($callback, $doubleColonPos + 2),
                ]);
            }

            // __invoke
            return $this->get(substr($callback, 1));
        }

        // other callables
        /** @var callable $callback */
        return \Closure::fromCallable($callback);
    }
}
