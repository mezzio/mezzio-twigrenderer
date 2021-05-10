<?php

declare(strict_types=1);

namespace Mezzio\Twig;

use Mezzio\Template\TemplateRendererInterface;
use Twig\Environment;
use Twig_Environment;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                TemplateRendererInterface::class => TwigRenderer::class,
                Twig_Environment::class          => Environment::class,

                // Legacy Zend Framework aliases
                \Zend\Expressive\Template\TemplateRendererInterface::class => TemplateRendererInterface::class,
                \Zend\Expressive\Twig\Twig_Environment::class              => Twig_Environment::class,
                \Zend\Expressive\Twig\TwigExtension::class                 => TwigExtension::class,
                \Zend\Expressive\Twig\TwigRenderer::class                  => TwigRenderer::class,
            ],
            'factories' => [
                Environment::class   => TwigEnvironmentFactory::class,
                TwigExtension::class => TwigExtensionFactory::class,
                TwigRenderer::class  => TwigRendererFactory::class,
            ],
        ];
    }

    public function getTemplates(): array
    {
        return [
            'extension' => 'html.twig',
            'paths'     => [],
        ];
    }
}
