<?php

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
