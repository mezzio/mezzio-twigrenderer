<?php

declare(strict_types=1);

namespace Mezzio\Twig;

use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

/**
 * Twig extension for rendering URLs and assets URLs from Mezzio.
 */
class TwigExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @param null|string|int $assetsVersion
     */
    public function __construct(
        private ServerUrlHelper $serverUrlHelper,
        private UrlHelper $urlHelper,
        private ?string $assetsUrl,
        private $assetsVersion,
        private array $globals = []
    ) {
    }

    public function getGlobals(): array
    {
        return $this->globals;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('absolute_url', [$this, 'renderUrlFromPath']),
            new TwigFunction('asset', [$this, 'renderAssetUrl']),
            new TwigFunction('path', [$this, 'renderUri']),
            new TwigFunction('url', [$this, 'renderUrl']),
        ];
    }

    /**
     * Render relative uri for a given named route
     *
     * Usage: {{ path('article_show', {'id': '3'}) }}
     * Generates: /article/3
     *
     * Usage: {{ path('article_show', {'id': '3'}, {'foo': 'bar'}, 'fragment') }}
     * Generates: /article/3?foo=bar#fragment
     *
     * @param array $routeParams
     * @param array $queryParams
     * @param array $options Can have the following keys:
     *                             - reuse_result_params (bool): indicates if the current
     *                             RouteResult parameters will be used, defaults to true
     */
    public function renderUri(
        ?string $route = null,
        array $routeParams = [],
        array $queryParams = [],
        ?string $fragmentIdentifier = null,
        array $options = []
    ): string {
        return $this->urlHelper->generate($route, $routeParams, $queryParams, $fragmentIdentifier, $options);
    }

    /**
     * Render absolute url for a given named route
     *
     * Usage: {{ url('article_show', {'slug': 'article.slug'}) }}
     * Generates: http://example.com/article/article.slug
     *
     * Usage: {{ url('article_show', {'id': '3'}, {'foo': 'bar'}, 'fragment') }}
     * Generates: http://example.com/article/3?foo=bar#fragment
     *
     * @param array $routeParams
     * @param array $queryParams
     * @param array $options Can have the following keys:
     *                             - reuse_result_params (bool): indicates if the current
     *                             RouteResult parameters will be used, defaults to true
     */
    public function renderUrl(
        ?string $route = null,
        array $routeParams = [],
        array $queryParams = [],
        ?string $fragmentIdentifier = null,
        array $options = []
    ): string {
        return $this->serverUrlHelper->generate(
            $this->renderUri($route, $routeParams, $queryParams, $fragmentIdentifier, $options)
        );
    }

    /**
     * Render absolute url from a path
     *
     * Usage: {{ absolute_url('path/to/something') }}
     * Generates: http://example.com/path/to/something
     */
    public function renderUrlFromPath(?string $path = null): string
    {
        return $this->serverUrlHelper->generate($path);
    }

    /**
     * Render asset url, optionally versioned
     *
     * Usage: {{ asset('path/to/asset/name.ext', version=3) }}
     * Generates: path/to/asset/name.ext?v=3
     */
    public function renderAssetUrl(string $path, ?string $version = null): string
    {
        $assetsVersion = $version !== null && $version !== '' ? $version : $this->assetsVersion;

        // One more time, in case $this->assetsVersion was null or an empty string
        $assetsVersion = $assetsVersion !== null && $assetsVersion !== '' ? '?v=' . $assetsVersion : '';

        return $this->assetsUrl . $path . $assetsVersion;
    }
}
