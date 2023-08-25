<?php declare(strict_types=1);

namespace SunlightConsole\Config;

use Kuria\Options\Option;
use SunlightConsole\Command;

class ProjectConfig extends ConfigObject
{
    const COMPOSER_EXTRA_KEY = 'sunlight-console';

    /** @var Project\CmsConfig */
    public $cms;
    /** @var array<string, class-string<Command>> */
    public $commands;
    /** @var bool */
    public $is_fresh_project;

    /**
     * @return static
     */
    static function loadFromComposerPackage(array $package): self
    {
        return self::load($package['extra'][self::COMPOSER_EXTRA_KEY] ?? []);
    }

    protected static function getDefinition(): array
    {
        return [
            Option::bool('is-fresh-project')->default(false),
            self::nested('cms', Project\CmsConfig::class),
            Option::list('commands', 'string')->default([])
        ];
    }
}
