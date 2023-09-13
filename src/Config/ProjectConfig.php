<?php declare(strict_types=1);

namespace SunlightConsole\Config;

use Kuria\Options\Option;
use SunlightConsole\JsonObject;

class ProjectConfig extends ConfigObject
{
    const COMPOSER_EXTRA_KEY = 'sunlight-console';

    /** @var Project\CmsConfig */
    public $cms;
    /** @var array<string, ServiceConfig> */
    public $commands;
    /** @var bool */
    public $is_fresh_project;

    /**
     * @return static
     */
    static function loadFromComposerJson(JsonObject $composerJson): self
    {
        return self::load($composerJson['extra'][self::COMPOSER_EXTRA_KEY] ?? []);
    }

    protected static function getDefinition(): array
    {
        return [
            Option::bool('is-fresh-project')->default(false),
            self::nested('cms', Project\CmsConfig::class),
            self::nestedList('commands', ServiceConfig::class),
        ];
    }
}
