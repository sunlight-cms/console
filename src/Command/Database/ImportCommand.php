<?php declare(strict_types=1);

namespace SunlightConsole\Command\Database;

use SunlightConsole\Command;
use SunlightConsole\Argument\ArgumentDefinition;
use Sunlight\Database\Database as DB;
use Sunlight\Database\DatabaseLoader;
use Sunlight\Database\SqlReader;
use SunlightConsole\Util\CmsFacade;

class ImportCommand extends Command
{
    function getHelp(): string
    {
        return 'import a SQL dump';
    }

    protected function defineArguments(): array
    {
        return [
            ArgumentDefinition::option('prefix', 'prefix used in the SQL dump to replace with current prefix'),
            ArgumentDefinition::argument(0, 'sql-path', 'path to a SQL file (otherwise reads from STDIN)'),
        ];
    }

    function run(CmsFacade $cms, array $args): int
    {
        $cms->init();

        $this->output->write('Reading SQL');

        if (isset($args['sql-path'])) {
            $reader = SqlReader::fromFile($args['sql-path']);
        } else {
            $reader = new SqlReader(stream_get_contents(STDIN));
        }

        $this->output->write('Importing SQL');

        DatabaseLoader::load(
            $reader,
            $args['prefix'] ?? null,
            isset($args['prefix'])
                ? DB::$prefix
                : null
        );

        $this->output->write('Done');

        return 0;
    }
}
