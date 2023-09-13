<?php declare(strict_types=1);

namespace SunlightConsole;

use Composer\InstalledVersions;
use Kuria\Options\Exception\ResolverException;
use SunlightConsole\Config\ProjectConfig;

class Project
{
    /** @var string|null */
    private $root;
    /** @var ProjectConfig|null */
    private $config;

    function getRoot(): string
    {
        return $this->root ?? ($this->root = $this->determineRoot());
    }

    function getConfig(): ProjectConfig
    {
        return $this->config ?? ($this->config = $this->loadConfig());
    }

    function getComposerJsonPath(): string
    {
        return $this->getRoot() . '/composer.json';
    }

    private function determineRoot(): string
    {
        $root = getenv('SL_CONSOLE_PROJECT_ROOT');

        if ($root !== false) {
            // from env
            $root = realpath($root);

            if ($root === false) {
                throw new \Exception('Invalid path in SL_CONSOLE_PROJECT_ROOT');
            }

            return $root;
        }

        if (class_exists(InstalledVersions::class)) {
            $root = realpath(InstalledVersions::getRootPackage()['install_path']);

            if ($root === false) {
                throw new \Exception('Invalid root package install_path');
            }

            return $root;
        }

        throw new \Exception('Cannot determine path to project root');
    }

    private function loadConfig(): ProjectConfig
    {
        try {
            return ProjectConfig::loadFromComposerJson(
                JsonObject::fromFile($this->getComposerJsonPath())
            );
        } catch (ResolverException $e) {
            throw new \Exception(
                sprintf(
                    "Invalid extra[%s] configuration in \"%s\"", 
                    ProjectConfig::COMPOSER_EXTRA_KEY,
                    $this->getComposerJsonPath()
                ),
                0,
                $e
            );
        }
    }
}
