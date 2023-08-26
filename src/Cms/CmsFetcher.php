<?php declare(strict_types=1);

namespace SunlightConsole\Cms;

use SunlightConsole\Cli;
use SunlightConsole\Config\Project\CmsConfig;
use SunlightConsole\Output;
use SunlightConsole\Utils;

class CmsFetcher
{
    /** @var Cli */
    private $cli;
    /** @var Utils */
    private $utils;
    /** @var Output */
    private $output;
    /** @var CmsLocator */
    private $locator;
    /** @var ComposerJsonUpdater */
    private $composerJsonUpdater;

    function __construct(
        Cli $cli,
        Utils $utils,
        Output $output,
        CmsLocator $locator,
        ComposerJsonUpdater $composerJsonUpdater
    ) {
        $this->cli = $cli;
        $this->utils = $utils;
        $this->output = $output;
        $this->locator = $locator;
        $this->composerJsonUpdater = $composerJsonUpdater;
    }

    static function factory(Cli $cli, Utils $utils, Output $output): self
    {
        return new self(
            $cli,
            $utils,
            $output,
            new CmsLocator(),
            new ComposerJsonUpdater($utils, $output)
        );
    }

    function fetch(bool $ifNotExist = false, bool $forceInstaller = false): void
    {
        $projectConfig = $this->cli->getProjectConfig();
        $extractor = $this->createExtractor($projectConfig->cms, $forceInstaller);

        // abort if files exist?
        if ($ifNotExist && $extractor->filesAlreadyExist()) {
            $this->output->log('CMS files already exist');

            return;
        }

        // locate
        $this->output->log('Locating archive');
        $archiveParams = $this->locator->locate($projectConfig->cms);

        // download
        $this->output->log('Downloading %s', $archiveParams->url);

        $tempPath = tempnam(sys_get_temp_dir(), 'slcms')
            or $this->cli->fail('Could not create a temporary file'); 

        try {
            $this->utils->downloadFile($archiveParams->url, $tempPath);

            // extract
            $this->output->log('Extracting archive');

            $result = $extractor->extract($tempPath, $archiveParams->pathsPrefix);
        } finally {
            @unlink($tempPath);
        }

        $this->output->log('Written %d files', $result->numWrittenFiles);
        $this->output->log('CMS version is %s', $result->version);

        // update composer.json
        $this->output->log('Updating composer.json');
        $this->composerJsonUpdater->update(
            $this->cli->getProjectRoot() . '/composer.json',
            $result,
            $archiveParams->isSemverMatched,
            $projectConfig->is_fresh_project
        );
    }

    private function createExtractor(CmsConfig $cmsConfig, bool $forceInstaller): CmsExtractor
    {
        $extractor = new CmsExtractor($this->cli->getProjectRoot());

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
