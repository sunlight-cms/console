<?php declare(strict_types=1);

namespace SunlightConsole\Command\Cms;

use Sunlight\Backup\Backup;
use Sunlight\Backup\BackupRestorer;
use Sunlight\Util\Filesystem;
use SunlightConsole\Argument\ArgumentDefinition;
use SunlightConsole\Cms\CmsFacade;
use SunlightConsole\Cms\ComposerJsonUpdater;
use SunlightConsole\Command;
use SunlightConsole\JsonObject;
use SunlightConsole\Project;
use SunlightConsole\Util\FileDownloader;

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

    function run(
        Project $project,
        CmsFacade $cms,
        FileDownloader $fileDownloader,
        array $args
    ): int {
        $cms->init();

        // get patch archive
        if (isset($args['from-path'])) {
            $path = $args['from-path'];
        } elseif (isset($args['from-url'])) {
            $tmpFile = Filesystem::createTmpFile();
            $path = $tmpFile->getPathname();
            $fileDownloader->download($args['from-url'], $path);
        } else {
            $this->output->fail('Specify --from-path or --from-url');
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

        // prepare arguments
        $directories = $patch->getMetaData('directory_list');
        $files = $patch->getMetaData('file_list');

        // do not overwrite composer.json or the vendor directory
        $this->removeListValue($directories, 'vendor');
        $patchHasComposerJson = $this->removeListValue($files, 'composer.json');

        // apply patch
        $this->output->write('Applying patch');

        if (!$restorer->restore(true, $directories, $files, $errors)) {
            $this->output->write('Cannot apply patch:');
            $this->printErrorList($errors);

            return 1;
        }

        $newVersion = $patch->getMetaData('patch')['new_system_version'];
        $this->output->write('CMS version is now %s', $newVersion);

        // update composer.json
        $this->output->write('Updating composer.json');
        $composerJsonUpdater = new ComposerJsonUpdater($project->getComposerJson(), $this->output);

        if (!isset($args['keep-version'])) {
            // update cms.version
            $composerJsonUpdater->updateCmsVersion($newVersion);
        }

        if (
            $patchHasComposerJson
            && ($patchComposerJson = $patch->getFile($patch->getDataPath() . '/composer.json')) !== null
        ) {
            // update dependencies
            $patchComposerJson = JsonObject::fromJson($patchComposerJson);
            $composerJsonUpdater->updateDependencies($patchComposerJson['require'] ?? []);
        }

        $composerJsonUpdater->save();

        $this->output->write('Done');

        return 0;
    }

    private function printErrorList(array $errors): void
    {
        foreach ($errors as $error) {
            $this->output->write('- %s', $error);
        }
    }

    private function removeListValue(array &$list, string $value): bool
    {
        $offset = array_search($value, $list, true);

        if ($offset !== false) {
            array_splice($list, $offset, 1);

            return true;
        }

        return false;
    }
}
