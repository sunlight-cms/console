<?php declare(strict_types=1);

namespace SunlightConsole\Cms;

use SunlightConsole\Cli;
use SunlightConsole\Config\ProjectConfig;
use SunlightConsole\JsonObject;
use SunlightConsole\Output;

class ComposerJsonUpdater
{
    /** @var Cli */
    private $cli;
    /** @var Output */
    private $output;

    function __construct(Cli $cli, Output $output)
    {
        $this->cli = $cli;
        $this->output = $output;
    }

    function updateProjectConfig(array $updates): void
    {
        $composerJson = JsonObject::fromFile($this->cli->getProjectRoot() . '/composer.json');
        $composerJson->exchangeArray(
            array_replace_recursive(
                $composerJson->getArrayCopy(),
                ['extra' => [ProjectConfig::COMPOSER_EXTRA_KEY => $updates]]
            )
        );

        $composerJson->save();
    }

    function updateAfterExtraction(
        CmsExtractionResult $extractionResult,
        bool $archiveIsSemverMatched,
        bool $isFreshProject
    ): void {
        // load project composer.json
        $composerJson = JsonObject::fromFile($this->cli->getProjectRoot() . '/composer.json');

        // load archive composer.json
        if ($extractionResult->composerJson !== null) {
            $archiveComposerJson = JsonObject::fromJson($extractionResult->composerJson);
        } else {
            $archiveComposerJson = null;
        }

        // fresh project updates
        if ($isFreshProject) {
            unset(
                $composerJson['name'],
                $composerJson['description'],
                $composerJson['license'],
                $composerJson['extra'][ProjectConfig::COMPOSER_EXTRA_KEY]['is-fresh-project']
            );

            if ($archiveIsSemverMatched && $extractionResult->version !== null) {
                $this->output->log('Setting cms.version to %s', $extractionResult->version);
                $composerJson['extra'][ProjectConfig::COMPOSER_EXTRA_KEY]['cms']['version'] = $extractionResult->version;
            }
        }

        // update dependencies
        if ($archiveComposerJson !== null) {
            foreach ($archiveComposerJson['require'] ?? [] as $package => $version) {
                if (!isset($composerJson['require'][$package]) || $composerJson['require'][$package] !== $version) {
                    $this->output->log(
                        isset($composerJson['require'][$package])
                            ? 'Updating %s dependency in composer.json'
                            : 'Adding %s dependency to composer.json',
                        $package
                    );
                    $composerJson['require'][$package] = $version;
                }
            }
        } else {
            $this->output->log('Warning: There is no valid composer.json in the archive - not updating dependencies!');
        }

        // save changes
        $composerJson->save();
    }
}
