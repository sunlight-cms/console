<?php declare(strict_types=1);

namespace SunlightConsole\Cms\Archive;

class ExtractionResult
{
    /** @var string|null */
    public $version;
    /** @var string|null */
    public $composerJson;
    /** @var int */
    public $numWrittenFiles = 0;
}
