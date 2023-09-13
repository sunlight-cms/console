<?php declare(strict_types=1);

namespace SunlightConsole\DependencyInjection;

use Psr\Container\ContainerExceptionInterface;

class ContainerException extends \RuntimeException implements ContainerExceptionInterface
{
}
