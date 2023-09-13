<?php declare(strict_types=1);

namespace SunlightConsole\Command\Log;

use Sunlight\Log\LogQuery;
use Sunlight\Logger;
use SunlightConsole\Output;

class LogQueryFactory
{
    /** @var Output */
    private $output;

    function __construct(Output $output)
    {
        $this->output = $output;
    }

    /**
     * @param array<string, string|null> $args
     */
    function createFromArgs(array $args): LogQuery
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
                    or $this->output->fail('Invalid --%s, a number is required', $option);

                $query->{$prop} = (int) $args[$option];
            }
        }

        // level
        if (isset($args['level'])) {
            if (ctype_digit($args['level'])) {
                $maxLevel = (int) $args['level'];

                isset(Logger::LEVEL_NAMES[$maxLevel])
                    or $this->output->fail('Invalid --level, valid levels are %d to %d', Logger::EMERGENCY, Logger::DEBUG);
            } else {
                $maxLevel = array_search($args['level'], Logger::LEVEL_NAMES, true);

                $maxLevel !== false
                    or $this->output->fail('Invalid --level, valid names are: %s', implode(', ', Logger::LEVEL_NAMES));
            }

            $query->maxLevel = $maxLevel;
        }

        // since
        if (isset($args['since'])) {
            $since = strtotime($args['since']);

            $since !== false
                or $this->output->fail('Could not convert --since value to a timestamp');

            $query->since = $since;
        }

        // until
        if (isset($args['until'])) {
            $until = strtotime($args['until']);

            $until !== false
                or $this->output->fail('Could not convert --until value to a timestamp');

            $query->until = $until;
        }

        // desc
        $query->desc = isset($args['desc']);

        return $query;
    }
}
