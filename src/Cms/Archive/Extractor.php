<?php declare(strict_types=1);

namespace SunlightConsole\Cms\Archive;

class Extractor
{
    /** @var string */
    private $targetDir;
    /** @var array<string, bool> path => replace? */
    private $paths = [
        'admin/' => true,
        'images/' => false,
        'plugins/config/' => false,
        'plugins/templates/.htaccess' => true,
        'plugins/.htaccess' => true,
        'system/' => true,
        'upload/' => false,
        '.htaccess' => false,
        'composer.json' => true,
        'index.php' => true,
        'robots.txt' => false,
    ];

    function __construct(string $targetDir)
    {
        $this->targetDir = $targetDir;
    }

    function addPlugin(string $typeDirectory, string $name, bool $overwrite): void
    {
        $this->paths["plugins/{$typeDirectory}/{$name}/config.php"] = false; // 8.0 BC
        $this->paths["plugins/{$typeDirectory}/{$name}/"] = $overwrite;
    }

    function addInstaller(): void
    {
        $this->paths['install/'] = true;
    }

    function filesAlreadyExist(): bool
    {
        foreach ($this->paths as $path => $overwrite) {
            if (!file_exists($this->targetDir . '/' . $path) && $overwrite) {
                return false;
            }
        }

        return true;
    }

    function extract(string $archivePath, string $archivePathsPrefix): ExtractionResult
    {
        $zip = new \ZipArchive();

        if ($zip->open($archivePath) !== true) {
            throw new \Exception(sprintf('Could not open "%s" as a ZIP archive', $archivePath));
        }

        $result = new ExtractionResult();
        $prefixLen = strlen($archivePathsPrefix);
        $purgedDirMap = [];

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $stat = $zip->statIndex($i);

            // ignore entries outside of prefix
            if (strncmp($stat['name'], $archivePathsPrefix, $prefixLen) !== 0) {
                continue;
            }

            $unprefixedPath = substr($stat['name'], $prefixLen);
            $cmsPath = $this->matchCmsPath($unprefixedPath);

            // ignore unmapped entries
            if ($cmsPath === null) {
                continue;
            }

            $targetPath = $this->targetDir . '/' . $unprefixedPath;

            // ignore existing paths if not overwriting
            if (!$cmsPath->overwrite && file_exists($targetPath)) {
                continue;
            }

            // handle directory entries
            if ($stat['name'][-1] === '/') {
                if (!is_dir($targetPath) && !@mkdir($targetPath, 0777, true)) {
                    throw new \Exception(sprintf('Could not create directory "%s"', $targetPath));
                }

                continue;
            }

            // purge each mapped directory once when overwriting
            if (
                $cmsPath->overwrite
                && $cmsPath->isDir()
                && !isset($purgedDirMap[$cmsPath->path])
            ) {
                if (is_dir($this->targetDir . '/' . $cmsPath->path)) {
                    $this->purgeDirectory($this->targetDir . '/' . $cmsPath->path);
                }

                $purgedDirMap[$cmsPath->path] = true;
            }

            // create directory structure
            $targetDir = dirname($targetPath);

            if (!is_dir($targetDir) && !@mkdir($targetDir, 0777, true)) {
                throw new \Exception(sprintf('Could not create directory "%s"', $targetDir));
            }

            // write file
            $data = $zip->getFromIndex($stat['index']);

            if ($data === false) {
                throw new \Exception(sprintf('Could not load ZIP entry "%s"', $stat['name']));
            }

            if (basename($unprefixedPath) === '.gitkeep') {
                // skip .gitkeep files
                continue;
            } elseif ($unprefixedPath === 'composer.json') {
                // skip composer.json, but store it in the result
                $result->composerJson = $data;
                continue;
            } elseif ($unprefixedPath === 'system/class/Core.php') {
                // store core version in the result
                $result->version = $this->parseCoreVersion($data);
            }

            if (file_put_contents($targetPath, $data) !== strlen($data)) {
                throw new \Exception(sprintf('Could not fully write "%s"', $targetPath));
            }

            ++$result->numWrittenFiles;
        }

        return $result;
    }

    private function matchCmsPath(string $path): ?CmsPath
    {
        foreach ($this->paths as $cmsPath => $overwrite) {
            if (strncmp($path, $cmsPath, strlen($cmsPath)) === 0) {
                return new CmsPath($cmsPath, $overwrite);
            }
        }

        return null;
    }

    private function parseCoreVersion(string $coreClassSource): ?string
    {
        if (preg_match('{^ *+const VERSION = \'([^\']++)\';}m', $coreClassSource, $match)) {
            return $match[1];
        }

        return null;
    }

    private function purgeDirectory(string $path): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $path,
                \FilesystemIterator::CURRENT_AS_FILEINFO
                | \FilesystemIterator::SKIP_DOTS
                | \FilesystemIterator::UNIX_PATHS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                if (!@rmdir($item->getPathname())) {
                    throw new \Exception(sprintf('Could not remove directory "%s"', $item));
                }
            } else {
                if (!@unlink($item->getPathname())) {
                    throw new \Exception(sprintf('Could not remove file "%s"', $item));
                }
            }
        }
    }
}
