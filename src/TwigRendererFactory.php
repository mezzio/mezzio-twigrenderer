<?php

declare(strict_types=1);

namespace Mezzio\Twig;

use ArrayObject;
use Psr\Container\ContainerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;

use function array_replace_recursive;
use function get_debug_type;
use function is_array;
use function sprintf;

/**
 * Create and return a Twig template instance.
 */
class TwigRendererFactory
{
    /**
     * @throws LoaderError
     * @throws Exception\InvalidConfigException If a non-array, non-ArrayObject $config is received.
     */
    public function __invoke(ContainerInterface $container): TwigRenderer
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = self::mergeConfig($config);
        /** @var Environment $environment */
        $environment = $container->get(Environment::class);

        return new TwigRenderer($environment, $config['extension'] ?? 'html.twig');
    }

    /**
     * Merge mezzio templating config with twig config.
     *
     * Pulls the `templates` and `twig` top-level keys from the configuration,
     * if present, and then returns the merged result, with those from the twig
     * array having precedence.
     *
     * @param array|ArrayObject $config
     * @throws Exception\InvalidConfigException If a non-array, non-ArrayObject $config is received.
     */
    public static function mergeConfig($config): array
    {
        $config = $config instanceof ArrayObject ? $config->getArrayCopy() : $config;

        if (! is_array($config)) {
            throw new Exception\InvalidConfigException(sprintf(
                'Config service MUST be an array or ArrayObject; received %s',
                get_debug_type($config)
            ));
        }

        $mezzioConfig = isset($config['templates']) && is_array($config['templates'])
            ? $config['templates']
            : [];
        $twigConfig   = isset($config['twig']) && is_array($config['twig'])
            ? $config['twig']
            : [];

        return array_replace_recursive($mezzioConfig, $twigConfig);
    }
}
