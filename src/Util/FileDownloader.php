<?php declare(strict_types=1);

namespace SunlightConsole\Util;

use SunlightConsole\Output;

class FileDownloader
{
    /** @var Output */
    private $output;

    function __construct(Output $output)
    {
        $this->output = $output;
    }

    function download(string $url, string $targetPath): void
    {
        $this->output->log('Downloading "%s"', $url);

        $targetHandle = fopen($targetPath, 'w');
        $urlHandle = fopen($url, 'r');

        if (stream_copy_to_stream($urlHandle, $targetHandle) === false) {
            throw new \Exception(sprintf('Could not download file from "%s"', $url));
        }
    }
}
