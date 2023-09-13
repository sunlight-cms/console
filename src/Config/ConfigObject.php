<?php declare(strict_types=1);

namespace SunlightConsole\Config;

use Kuria\Options\Exception\ResolverException;
use Kuria\Options\Node;
use Kuria\Options\Option;
use Kuria\Options\Option\NodeOption;
use Kuria\Options\Option\OptionDefinition;
use Kuria\Options\Resolver;

abstract class ConfigObject
{
    /** @var array<class-string<self>, Resolver> */
    private static $resolverCache = [];

    protected function __construct()
    {
    }

    /**
     * @throws ResolverException
     * @return static
     */
    static function load(array $data): self
    {
        $object = new static();
        $object->hydrate(self::getResolver()->resolve($data)->toArray());

        return $object;
    }

    /**
     * @return OptionDefinition[]
     */
    abstract protected static function getDefinition(): array;

    function __clone()
    {
        foreach (get_object_vars($this) as $prop => $value) {
            if ($value instanceof self) {
                $this->{$prop} = clone $value;
            }
        }
    }

    function toArray(): array
    {
        $array = [];

        foreach (get_object_vars($this) as $prop => $value) {
            $array[str_replace('_', '-', $prop)] = $value instanceof self ? $value->toArray() : $value;
        }

        return $array;
    }

    /**
     * @param array<string, mixed> $array
     */
    protected function hydrate(array $array): void
    {
        foreach ($array as $key => $value) {
            $prop = str_replace('-', '_', $key);

            if (!property_exists($this, $prop)) {
                throw new \Exception(sprintf(
                    'Property $%s does not exist on %s (from array key "%s")',
                    $prop,
                    static::class,
                    $key
                ));
            }

            $this->{$prop} = $value;
        }
    }

    /**
     * @param class-string<self> $type
     */
    protected static function nested(string $name, string $type): NodeOption
    {
        $node = Option::node($name, ...$type::getDefinition());
        $node->normalize(function (Node $node) use ($type) {
            return $type::fromNode($node);
        });

        return $node;
    }

    /**
     * @param class-string<self> $type
     */
    protected static function nestedList(string $name, string $type): NodeOption
    {
        $node = Option::nodeList($name, ...$type::getDefinition());
        $node->normalize(function (array $nodes) use ($type) {
            return array_map([$type, 'fromNode'], $nodes);
        });

        return $node;
    }

    /**
     * @return static
     */
    protected static function fromNode(Node $node): self
    {
        $object = new static();
        $object->hydrate($node->toArray());

        return $object;
    }

    private static function getResolver(): Resolver
    {
        if (!isset(self::$resolverCache[static::class])) {
            $resolver = new Resolver();
            $resolver->addOption(...static::getDefinition());

            self::$resolverCache[static::class] = $resolver;
        }

        return self::$resolverCache[static::class];
    }
}
