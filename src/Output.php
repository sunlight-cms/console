<?php declare(strict_types=1);

namespace SunlightConsole;

class Output
{
    /**
     * Write a line to standard output
     */
    function write(string $message, ...$params): void
    {
        if (empty($params)) {
            echo $message;
        } else {
            printf($message, ...$params);
        }

        echo "\n";
    }

    /**
     * Log a line into error output
     */
    function log(string $message, ...$params): void
    {
        fwrite(STDERR, empty($params) ? $message : sprintf($message, ...$params));
        fwrite(STDERR, "\n");
    }
}
