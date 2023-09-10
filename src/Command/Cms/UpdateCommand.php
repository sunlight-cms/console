<?php declare(strict_types=1);

namespace SunlightConsole\Command\Cms;

use SunlightConsole\Argument\ArgumentDefinition;
use SunlightConsole\Cms\CmsFetcher;
use SunlightConsole\Command;

class UpdateCommand extends Command
{
    function getHelp(): string
    {
        return 'update CMS files in the project';
    }

    function defineArguments(): array
    {
        return [
            ArgumentDefinition::flag('with-installer', 'include the install/ directory'),
        ];
    }

    function run(array $args): int
    {
        $fetcher = CmsFetcher::factory($this->cli, $this->utils, $this->output);
        $fetcher->fetch(false, isset($args['with-installer']));

        $this->output->write('Done');

        return 0;
    }
}
