<?php

declare(strict_types=1);

namespace Mezzio\Twig\Exception;

use DomainException;
use Psr\Container\ContainerExceptionInterface;

class InvalidRuntimeLoaderException extends DomainException implements
    ContainerExceptionInterface,
    ExceptionInterface
{
}
