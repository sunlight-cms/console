<?php declare(strict_types=1);

namespace SunlightConsole;

use SunlightConsole\Config\ProjectConfig;
use SunlightConsole\Config\ServiceConfig;
use SunlightConsole\DependencyInjection\Container;

class CommandLoader
{
    /** @var Container */
    private $container;
    /** @var Project */
    private $project;

    function __construct(Container $container, Project $project)
    {
        $this->container = $container;
        $this->project = $project;
    }

    /**
     * @return array<string, string> name => service ID
     */
    function load(): array
    {
        $this->loadProjectCommands();
        $this->loadComposerPackageCommands();

        $commands = $this->container->getTagged('console.command');

        uasort($commands, function (array $a, array $b) {
            $cmp = ($a['group'] ?? 0) <=> ($b['group'] ?? 0);

            return $cmp !== 0 ? $cmp : $a['name'] <=> $b['name'];
        });

        return array_combine(array_column($commands, 'name'), array_keys($commands));
    }

    private function loadProjectCommands(): void
    {
        foreach ($this->project->getConfig()->commands as $name => $command) {
            $this->container->define(
                $command->toDefinition("project_command.{$name}")
                    ->extend(Command::class)
                    ->tag('console.command', ['name' => (string) $name, 'group' => 1])
            );
        }
    }

    private function loadComposerPackageCommands(): void
    {
        $installedJsonPath = $this->project->getRoot() . '/vendor/composer/installed.json';

        if (!is_file($installedJsonPath)) {
            return;
        }

        $packages = JsonObject::fromFile($installedJsonPath);
        $packages = $packages['packages'] ?? $packages; // composer 1 vs 2

        foreach ($packages as $package) {
            if ($package['type'] === 'project') {
                continue;
            }

            if (!empty($commands = $package['extra'][ProjectConfig::COMPOSER_EXTRA_KEY]['commands'] ?? [])) {
                try {
                    foreach ($commands as $name => $command) {
                        $this->container->define(
                            ServiceConfig::load($command)->toDefinition("package_command.{$name}")
                                ->extend(Command::class)
                                ->tag('console.command', ['name' => (string) $name, 'group' => 2])
                        );
                    }
                } catch (\Throwable $e) {
                    throw new \Exception(sprintf('Error when loading console commands from composer package "%s"', $package['name']), 0, $e);
                }
            }
        }
    }
}
