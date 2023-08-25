<?php declare(strict_types=1);

namespace SunlightConsole\Command\Database;

use SunlightConsole\Command;
use SunlightConsole\Argument\ArgumentDefinition;
use Sunlight\Database\Database as DB;
use Sunlight\Database\SqlDumper;

class DumpCommand extends Command
{
    function getHelp(): string
    {
        return 'dump database';
    }

    protected function defineArguments(): array
    {
        return [
            ArgumentDefinition::option('tables', 'comma-separated list of tables (otherwise all are dumped)'),
            ArgumentDefinition::flag('no-tables', 'don\'t dump table structure'),
            ArgumentDefinition::flag('no-data', 'don\'t dump rows'),
            ArgumentDefinition::argument(0, 'output-path', 'path where to write the SQL dump', true),
        ];
    }

    function run(array $args): int
    {
        $this->utils->initCms($this->cli->getProjectRoot());

        if (isset($args['tables'])) {
            $tables = explode(',', $args['tables']);
        } else {
            $tables = DB::getTablesByPrefix();
        }

        $dumper = new SqlDumper();
        $dumper->addTables($tables);
        $dumper->setDumpTables(!isset($args['no-tables']));
        $dumper->setDumpData(!isset($args['no-data']));

        $this->output->log('Dumping database');

        $tmpFile = $dumper->dump();
        $tmpFile->move($args['output-path']);

        $this->output->log('Done');

        return 0;
    }
}
