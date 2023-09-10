<?php declare(strict_types=1);

namespace SunlightConsole\Command\Log;

use Sunlight\Log\LogEntry;
use Sunlight\Log\LogQuery;
use Sunlight\Logger;
use SunlightConsole\Argument\ArgumentDefinition;
use SunlightConsole\Command;

class SearchCommand extends Command
{
    function getHelp(): string
    {
        return 'search log entries';
    }

    function defineArguments(): array
    {
        return [
            ArgumentDefinition::option('max-level', 'only include messages at this level or more severe (example: notice)'),
            ArgumentDefinition::option('category', 'include only this category (example: system)'),
            ArgumentDefinition::option('since', 'strtotime-compatible lower time bound (example: "this monday")'),
            ArgumentDefinition::option('until', 'strtotime-compatible upper time bound (example: "1 hour ago")'),
            ArgumentDefinition::option('keyword', 'string to match in message text (example: "Error")'),
            ArgumentDefinition::option('method', 'http method to match (example: "POST")'),
            ArgumentDefinition::option('url-keyword', 'string to match in request URL (example: "/admin/")'),
            ArgumentDefinition::option('ip', 'IP address to match (example: "127.0.0.1")'),
            ArgumentDefinition::option('user-id', 'user ID to match (example: 123)'),
            ArgumentDefinition::flag('desc', 'print newer messages first'),
            ArgumentDefinition::option('offset', 'skip the first N messages'),
            ArgumentDefinition::option('limit', 'maximum number of messages shown (default: 100)'),
        ];
    }

    function run(array $args): int
    {
        $this->utils->initCms($this->cli->getProjectRoot());

        $query = $this->createQuery($args);
        $entries = Logger::search($query);

        if (empty($entries)) {
            $this->output->write('No entries found');

            return 0;
        }

        foreach ($entries as $entry) {
            $this->output->write($this->utils->renderLogEntry($entry));
        }

        return 0;
    }

    private function createQuery(array $args): LogQuery
    {
        $query = new LogQuery();

        // map string options
        $stringOptionMap = [
            'category' => 'category',
            'keyword' => 'keyword',
            'method' => 'method',
            'url-keyword' => 'urlKeyword',
            'ip' => 'ip',
        ];

        foreach ($stringOptionMap as $option => $prop) {
            if (isset($args[$option])) {
                $query->{$prop} = $args[$option];
            }
        }

        // map numeric options
        $numericOptionMap = [
            'user-id' => 'userId',
            'offset' => 'offset',
            'limit' => 'limit',
        ];

        foreach ($numericOptionMap as $option => $prop) {
            if (isset($args[$option])) {
                ctype_digit($args[$option])
                    or $this->cli->fail('Invalid --%s, a number is required', $option);

                $query->{$prop} = (int) $args[$option];
            }
        }

        // max-level
        if (isset($args['max-level'])) {
            if (ctype_digit($args['max-level'])) {
                $maxLevel = (int) $args['max-level'];

                isset(Logger::LEVEL_NAMES[$maxLevel])
                    or $this->cli->fail('Invalid --max-level, valid levels are %d to %d', Logger::EMERGENCY, Logger::DEBUG);
            } else {
                $maxLevel = array_search($args['max-level'], Logger::LEVEL_NAMES, true);

                $maxLevel !== false
                    or $this->cli->fail('Invalid --max-level, valid names are: %s', implode(', ', Logger::LEVEL_NAMES));
            }

            $query->maxLevel = $maxLevel;
        }

        // since
        if (isset($args['since'])) {
            $since = strtotime($args['since']);

            $since !== false
                or $this->cli->fail('Could not convert --since value to a timestamp');

            $query->since = $since;
        }

        // until
        if (isset($args['until'])) {
            $until = strtotime($args['until']);

            $until !== false
                or $this->cli->fail('Could not convert --until value to a timestamp');

            $query->until = $until;
        }

        // desc
        $query->desc = isset($args['desc']);

        return $query;
    }
}
