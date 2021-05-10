<?php

declare(strict_types=1);

namespace Mezzio\Twig;

use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Twig\Exception\InvalidConfigException;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Helper\ServerUrlHelper as ZfServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper as ZfUrlHelper;

use function sprintf;

class TwigExtensionFactory
{
    public function __invoke(ContainerInterface $container): TwigExtension
    {
        $serverUrlHelper = $container->has(ServerUrlHelper::class)
            ? ServerUrlHelper::class
            : ($container->has(ZfServerUrlHelper::class) ? ZfServerUrlHelper::class : null);
        if ($serverUrlHelper === null) {
            throw new InvalidConfigException(sprintf('Missing required `%s` dependency.', ServerUrlHelper::class));
        }

        $urlHelper = $container->has(UrlHelper::class)
            ? UrlHelper::class
            : ($container->has(ZfUrlHelper::class) ? ZfUrlHelper::class : null);
        if ($urlHelper === null) {
            throw new InvalidConfigException(sprintf('Missing required `%s` dependency.', UrlHelper::class));
        }

        $config = $container->has('config') ? $container->get('config') : [];
        $config = TwigRendererFactory::mergeConfig($config);

        return new TwigExtension(
            $container->get($serverUrlHelper),
            $container->get($urlHelper),
            $config['assets_url'] ?? '',
            $config['assets_version'] ?? '',
            $config['globals'] ?? []
        );
    }
}
