<?php declare(strict_types=1);

namespace SunlightConsole\Cms;

use SunlightConsole\Config\ProjectConfig;
use SunlightConsole\Output;
use SunlightConsole\Utils;

class ComposerJsonUpdater
{
    /** @var Utils */
    private $utils;
    /** @var Output */
    private $output;

    function __construct(Utils $utils, Output $output)
    {
        $this->utils = $utils;
        $this->output = $output;
    }

    function update(
        string $composerJsonPath,
        CmsExtractionResult $extractionResult,
        bool $archiveIsSemverMatched,
        bool $isFreshProject
    ): void {
        $update = false;

        // load project's composer.json
        $projectPackage = $this->utils->loadJsonFromFile($composerJsonPath);

        // decode archive composer.json
        if ($extractionResult->composerJson !== null) {
            $archivePackage = json_decode($extractionResult->composerJson, true);

            if (!is_array($archivePackage)) {
                $this->output->log('Failed to decode composer.json from CMS archive');
                $archivePackage = null;
            }
        } else {
            $archivePackage = null;
        }

        // fresh project updates
        if ($isFreshProject) {
            unset(
                $projectPackage['name'],
                $projectPackage['description'],
                $projectPackage['license'],
                $projectPackage['extra'][ProjectConfig::COMPOSER_EXTRA_KEY]['is-fresh-project']
            );

            if ($archiveIsSemverMatched && $extractionResult->version !== null) {
                $newVersion = '~' . $extractionResult->version;
                $this->output->log('Setting cms.version to %s', $newVersion);
                $projectPackage['extra'][ProjectConfig::COMPOSER_EXTRA_KEY]['cms']['version'] = $newVersion;
            }
            
            $update = true;
        }

        // update dependencies
        if ($archivePackage !== null) {
            foreach ($archivePackage['require'] ?? [] as $package => $version) {
                if (!isset($projectPackage['require'][$package]) || $projectPackage['require'][$package] !== $version) {
                    $this->output->log(
                        isset($projectPackage['require'][$package])
                            ? 'Updating %s dependency in composer.json'
                            : 'Adding %s dependency to composer.json',
                        $package
                    );
                    $projectPackage['require'][$package] = $version;
                    $update = true;
                }
            }
        } else {
            $this->output->log('Warning: There is no valid composer.json in the archive - not updating dependencies!');
        }

        // update composer.json
        if ($update) {
            file_put_contents($composerJsonPath, $this->utils->encodeJsonPretty($projectPackage), LOCK_EX);
        }
    }
}
