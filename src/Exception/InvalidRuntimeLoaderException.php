<?php

/**
 * @see       https://github.com/mezzio/mezzio-twigrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-twigrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-twigrenderer/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Twig\Exception;

use DomainException;
use Psr\Container\ContainerExceptionInterface;

class InvalidRuntimeLoaderException extends DomainException implements
    ContainerExceptionInterface,
    ExceptionInterface
{
}
