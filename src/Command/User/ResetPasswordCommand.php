<?php declare(strict_types=1);

namespace SunlightConsole\Command\User;

use SunlightConsole\Command;
use SunlightConsole\Argument\ArgumentDefinition;
use Sunlight\Database\Database as DB;
use Sunlight\Util\Password;
use Sunlight\Util\StringGenerator;
use SunlightConsole\Util\CmsFacade;

class ResetPasswordCommand extends Command
{
    function getHelp(): string
    {
        return 'reset password for the given user';
    }

    protected function defineArguments(): array
    {
        return [
            ArgumentDefinition::argument(0, 'user', 'username, display name or e-mail', true),
            ArgumentDefinition::argument(1, 'password', 'specify a password (otherwise use randomly generated)'),
        ];
    }

    function run(CmsFacade $cms, array $args): int
    {
        $cms->init();

        if (strpos($args['user'], '@') !== false) {
            $cond = 'email=' . DB::val($args['user']);
        } else {
            $cond = 'username=' . DB::val($args['user']) . ' OR publicname=' . DB::val($args['user']);
        }

        $user = DB::queryRow('SELECT id,username FROM ' . DB::table('user') . ' WHERE ' . $cond);

        $user !== false
            or $this->output->fail('Could not find user "%s"', $args['user']);

        $newPassword = $args['password'] ?? StringGenerator::generateString(14);

        DB::update('user', $cond, ['password' => Password::create($newPassword)->build()]);

        $this->output->write('Changed password for user %d (%s)', $user['id'], $user['username']);
        $this->output->write('New password: %s', $newPassword);

        return 0;
    }
}
