<?php

declare(strict_types=1);

namespace Mezzio\Twig\Exception;

use DomainException;
use Psr\Container\ContainerExceptionInterface;

/** @final */
class InvalidExtensionException extends DomainException implements
    ContainerExceptionInterface,
    ExceptionInterface
{
}
