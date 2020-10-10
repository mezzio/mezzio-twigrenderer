<?php

/**
 * @see       https://github.com/mezzio/mezzio-twigrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-twigrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-twigrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Twig;

use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Twig\Exception\InvalidConfigException;
use Psr\Container\ContainerInterface;

use function sprintf;

class TwigExtensionFactory
{
    public function __invoke(ContainerInterface $container): TwigExtension
    {
        if (
            ! $container->has(ServerUrlHelper::class)
            && ! $container->has(\Zend\Expressive\Helper\ServerUrlHelper::class)
        ) {
            throw new InvalidConfigException(sprintf(
                'Missing required `%s` dependency.',
                ServerUrlHelper::class
            ));
        }

        if (
            ! $container->has(UrlHelper::class)
            && ! $container->has(\Zend\Expressive\Helper\UrlHelper::class)
        ) {
            throw new InvalidConfigException(sprintf(
                'Missing required `%s` dependency.',
                UrlHelper::class
            ));
        }

        $config = $container->has('config') ? $container->get('config') : [];
        $config = TwigRendererFactory::mergeConfig($config);

        return new TwigExtension(
            $container->has(ServerUrlHelper::class)
                ? $container->get(ServerUrlHelper::class)
                : $container->get(\Zend\Expressive\Helper\ServerUrlHelper::class),
            $container->has(UrlHelper::class)
                ? $container->get(UrlHelper::class)
                : $container->get(\Zend\Expressive\Helper\UrlHelper::class),
            $config['assets_url'] ?? '',
            $config['assets_version'] ?? '',
            $config['globals'] ?? []
        );
    }
}
