<?php declare(strict_types=1);

namespace SunlightConsole\Cms\Archive;

use Composer\Semver\Semver;
use SunlightConsole\Cms\CmsFacade;
use SunlightConsole\Config\Project\Cms\ArchiveConfig;
use SunlightConsole\Config\Project\CmsConfig;

class Locator
{
    function locate(CmsConfig $cmsConfig): ArchiveParams
    {
        $params = new ArchiveParams();
        $params->pathsPrefix = $cmsConfig->archive->zip_paths_prefix;
        $params->isSemverMatched = false;
        $params->isLatestVersion = false;

        // prepare params
        if ($cmsConfig->archive->zip_url !== null) {
            $params->version = $cmsConfig->version;
            $params->url = $cmsConfig->archive->zip_url;
        } else {
            $this->locateFromGit($params, $cmsConfig->archive, $cmsConfig->version);
        }

        // replace version placeholders
        $params->url = $this->replaceVersionPlaceholder($params->url, $params->version);
        $params->pathsPrefix = $this->replaceVersionPlaceholder($params->pathsPrefix, $params->version);

        return $params;
    }

    private function locateFromGit(ArchiveParams $params, ArchiveConfig $archiveConfig, string $version): void
    {
        if (strncmp('dev-', $version, 4) === 0) {
            // use a branch
            if ($archiveConfig->git_branch_zip_url === null) {
                throw new \Exception('Cannot get CMS from a GIT branch because git-branch-zip-url is not defined');
            }

            $params->version = substr($version, 4);
            $params->url = $archiveConfig->git_branch_zip_url;
        } else {
            // use a tag
            if ($archiveConfig->git_tag_zip_url === null) {
                throw new \Exception('Cannot get CMS from a GIT tag because git-tag-zip-url is not defined');
            }

            if (preg_match('{\d+\.\d+\.\d+$}AD', $version)) {
                // exact version provided
                $params->version = $version;
            } else {
                // try to match a constraint against existing tags
                if ($version === 'latest') {
                    $version = CmsFacade::CORE_VERSION_CONSTRAINT;
                    $params->isLatestVersion = true;
                }

                $params->version = $this->getLatestVersionFromGitTags($archiveConfig, $version);
                $params->isSemverMatched = true;
            }

            $params->url = $archiveConfig->git_tag_zip_url;
        }
    }

    private function getLatestVersionFromGitTags(ArchiveConfig $archiveConfig, string $versionConstraint): string
    {
        if ($archiveConfig->git_url === null) {
            throw new \Exception('Cannot list tags from CMS repository because git-url is not defined');
        }

        if ($archiveConfig->git_tag_pattern === null) {
            throw new \Exception('Cannot list tags from CMS repository because git-tag-pattern is not defined');
        }

        exec(
            sprintf(
                'git ls-remote --tags --refs --sort=%s %s %s',
                escapeshellarg('-v:refname'),
                escapeshellarg($archiveConfig->git_url),
                escapeshellarg($archiveConfig->git_tag_pattern)
            ),
            $output,
            $status
        );

        if ($status !== 0) {
            throw new \Exception('Could not list tags from CMS repository - check config or specify an exact version');
        }

        $foundVersions = [];

        foreach ($output as $line) {
            if (preg_match('{[[:xdigit:]]+\s+refs/tags/v?(.++)$}AD', $line, $match)) {
                $foundVersions[] = $match[1];

                try {
                    if (Semver::satisfies($match[1], $versionConstraint)) {
                        return $match[1];
                    }
                } catch (\UnexpectedValueException $e) {
                    // ignore tags that are not valid versions
                }
            }
        }

        throw new \Exception(sprintf(
            'Could not find a tag in CMS repository that satisfies "%s", found versions: %s',
            $versionConstraint,
            implode(', ', $foundVersions)
        ));
    }

    private function replaceVersionPlaceholder(string $string, string $version): string
    {
        return str_replace('%version%', $version, $string);
    }
}
