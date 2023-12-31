<?php declare(strict_types=1);

namespace SunlightConsole\Command\Log;

use Sunlight\Log\LogEntry;
use Sunlight\Log\LogQuery;
use Sunlight\Logger;
use SunlightConsole\Argument\ArgumentDefinition;
use SunlightConsole\Cms\CmsFacade;
use SunlightConsole\Command;
use SunlightConsole\Util\Formatter;

class MonitorCommand extends Command
{
    function getHelp(): string
    {
        return 'continuously print out log entries';
    }

    function defineArguments(): array
    {
        return [
            ArgumentDefinition::option('level', 'only include messages at this level or more severe (example: notice)'),
            ArgumentDefinition::option('category', 'include only this category (example: system)'),
            ArgumentDefinition::option('keyword', 'string to match in message text (example: "Error")'),
            ArgumentDefinition::option('method', 'http method to match (example: "POST")'),
            ArgumentDefinition::option('url-keyword', 'string to match in request URL (example: "/admin/")'),
            ArgumentDefinition::option('ip', 'IP address to match (example: "127.0.0.1")'),
            ArgumentDefinition::option('user-id', 'user ID to match (example: 123)'),
            ArgumentDefinition::option('limit', 'initial maximum number of last messages shown (default: 10)'),
            ArgumentDefinition::option('load-limit', 'maximum number of new messaages to load in one query (default: 100)'),
            ArgumentDefinition::option('delay', 'number of seconds to wait before loading more messages (default: 5)', false),
            ArgumentDefinition::flag('new-only', 'only output entries created since the command has started running'),
            ArgumentDefinition::flag('bell', 'output the BEL character every time there are new entries'),
        ];
    }

    function run(CmsFacade $cms, LogQueryFactory $queryFactory, Formatter $formatter, array $args): int
    {
        $limit = max(1, (int) ($args['limit'] ?? 10));
        $loadLimit = max(1, (int) ($args['load-limit'] ?? 100));
        $delay = max(1, (int) ($args['delay'] ?? 5));
        $newOnly = isset($args['new-only']);
        $bell = isset($args['bell']);

        $cms->init();

        $isFirstQuery = true;
        $lastSeenEntryId = null;

        $query = $queryFactory->createFromArgs([
            'level' => $args['level'] ?? null,
            'category' => $args['category'] ?? null,
            'keyword' => $args['keyword'] ?? null,
            'method' => $args['method'] ?? null,
            'url-keyword' => $args['url-keyword'] ?? null,
            'ip' => $args['ip'] ?? null,
            'user-id' => $args['user-id'] ?? null,
        ]);

        $query->desc = true;

        while (true) {
            $query->limit = $isFirstQuery ? $limit : $loadLimit;
            $entries = $this->getEntriesSince($query, $lastSeenEntryId);

            if ($newOnly && $isFirstQuery && !empty($entries)) {
                // skip initial entries if --new-only is set
                end($entries);
                $lastSeenEntryId = current($entries)->id;
            } elseif (!empty($entries)) {
                // output entries
                if ($bell) {
                    fwrite(STDERR, "\x07");
                }

                foreach ($entries as $entry) {
                    $this->output->write($formatter->logEntry($entry));
                    $lastSeenEntryId = $entry->id;
                }
            }

            $isFirstQuery = false;

            sleep($delay);
        }

        return 0;
    }

    /**
     * @return LogEntry[]
     */
    private function getEntriesSince(LogQuery $query, $lastSeenEntryId): array
    {
        $query->offset = 0;
        $lastEntryFound = false;
        $entries = [];

        do {
            foreach (Logger::search($query) as $entry) {
                if ($entry->id === $lastSeenEntryId) {
                    $lastEntryFound = true;
                    break;
                }

                array_unshift($entries, $entry);
            }

            $query->offset += $query->limit;
        } while (!$lastEntryFound && $lastSeenEntryId !== null);

        return $entries;
    }
}
