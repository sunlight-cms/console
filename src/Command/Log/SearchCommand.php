<?php declare(strict_types=1);

namespace SunlightConsole\Command\Log;

use Sunlight\Logger;
use SunlightConsole\Argument\ArgumentDefinition;
use SunlightConsole\Cms\CmsFacade;
use SunlightConsole\Command;
use SunlightConsole\Util\Formatter;

class SearchCommand extends Command
{
    function getHelp(): string
    {
        return 'search log entries';
    }

    function defineArguments(): array
    {
        return [
            ArgumentDefinition::option('level', 'only include messages at this level or more severe (example: notice)'),
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

    function run(CmsFacade $cms, LogQueryFactory $queryFactory, Formatter $formatter, array $args): int
    {
        $cms->init();

        $query = $queryFactory->createFromArgs($args);
        $entries = Logger::search($query);

        if (empty($entries)) {
            $this->output->write('No entries found');

            return 0;
        }

        foreach ($entries as $entry) {
            $this->output->write($formatter->logEntry($entry));
        }

        return 0;
    }
}
