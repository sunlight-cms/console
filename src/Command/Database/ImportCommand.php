<?php declare(strict_types=1);

namespace SunlightConsole\Command\Database;

use Sunlight\Database\Database as DB;
use Sunlight\Database\DatabaseLoader;
use Sunlight\Database\SqlReader;
use SunlightConsole\Argument\ArgumentDefinition;
use SunlightConsole\Cms\CmsFacade;
use SunlightConsole\Command;

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
            ArgumentDefinition::flag('drop-all-system-tables', 'drop ALL existing system tables before importing'),
            ArgumentDefinition::argument(0, 'sql-path', 'path to a SQL file (otherwise reads from STDIN)'),
        ];
    }

    function run(CmsFacade $cms, array $args): int
    {
        $cms->initMinimalWithDatabase();

        $this->output->write('Reading SQL');

        if (isset($args['sql-path'])) {
            $reader = SqlReader::fromFile($args['sql-path']);
        } else {
            $reader = new SqlReader(stream_get_contents(STDIN));
        }

        if (isset($args['drop-all-system-tables'])) {
            $this->output->write('Dropping tables');
            DatabaseLoader::dropTables(DB::getTablesByPrefix());
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
