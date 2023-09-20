<?php declare(strict_types=1);

namespace SunlightConsole\Cms;

use SunlightConsole\Config\ProjectConfig;
use SunlightConsole\JsonObject;
use SunlightConsole\Output;

class ComposerJsonUpdater
{
    /** @var JsonObject */
    private $package;
    /** @var Output */
    private $output;

    function __construct(JsonObject $package, Output $output)
    {
        $this->package = $package;
        $this->output = $output;
    }

    function updateProjectConfig(array $updates): void
    {
        $this->package->exchangeArray(
            array_replace_recursive(
                $this->package->getArrayCopy(),
                ['extra' => [ProjectConfig::COMPOSER_EXTRA_KEY => $updates]]
            )
        );
    }

    function updateFreshProject(?string $cmsVersion): void
    {
        unset(
            $this->package['name'],
            $this->package['description'],
            $this->package['license'],
            $this->package['extra'][ProjectConfig::COMPOSER_EXTRA_KEY]['is-fresh-project']
        );

        if ($cmsVersion !== null) {
            $this->output->log('Setting cms.version to %s', $cmsVersion);
            $this->package['extra'][ProjectConfig::COMPOSER_EXTRA_KEY]['cms']['version'] = $cmsVersion;
        }
    }

    function updateDependencies(array $newDependencies): void
    {
        $changed = false;

        foreach ($newDependencies as $package => $version) {
            if (!isset($this->package['require'][$package]) || $this->package['require'][$package] !== $version) {
                $this->output->log(
                    isset($this->package['require'][$package])
                        ? 'Updating %s dependency in composer.json'
                        : 'Adding %s dependency to composer.json',
                    $package
                );
                $this->package['require'][$package] = $version;
                $changed = true;
            }
        }

        if ($changed) {
            $this->output->log('Warning: Dependencies have changed - you should run composer update now');
        }
    }

    function save(): void
    {
        $this->package->save();
    }
}
