<?php declare(strict_types=1);

namespace SunlightConsole\Command\Database;

use Sunlight\Database\Database as DB;
use Sunlight\Database\DatabaseException;
use Sunlight\Util\Json;
use SunlightConsole\Argument\ArgumentDefinition;
use SunlightConsole\Cms\CmsFacade;
use SunlightConsole\Command;
use SunlightConsole\Util\Formatter;

class QueryCommand extends Command
{
    function getHelp(): string
    {
        return 'execute a SQL query';
    }

    protected function defineArguments(): array
    {
        return [
            ArgumentDefinition::flag('json', 'output as JSON'),
            ArgumentDefinition::argument(0, 'sql', 'single SQL query to execute', true),
        ];
    }

    function run(CmsFacade $cms, Formatter $formatter, array $args): int
    {
        $json = isset($args['json']);
        $cms->initMinimalWithDatabase();

        try {
            $result = DB::query($args['sql']);
        } catch (DatabaseException $e) {
            if ($json) {
                $this->output->write(Json::encode(['error' => $e->getMessage()], Json::PRETTY));
            } else {
                $this->output->write('Query has failed with an error: %s', $e->getMessage());
            }

            return 1;
        }

        if ($result instanceof \mysqli_result) {
            if ($json) {
                $this->output->write('[');

                for ($i = 1, $total = DB::size($result); $i <= $total; ++$i) {
                    $this->output->write('    %s%s', Json::encode(DB::row($result)), $i < $total ? ',' : '');
                }

                $this->output->write(']');
            } else {
                if (DB::size($result) > 0) {
                    $index = 0;

                    while ($row = DB::row($result)) {
                        $this->output->write('#%d %s', ++$index, $formatter->dump($row));
                    }
                } else {
                    $this->output->write('No rows returned');
                }
            }
        } else {
            if ($json) {
                $this->output->write(Json::encode(['affected_rows' => DB::affectedRows()], Json::PRETTY));
            } else {
                $this->output->write('Affected rows: %d', DB::affectedRows());
            }
        }

        return 0;
    }
}
