<?php declare(strict_types=1);

namespace SunlightConsole;

use Composer\InstalledVersions;
use Kuria\Options\Exception\ResolverException;
use SunlightConsole\Argument\ArgumentParser;
use SunlightConsole\Config\ProjectConfig;

class Cli
{
    /** @var Utils */
    private $utils;
    /** @var Output */
    private $output;
    /** @var string|null */
    private $projectRoot;
    /** @var ProjectConfig|null */
    private $projectConfig;
    /** @var array<string, class-string<Command>>|null */
    private $commands;

    function __construct()
    {
        $this->utils = new Utils();
        $this->output = new Output();
    }

    function run(array $args): int
    {
        try {
            // get command
            $command = $this->matchCommand($args[0] ?? 'help');

            if ($command === null) {
                $this->fail('Unknown command');
            }

            // handle --help
            for ($i = 1; isset($args[$i]); ++$i) {
                if ($args[$i] === '--help') {
                    return $this->getCommand('help')->run(['command' => $args[0]]);
                }
            }

            // parse arguments
            $commandArgs = (new ArgumentParser())->parse($command->getArguments(), array_slice($args, 1));

            // run command
            return $command->run($commandArgs);
        } catch (\Throwable $e) {
            $this->output->log("ERROR: %s\n(%s:%d)", $e->getMessage(), $e->getFile(), $e->getLine());

            return 1;
        }
    }

    function getProjectRoot(): string
    {
        return $this->projectRoot ?? ($this->projectRoot = $this->determineProjectRoot());
    }

    function getProjectConfig(): ProjectConfig
    {
        return $this->projectConfig ?? ($this->projectConfig = $this->loadProjectConfig());
    }

    /**
     * @return array<string, class-string<Command>>
     */
    function getCommands(): array
    {
        return $this->commands ?? ($this->commands = $this->loadCommands());
    }

    /**
     * @return string[]
     */
    function getCommandNames(): array
    {
        return array_keys($this->getCommands());
    }

    function getCommand(string $name): ?Command
    {
        $commandClass = $this->getCommands()[$name] ?? null;

        if ($commandClass === null) {
            return null;
        }

        return new $commandClass($this, $this->utils, $this->output, $name);
    }

    function matchCommand(string $name): ?Command
    {
        $command = $this->getCommand($name);

        if ($command !== null) {
            return $command;
        }

        if (strpbrk($name, '*?[]') !== false) {
            return null;
        }

        $pattern = str_replace('.', '*.', $name) . '*';

        $matchingCommands = array_keys(
            array_filter(
                $this->getCommands(),
                function (string $commandName) use ($pattern) {
                    return fnmatch($pattern, $commandName, FNM_NOESCAPE | FNM_CASEFOLD);
                },
                ARRAY_FILTER_USE_KEY
            )
        );

        return count($matchingCommands) === 1 ? $this->getCommand(current($matchingCommands)) : null;
    }

    /**
     * @psalm-return never
     * @throws \Exception
     */
    function fail(string $message, ...$params): void
    {
        throw new \Exception(sprintf($message, ...$params));
    }

    private function determineProjectRoot(): string
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

    private function loadProjectConfig(): ProjectConfig
    {
        $composerJsonPath = $this->getProjectRoot() . '/composer.json';

        try {
            return ProjectConfig::loadFromComposerPackage(
                $this->utils->loadJsonFromFile($composerJsonPath)
            );
        } catch (ResolverException $e) {
            throw new \Exception(
                sprintf(
                    "Invalid extra[%s] configuration in \"%s\":\n\n%s", 
                    ProjectConfig::COMPOSER_EXTRA_KEY,
                    $composerJsonPath,
                    implode("\n", $e->getErrors())
                ),
                0,
                $e
            );
        }
    }

    /**
     * @return array<string, class-string<Command>>
     */
    private function loadCommands(): array
    {
        $commands = [
            'cms.info' => Command\Cms\InfoCommand::class,
            'cms.download' => Command\Cms\DownloadCommand::class,
            'cms.update' => Command\Cms\UpdateCommand::class,
            'cms.patch' => Command\Cms\PatchCommand::class,
            'config.create' => Command\Config\CreateCommand::class,
            'config.set' => Command\Config\SetCommand::class,
            'config.dump' => Command\Config\DumpCommand::class,
            'plugin.list' => Command\Plugin\ListCommand::class,
            'plugin.show' => Command\Plugin\ShowCommand::class,
            'plugin.install' => Command\Plugin\InstallCommand::class,
            'plugin.action' => Command\Plugin\ActionCommand::class,
            'db.dump' => Command\Database\DumpCommand::class,
            'db.import' => Command\Database\ImportCommand::class,
            'db.query' => Command\Database\QueryCommand::class,
            'user.reset-password' => Command\User\ResetPasswordCommand::class,
            'backup' => Command\BackupCommand::class,
            'clear-cache' => Command\ClearCacheCommand::class,
            'project.dump-config' => Command\Project\DumpConfigCommand::class,
        ];

        $commands += $this->getProjectConfig()->commands;
        $commands += $this->loadComposerPackageCommands();
        $commands['help'] = Command\HelpCommand::class;

        return $commands;
    }

    private function loadComposerPackageCommands(): array
    {
        $installedJsonPath = $this->getProjectRoot() . '/vendor/composer/installed.json';

        if (!is_file($installedJsonPath)) {
            return [];
        }

        $packages = $this->utils->loadJsonFromFile($installedJsonPath);
        $packages = $packages['packages'] ?? $packages; // composer 1 vs 2

        $packageCommands = [];

        foreach ($packages as $package) {
            if ($package['type'] === 'project') {
                continue;
            }

            // optimalization: we only need the commands, get them directly without resolving the entire config
            if (isset($package['extra'][ProjectConfig::COMPOSER_EXTRA_KEY]['commands'])) {
                $packageCommands += $package['extra'][ProjectConfig::COMPOSER_EXTRA_KEY]['commands'];
            }
        }

        return $packageCommands;
    }
}
