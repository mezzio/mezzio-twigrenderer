<?php

namespace MezzioTest\Twig\TestAsset\Extension;

class BarTwigExtension extends \Twig_Extension
{
    public function getName()
    {
        return 'bar-twig-extension';
    }
}
