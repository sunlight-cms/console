<?php declare(strict_types=1);

namespace SunlightConsole\DependencyInjection;

use Psr\Container\NotFoundExceptionInterface;

class ServiceNotFoundException extends \OutOfBoundsException implements NotFoundExceptionInterface
{
}
