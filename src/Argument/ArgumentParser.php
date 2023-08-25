<?php declare(strict_types=1);

namespace SunlightConsole\Argument;

class ArgumentParser
{
    /**
     * @param array<array-key, ArgumentDefinition> $args
     * @param string[] $input
     * @return array<string, string>
     */
    function parse(array $args, array $input): array
    {
        $parsedArgs = [];
        $argIndex = 0;

        foreach ($input as $arg) {
            if (strncmp($arg, '--', 2) === 0) {
                // option
                $equalSignPos = strpos($arg, '=', 2);

                if ($equalSignPos !== false) {
                    $key = substr($arg, 2, $equalSignPos - 2);
                    $value = substr($arg, $equalSignPos + 1);
                } else {
                    $key = substr($arg, 2);
                    $value = '';
                }

                if (!isset($args[$key]) || !$args[$key]->isOption()) {
                    throw new \Exception(sprintf('Unknown argument --%s', $key));
                }

                if ($args[$key]->acceptsValue === true && $equalSignPos === false) {
                    throw new \Exception(sprintf('Argument --%s requires a value', $key));
                } elseif ($args[$key]->acceptsValue === false && $equalSignPos !== false) {
                    throw new \Exception(sprintf('Argument --%s does not accept a value', $key));
                }
            } else {
                // argument
                $key = $argIndex++;
                $value = $arg;

                if (!isset($args[$key]) || !$args[$key]->isArgument()) {
                    throw new \Exception(sprintf('Argument "%s" is not expected', $value));
                }
            }

            $parsedArgs[$args[$key]->name] = $value;
        }

        foreach ($args as $arg) {
            if ($arg->required && !isset($parsedArgs[$arg->name])) {
                throw new \Exception(sprintf('Missing required command %s %s', $arg->isOption() ? 'option' : 'argument', $arg->format()));
            }
        }

        return $parsedArgs;
    }
}
