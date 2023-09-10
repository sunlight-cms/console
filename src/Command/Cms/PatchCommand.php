<?php declare(strict_types=1);

namespace SunlightConsole\Command\Cms;

use Sunlight\Backup\Backup;
use Sunlight\Backup\BackupRestorer;
use Sunlight\Util\Filesystem;
use SunlightConsole\Argument\ArgumentDefinition;
use SunlightConsole\Cms\ComposerJsonUpdater;
use SunlightConsole\Command;

class PatchCommand extends Command
{
    function getHelp(): string
    {
        return 'apply a patch to CMS files in the project';
    }

    function defineArguments(): array
    {
        return [
            ArgumentDefinition::option('from-path', 'path to a patch ZIP file'),
            ArgumentDefinition::option('from-url', 'patch ZIP download URL'),
            ArgumentDefinition::flag('keep-version', 'do not update cms.version in composer.json')
        ];
    }

    function run(array $args): int
    {
        $this->utils->initCms($this->cli->getProjectRoot());

        // get patch archive
        if (isset($args['from-path'])) {
            $path = $args['from-path'];
        } elseif (isset($args['from-url'])) {
            $tmpFile = Filesystem::createTmpFile();
            $path = $tmpFile->getPathname();

            $this->output->write('Downloading %s', $args['from-url']);
            $this->utils->downloadFile($args['from-url'], $path);
        } else {
            $this->cli->fail('Specify --from-path or --from-url');
        }

        // load patch
        $this->output->write('Loading patch');

        $patch = new Backup($path);
        $patch->open();
        $restorer = new BackupRestorer($patch);

        // validate patch
        if (!$restorer->validate(true, $errors)) {
            $this->output->write('Invalid patch:');
            $this->printErrorList($errors);

            return 1;
        }

        // apply patch
        $this->output->write('Applying patch');

        if (!$restorer->restore(true, null, null, $errors)) {
            $this->output->write('Cannot apply patch:');
            $this->printErrorList($errors);

            return 1;
        }

        $newVersion = $patch->getMetaData('patch')['new_system_version'];
        $this->output->write('CMS version is now %s', $newVersion);

        // update composer.json
        if (!isset($args['keep-version'])) {
            $this->output->write('Updating composer.json');

            (new ComposerJsonUpdater($this->cli, $this->output))
                ->updateProjectConfig(['cms' => ['version' => $newVersion]]);
        }

        $this->output->write('Done');

        return 0;
    }

    private function printErrorList(array $errors): void
    {
        foreach ($errors as $error) {
            $this->output->write('- %s', $error);
        }
    }
}
