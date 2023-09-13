<?php declare(strict_types=1);

namespace SunlightConsole\Util;

class StringHelper
{
    /**
     * @param string[] $strings
     */
    function getMaxStringLength(array $strings): int
    {
        if (empty($strings)) {
            return 0;
        }

        return max(array_map('strlen', $strings));
    }
}
