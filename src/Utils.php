<?php declare(strict_types=1);

namespace SunlightConsole;

use Kuria\Debug\Dumper;
use Sunlight\Core;
use Sunlight\Message;
use Sunlight\Plugin\Plugin;

class Utils
{
    function downloadFile(string $url, string $targetPath): void
    {
        $targetHandle = fopen($targetPath, 'w');
        $urlHandle = fopen($url, 'r');

        if (stream_copy_to_stream($urlHandle, $targetHandle) === false) {
            throw new \Exception(sprintf('Could not download file from "%s"', $url));
        }
    }

    function loadJsonFromFile(string $path): array
    {
        $data = @file_get_contents($path);

        if ($data === false) {
            throw new \Exception(sprintf('Could not load "%s"', $path));
        }

        $data = json_decode($data, true);

        if ($data === null) {
            throw new \Exception(sprintf('Could not parse JSON from "%s"', $path));
        }

        return $data;
    }

    function encodeJsonPretty($data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    function renderMessage(Message $message): string
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
            $message->isHtml() ? $this->htmlToPlaintext($message->getMessage()) : $message->getMessage()
        );
    }

    function htmlToPlaintext(string $html): string
    {
        return html_entity_decode(strip_tags($html));
    }

    function dump($value, int $maxLevel = 10): string
    {
        return Dumper::dump($value, $maxLevel, 255);
    }

    /**
     * @param string[] $strings
     */
    function getMaxStringLength(array $strings): int
    {
        if (empty($strings)) {
            return 0;
        }

        return max(array_map('strlen', $strings));
    }

    function ensureCmsClassesAvailable(): void
    {
        if (!class_exists(Core::class)) {
            throw new \Exception('CMS classes are not available');
        }
    }

    function initCms(string $projectRoot, array $options = []): void
    {
        $this->ensureCmsClassesAvailable();

        if (Core::isReady()) {
            return;
        }

        try {            
            // set class loader
            // (including autoload.php again just returns the existing autoloader instance)
            Core::$classLoader = require $projectRoot . '/vendor/autoload.php';

            // init core
            Core::init($options + ['session_enabled' => false, 'debug' => true]);
        } catch (\Throwable $e) {
            throw new \Exception(sprintf(
                'Could not initialize CMS: %s in %s:%d',
                 $e->getMessage(),
                 $e->getFile(),
                 $e->getLine()
            ), 0, $e);
        }
    }

    function findPlugin(string $name): ?Plugin
    {
        $plugins = Core::$pluginManager->getPlugins();

        return $plugins->get($name)
            ?? $plugins->getInactive($name)
            ?? $plugins->getExtend($name)
            ?? $plugins->getInactiveByName('extend', $name)
            ?? $plugins->getTemplate($name)
            ?? $plugins->getInactiveByName('template', $name)
            ?? $plugins->getLanguage($name)
            ?? $plugins->getInactiveByName('language', $name);
    }
}
