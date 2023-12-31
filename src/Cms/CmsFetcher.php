<?php declare(strict_types=1);

namespace SunlightConsole\Cms;

use SunlightConsole\Cms\Archive\Extractor;
use SunlightConsole\Cms\Archive\Locator;
use SunlightConsole\Config\Project\CmsConfig;
use SunlightConsole\JsonObject;
use SunlightConsole\Output;
use SunlightConsole\Project;
use SunlightConsole\Util\FileDownloader;

class CmsFetcher
{
    /** @var Project */
    private $project;
    /** @var Output */
    private $output;
    /** @var Locator */
    private $locator;
    /** @var FileDownloader */
    private $fileDownloader;

    function __construct(
        Project $project,
        Output $output,
        Locator $locator,
        FileDownloader $fileDownloader
    ) {
        $this->project = $project;
        $this->output = $output;
        $this->locator = $locator;
        $this->fileDownloader = $fileDownloader;
    }

    function fetch(bool $overwrite = false, bool $forceInstaller = false): void
    {
        $projectConfig = $this->project->getConfig();
        $extractor = $this->createExtractor($projectConfig->cms, $forceInstaller);

        // abort if files exist?
        if (!$overwrite && $extractor->filesAlreadyExist()) {
            $this->output->log('CMS files already exist');

            return;
        }

        // locate
        $this->output->log('Locating archive');
        $archiveParams = $this->locator->locate($projectConfig->cms);

        // download
        $tempPath = tempnam(sys_get_temp_dir(), 'slcms')
            or $this->output->fail('Could not create a temporary file');

        try {
            $this->fileDownloader->download($archiveParams->url, $tempPath);

            // extract
            $this->output->log('Extracting archive');

            $result = $extractor->extract($tempPath, $archiveParams->pathsPrefix);
        } finally {
            @unlink($tempPath);
        }

        if ($result->numWrittenFiles !== 0) {
            $this->output->log('Written %d files', $result->numWrittenFiles);
        } else {
            $this->output->log('No files written - maybe zip-paths-prefix is wrong?');
        }

        $this->output->log('CMS version is now %s', $result->version ?? 'unknown');

        // update composer.json
        $this->output->log('Updating composer.json');

        $composerJsonUpdater = new ComposerJsonUpdater($this->project->getComposerJson(), $this->output);

        if ($projectConfig->is_fresh_project) {
            // update fresh project
            $composerJsonUpdater->updateFreshProject();

            if ($archiveParams->isSemverMatched) {
                $composerJsonUpdater->updateCmsVersion($archiveParams->version);
            }
        } elseif ($archiveParams->isLatestVersion) {
            // update cms.version if "latest" was used
            $composerJsonUpdater->updateCmsVersion($archiveParams->version);
        }

        if ($result->composerJson !== null) {
            // update dependencies
            $archiveComposerJson = JsonObject::fromJson($result->composerJson);
            $composerJsonUpdater->updateDependencies($archiveComposerJson['require'] ?? []);
        } else {
            $this->output->log('Warning: No composer.json in the archive - not updating dependencies');
        }

        $composerJsonUpdater->save();
    }

    private function createExtractor(CmsConfig $cmsConfig, bool $forceInstaller): Extractor
    {
        $extractor = new Extractor($this->project->getRoot());

        if ($forceInstaller || $cmsConfig->installer && !$extractor->filesAlreadyExist()) {
            $extractor->addInstaller();
        }

        foreach ($cmsConfig->plugins->extend as $name) {
            $extractor->addPlugin('extend', $name, true);
        }

        foreach ($cmsConfig->plugins->templates as $name) {
            $extractor->addPlugin('templates', $name, false);
        }

        foreach ($cmsConfig->plugins->languages as $name) {
            $extractor->addPlugin('languages', $name, true);
        }

        return $extractor;
    }
}
