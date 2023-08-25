<?php declare(strict_types=1);

namespace SunlightConsole\Config\Project\Cms;

use Kuria\Options\Option;
use SunlightConsole\Config\ConfigObject;

class ArchiveConfig extends ConfigObject
{
    /** @var string|null */
    public $zip_url;
    /** @var string */
    public $zip_paths_prefix;
    /** @var string|null */
    public $git_url;
    /** @var string|null */
    public $git_branch_zip_url;
    /** @var string|null */
    public $git_tag_zip_url;
    /** @var string|null */
    public $git_tag_pattern;

    protected static function getDefinition(): array
    {
        return [
            Option::string('zip-url')
                ->default(null),
            Option::string('zip-paths-prefix')
                ->default('sunlight-cms-%version%/'),
            Option::string('git-url')
                ->default('https://github.com/sunlight-cms/sunlight-cms.git')
                ->nullable(),
            Option::string('git-branch-zip-url')
                ->default('https://github.com/sunlight-cms/sunlight-cms/archive/refs/heads/%version%.zip')
                ->nullable(),
            Option::string('git-tag-zip-url')
                ->default('https://github.com/sunlight-cms/sunlight-cms/archive/refs/tags/v%version%.zip')
                ->nullable(),
            Option::string('git-tag-pattern')
                ->default('v8*')
                ->nullable(),
        ];
    }
}
