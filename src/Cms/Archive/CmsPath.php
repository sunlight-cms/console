<?php declare(strict_types=1);

namespace SunlightConsole\Cms\Archive;

class CmsPath
{
    /** @var string */
    public $path;
    /** @var bool */
    public $overwrite;

    function __construct(string $path, bool $overwrite)
    {
        $this->path = $path;
        $this->overwrite = $overwrite;
    }

    function isDir(): bool
    {
        return $this->path[-1] === '/';
    }
}
