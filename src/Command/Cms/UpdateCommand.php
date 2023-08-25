<?php declare(strict_types=1);

namespace SunlightConsole\Command\Cms;

use SunlightConsole\Cms\CmsFetcher;
use SunlightConsole\Command;

class UpdateCommand extends Command
{
    function getHelp(): string
    {
        return 'update CMS files in the project';
    }

    function run(array $args): int
    {
        $fetcher = CmsFetcher::factory($this->cli, $this->utils, $this->output);
        $fetcher->fetch();

        $this->output->log('Done');

        return 0;
    }
}
