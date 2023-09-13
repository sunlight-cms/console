<?php declare(strict_types=1);

namespace SunlightConsole\Util;

use Kuria\Debug\Dumper;
use Sunlight\Log\LogEntry;
use Sunlight\Logger;
use Sunlight\Message;

class Formatter
{
    function message(Message $message): string
    {
        switch ($message->getType()) {
            case Message::OK: $symbol = '🟢'; break;
            case Message::WARNING: $symbol = '🟡'; break;
            case Message::ERROR: $symbol = '🔴'; break;
            default: $symbol = '⚪'; break;
        }

        return sprintf(
            '%s %s',
            $symbol,
            $message->isHtml() ? $this->htmlAsPlaintext($message->getMessage()) : $message->getMessage()
        );
    }

    function logEntry(LogEntry $entry): string
    {
        switch ($entry->level) {
            case Logger::EMERGENCY:
            case Logger::ALERT:
            case Logger::CRITICAL:
            case Logger::ERROR:
                $symbol = '🔴';
                break;
            case Logger::WARNING:
                $symbol = '🟡';
                break;
            case Logger::NOTICE:
                $symbol = '🔵';
                break;
            case Logger::INFO:
                $symbol = '🔵';
                break;
            default:
                $symbol = '🟤';
                break;
        }

        return sprintf(
            '[%s] %s %s: %s',
            $entry->getDateTime()->format('Y-m-d H:i:s.u'),
            $symbol,
            $entry->category,
            $entry->message
        );
    }

    function htmlAsPlaintext(string $html): string
    {
        return html_entity_decode(strip_tags($html));
    }

    function dump($value, int $maxLevel = 10): string
    {
        return Dumper::dump($value, $maxLevel, 255);
    }
}
