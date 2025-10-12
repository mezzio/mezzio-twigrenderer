<?php

declare(strict_types=1);

namespace Mezzio\Twig\Exception;

use DomainException;
use Psr\Container\ContainerExceptionInterface;

/** @final */
class InvalidConfigException extends DomainException implements
    ContainerExceptionInterface,
    ExceptionInterface
{
}
