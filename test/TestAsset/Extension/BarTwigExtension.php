<?php

/**
 * @see       https://github.com/mezzio/mezzio-twigrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-twigrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-twigrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Twig\TestAsset\Extension;

use Twig\Extension\AbstractExtension;

class BarTwigExtension extends AbstractExtension
{
    public function getName(): string
    {
        return 'bar-twig-extension';
    }
}
