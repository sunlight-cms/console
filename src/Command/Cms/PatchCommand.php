<?php declare(strict_types=1);

namespace SunlightConsole\Command\Cms;

use Sunlight\Backup\Backup;
use Sunlight\Backup\BackupRestorer;
use Sunlight\Util\Filesystem;
use SunlightConsole\Argument\ArgumentDefinition;
use SunlightConsole\Cms\CmsFetcher;
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
        ];
    }

    function run(array $args): int
    {
        $this->utils->initCms($this->cli->getProjectRoot());

        // get
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

        // load
        $patch = new Backup($path);
        $patch->open();
        $restorer = new BackupRestorer($patch);

        // validate
        if (!$restorer->validate(true, $errors)) {
            $this->output->write('Invalid patch:');
            $this->printErrorList($errors);

            return 1;
        }

        // apply
        if (!$restorer->restore(true, null, null, $errors)) {
            $this->output->write('Cannot apply patch:');
            $this->printErrorList($errors);

            return 1;
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
