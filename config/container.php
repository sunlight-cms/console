<?php declare(strict_types=1);

namespace SunlightConsole;

use SunlightConsole\DependencyInjection\Container;
use SunlightConsole\DependencyInjection\ServiceDefinition as Def;

$container = new Container();

$container->define(
    // main services
    Def::service(Cli::class),
    Def::service(CommandLoader::class),
    Def::service(CommandInitializer::class),
    Def::service(Output::class),
    Def::service(Project::class),

    // cms services
    Def::service(Cms\Archive\Locator::class),
    Def::service(Cms\CmsFacade::class),
    Def::service(Cms\CmsFetcher::class),
    Def::service(Cms\ComposerJsonUpdater::class),

    // utils
    Def::service(Util\FileDownloader::class),
    Def::service(Util\Formatter::class),
    Def::service(Util\StringHelper::class),

    // base command
    Def::base(Command::class)
        ->init('@SunlightConsole\CommandInitializer::initialize'),

    // cache
    Def::service(Command\Cache\ClearCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'cache.clear']),

    // backup
    Def::service(Command\Backup\CreateCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'backup.create']),

    // cms
    Def::service(Command\Cms\InfoCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'cms.info']),

    Def::service(Command\Cms\DownloadCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'cms.download']),

    Def::service(Command\Cms\PatchCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'cms.patch']),

    // config
    Def::service(Command\Config\CreateCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'config.create']),

    Def::service(Command\Config\SetCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'config.set']),

    Def::service(Command\Config\DumpCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'config.dump']),

    // database
    Def::service(Command\Database\DumpCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'db.dump']),

    Def::service(Command\Database\ImportCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'db.import']),

    Def::service(Command\Database\QueryCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'db.query']),

    // log
    Def::service(Command\Log\SearchCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'log.search']),

    Def::service(Command\Log\MonitorCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'log.monitor']),

    // plugin
    Def::service(Command\Plugin\ListCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'plugin.list']),

    Def::service(Command\Plugin\ShowCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'plugin.show']),

    Def::service(Command\Plugin\InstallCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'plugin.install']),

    Def::service(Command\Plugin\ActionCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'plugin.action']),

    // user
    Def::service(Command\User\ResetPasswordCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'user.reset-password']),

    // project
    Def::service(Command\Project\DumpConfigCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'project.dump-config']),

    // help
    Def::service(Command\HelpCommand::class)
        ->extend(Command::class)
        ->tag('console.command', ['name' => 'help', 'group' => 3])
);
