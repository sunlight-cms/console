<?php declare(strict_types=1);

namespace SunlightConsole\Config\Project;

use Kuria\Options\Option;
use SunlightConsole\Config\ConfigObject;

class CmsConfig extends ConfigObject
{
    /** @var string */
    public $version;
    /** @var Cms\ArchiveConfig */
    public $archive;
    /** @var Cms\PluginsConfig */
    public $plugins;
    /** @var bool */
    public $installer;

    protected static function getDefinition(): array
    {
        return [
            Option::string('version'),
            self::nested('archive', Cms\ArchiveConfig::class),
            self::nested('plugins', Cms\PluginsConfig::class),
            Option::bool('installer')->default(true),
        ];
    }
}
