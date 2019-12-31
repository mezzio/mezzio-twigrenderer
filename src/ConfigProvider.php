<?php

/**
 * @see       https://github.com/mezzio/mezzio-twigrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-twigrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-twigrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Twig;

use Mezzio\Template\TemplateRendererInterface;
use Twig_Environment;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates' => $this->getTemplates(),
        ];
    }

    public function getDependencies() : array
    {
        return [
            'aliases' => [
                TemplateRendererInterface::class => TwigRenderer::class,

                // Legacy Zend Framework aliases
                \Zend\Expressive\Template\TemplateRendererInterface::class => TemplateRendererInterface::class,
                \Zend\Expressive\Twig\Twig_Environment::class => Twig_Environment::class,
                \Zend\Expressive\Twig\TwigRenderer::class => TwigRenderer::class,
            ],
            'factories' => [
                Twig_Environment::class => TwigEnvironmentFactory::class,
                TwigRenderer::class => TwigRendererFactory::class,
            ],
        ];
    }

    public function getTemplates() : array
    {
        return [
            'extension' => 'html.twig',
            'paths' => [],
        ];
    }
}
