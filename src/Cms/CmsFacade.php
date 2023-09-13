<?php declare(strict_types=1);

namespace SunlightConsole\Cms;

use Sunlight\Core;
use Sunlight\Plugin\Plugin;
use SunlightConsole\Project;

class CmsFacade
{
    /** @var Project */
    private $project;

    function __construct(Project $project)
    {
        $this->project = $project;
    }

    function ensureClassesAvailable(): void
    {
        if (class_exists(Core::class) || $this->tryLoadCmsClasses()) {
            return;
        }

        throw new \Exception('CMS classes are not available');
    }

    function tryLoadCmsClasses(): bool
    {
        // include the project's autoloader only if the console is being used as standalone
        if (!isset($GLOBALS['_composer_autoload_path'])) {
            require $this->project->getRoot() . '/vendor/autoload.php';
        }

        return class_exists(Core::class);
    }

    function init(array $options = []): void
    {
        $this->ensureClassesAvailable();

        if (Core::isReady()) {
            throw new \Exception('Core is already initialized');
        }

        try {
            // set class loader
            // (including the same autoload.php again just returns the existing autoloader instance)
            Core::$classLoader = require $this->project->getRoot() . '/vendor/autoload.php';

            // init core
            Core::init($options + ['session_enabled' => false, 'debug' => true]);
        } catch (\Throwable $e) {
            throw new \Exception(sprintf(
                'Could not initialize CMS: %s in %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ), 0, $e);
        }
    }

    function findPlugin(string $name): ?Plugin
    {
        $plugins = Core::$pluginManager->getPlugins();

        return $plugins->get($name)
            ?? $plugins->getInactive($name)
            ?? $plugins->getExtend($name)
            ?? $plugins->getInactiveByName('extend', $name)
            ?? $plugins->getTemplate($name)
            ?? $plugins->getInactiveByName('template', $name)
            ?? $plugins->getLanguage($name)
            ?? $plugins->getInactiveByName('language', $name);
    }
}
