<?php declare(strict_types=1);

namespace SunlightConsole\Command\Config;

use SunlightConsole\Command;
use SunlightConsole\Argument\ArgumentDefinition;
use Sunlight\Util\ConfigurationFile;
use Sunlight\Util\StringGenerator;

class SetCommand extends Command
{
    function getHelp(): string
    {
        return 'modify an option in config.php';
    }

    protected function defineArguments(): array
    {
        return [
            ArgumentDefinition::option('debug', 'debug mode 1/0'),
            ArgumentDefinition::option('db.server', 'database server'),
            ArgumentDefinition::option('db.port', 'database port'),
            ArgumentDefinition::option('db.user', 'database user'),
            ArgumentDefinition::option('db.password', 'database password'),
            ArgumentDefinition::option('db.name', 'database name'),
            ArgumentDefinition::option('db.prefix', 'table name prefix'),
            ArgumentDefinition::flagOrOption('secret', 'secret hash (pass no value to generate random)'),
            ArgumentDefinition::option('fallback_lang', 'fallback language plugin name'),
            ArgumentDefinition::option('trusted_proxies', 'comma-separated trusted proxy IPs (or CIDR)'),
            ArgumentDefinition::option('trusted_proxy_headers', 'forwarded/x-forwarded/all or null'),
            ArgumentDefinition::option('cache', 'cache enabled 1/0'),
            ArgumentDefinition::option('timezone', 'timezone name'),
            ArgumentDefinition::option('safe_mode', 'safe mode 1/0'),
        ];
    }

    function run(array $args): int
    {
        $this->utils->ensureCmsClassesAvailable();

        $configPath = $this->cli->getProjectRoot() . '/config.php';

        if (!is_file($configPath)) {
            $this->output->log('The config.php file does not exist');

            return 1;
        }

        $config = new ConfigurationFile($configPath);

        foreach ($args as $key => $value) {
            switch ($key) {
                // bool
                case 'debug':
                case 'cache':
                case 'safe_mode':
                    $config[$key] = !empty($value) && $value !== 'false';
                    break;

                // ?int
                case 'db.port':
                    $config[$key] = !empty($value) && $value !== 'null' ? (int) $value : null;
                    break;

                // ?string
                case 'timezone':
                    $config[$key] = !empty($value) && $value !== 'null' ? $value : null;
                    break;

                // secret
                case 'secret':
                    $config[$key] = !empty($value) ? $value : StringGenerator::generateString(64);
                    break;

                // trusted_proxies
                case 'trusted_proxies':
                    $value = preg_split('{\s*+,\s*+}', $value);
                    $config[$key] = !empty($value) ? $value : null;
                    break;

                // trusted_proxy_headers
                case 'trusted_proxy_headers':
                    in_array($value, ['forwarded', 'x-forwarded', 'all', 'null', ''], true)
                        or $this->cli->fail('Invalid trusted_proxy_headers value');

                    $config[$key] = $value !== 'null' && $value !== '' ? $value : null;

                    break;

                // string
                default:
                    $config[$key] = $value;
            }
        }

        $config->save();
        $this->output->log('Updated config.php');

        return 0;
    }
}
