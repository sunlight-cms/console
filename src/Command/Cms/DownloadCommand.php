<?php declare(strict_types=1);

namespace SunlightConsole\Command\Cms;

use SunlightConsole\Argument\ArgumentDefinition;
use SunlightConsole\Cms\CmsFetcher;
use SunlightConsole\Command;

class DownloadCommand extends Command
{
    function getHelp(): string
    {
        return 'download CMS files';
    }

    function defineArguments(): array
    {
        return [
            ArgumentDefinition::flag('overwrite', 'overwrite existing CMS files'),
            ArgumentDefinition::flag('with-installer', 'include the install/ directory'),
        ];
    }

    function run(array $args): int
    {
        $fetcher = CmsFetcher::factory($this->cli, $this->utils, $this->output);
        $fetcher->fetch(isset($args['overwrite']), isset($args['with-installer']));

        $this->output->write('Done');

        return 0;
    }
}
