<?php declare(strict_types=1);

namespace SunlightConsole\Config\Project\Cms;

use Kuria\Options\Option;
use SunlightConsole\Config\ConfigObject;

class PluginsConfig extends ConfigObject
{
    /** @var string[] */
    public $extend;
    /** @var string[] */
    public $templates;
    /** @var string[] */
    public $languages;

    protected static function getDefinition(): array
    {
        return [
            Option::list('extend', 'string')->default([]),
            Option::list('templates', 'string')->default([]),
            Option::list('languages', 'string')->default([]),
        ];
    }
}
