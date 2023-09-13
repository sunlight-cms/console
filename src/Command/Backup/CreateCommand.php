<?php declare(strict_types=1);

namespace SunlightConsole\Command\Backup;

use SunlightConsole\Command;
use SunlightConsole\Argument\ArgumentDefinition;
use Sunlight\Backup\BackupBuilder;
use SunlightConsole\Util\CmsFacade;

class CreateCommand extends Command
{
    function getHelp(): string
    {
        return 'create a backup';
    }

    protected function defineArguments(): array
    {
        return [
            ArgumentDefinition::flag('database', 'include a database dump'),
            ArgumentDefinition::flag('plugins', 'backup plugins'),
            ArgumentDefinition::flag('images', 'backup images'),
            ArgumentDefinition::flag('upload', 'backup upload directory'),
            ArgumentDefinition::argument(0, 'output-path', 'path where to write the .zip file', true),
        ];
    }

    function run(CmsFacade $cms, array $args): int
    {
        $cms->init();

        $this->output->write('Creating a backup');

        $builder = new BackupBuilder();
        $builder->makeDynamicPathOptionalInFullBackup('plugins');
        $builder->setDatabaseDumpEnabled(isset($args['database']));
        
        if (!isset($args['plugins'])) {
            $builder->disableDynamicPath('plugins');
        }
        if (!isset($args['images'])) {
            $builder->disableDynamicPath('images_user');
            $builder->disableDynamicPath('images_articles');
            $builder->disableDynamicPath('images_galleries');
        }
        if (!isset($args['upload'])) {
            $builder->disableDynamicPath('upload');
        }

        $tmpFile = $builder->build();
        $tmpFile->move($args['output-path']);

        $this->output->write('Done');

        return 0;
    }
}
