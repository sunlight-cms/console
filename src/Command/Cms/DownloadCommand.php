<?php declare(strict_types=1);

namespace SunlightConsole\Command\Cms;

use SunlightConsole\Cms\CmsFetcher;
use SunlightConsole\Command;

class DownloadCommand extends Command
{
    function getHelp(): string
    {
        return 'download CMS files if they do not alraedy exist';
    }

    function run(array $args): int
    {
        $fetcher = CmsFetcher::factory($this->cli, $this->utils, $this->output);
        $fetcher->fetch(true);

        $this->output->log('Done');

        return 0;
    }
}
