<?php

declare(strict_types=1);

namespace MezzioTest\Twig\TestAsset\Extension;

use Twig\Extension\AbstractExtension;

class FooTwigExtension extends AbstractExtension
{
    public function getName(): string
    {
        return 'foo-twig-extension';
    }
}
