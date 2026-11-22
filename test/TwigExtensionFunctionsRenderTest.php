<?php

declare(strict_types=1);

namespace MezzioTest\Twig;

use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Twig\TwigExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class TwigExtensionFunctionsRenderTest extends TestCase
{
    /** @var array<string, string> */
    private array $templates;
    private (ServerUrlHelper&MockObject)|(ServerUrlHelper&Stub)|null $serverUrlHelper = null;
    private (UrlHelper&MockObject)|(UrlHelper&Stub)|null $urlHelper                   = null;

    protected function setUp(): void
    {
        $this->templates = [
            'template' => "{{ path('route') }}",
        ];
    }

    protected function getTwigEnvironment(string $assetsUrl = '', string $assetsVersion = ''): Environment
    {
        $loader = new ArrayLoader($this->templates);
        $twig   = new Environment($loader, ['debug' => true, 'cache' => false]);
        self::assertNotNull($this->serverUrlHelper);
        self::assertNotNull($this->urlHelper);
        $twig->addExtension(
            new TwigExtension(
                $this->serverUrlHelper,
                $this->urlHelper,
                $assetsUrl,
                $assetsVersion
            )
        );

        return $twig;
    }

    #[DataProvider('renderPathProvider')]
    public function testPathFunction(
        string $template,
        string $route,
        array $routeParams,
        array $queryParams,
        ?string $fragment,
        array $options
    ): void {
        $this->templates       = [
            'template' => $template,
        ];
        $this->serverUrlHelper = $this->createStub(ServerUrlHelper::class);
        $this->urlHelper       = $this->createMock(UrlHelper::class);
        $this->urlHelper->expects(self::once())->method('generate')->with(
            $route,
            $routeParams,
            $queryParams,
            $fragment,
            $options
        )->willReturn('PATH');
        $twig = $this->getTwigEnvironment();

        $this->assertSame('PATH', $twig->render('template'));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: array, 3: array, 4: null|string, 5: array}>
     */
    public static function renderPathProvider(): array
    {
        return [
            'path'                   => [
                "{{ path('route', {'foo': 'bar'}) }}",
                'route',
                ['foo' => 'bar'],
                [],
                null,
                [],
            ],
            'path-query'             => [
                "{{ path('path-query', {'id': '3'}, {'foo': 'bar'}) }}",
                'path-query',
                ['id' => 3],
                ['foo' => 'bar'],
                null,
                [],
            ],
            'path-query-fragment'    => [
                "{{ path('path-query-fragment', {'foo': 'bar'}, {'qux': 'quux'}, 'corge') }}",
                'path-query-fragment',
                ['foo' => 'bar'],
                ['qux' => 'quux'],
                'corge',
                [],
            ],
            'path-reuse-result'      => [
                "{{ path('path-query-fragment', {}, {}, null, {'reuse_result_params': true}) }}",
                'path-query-fragment',
                [],
                [],
                null,
                ['reuse_result_params' => true],
            ],
            'path-dont-reuse-result' => [
                "{{ path('path-query-fragment', {}, {}, null, {'reuse_result_params': false}) }}",
                'path-query-fragment',
                [],
                [],
                null,
                ['reuse_result_params' => false],
            ],
        ];
    }

    #[DataProvider('renderUrlProvider')]
    public function testUrlFunction(
        string $template,
        string $route,
        array $routeParams,
        array $queryParams,
        ?string $fragment,
        array $options
    ): void {
        $this->templates       = [
            'template' => $template,
        ];
        $this->serverUrlHelper = $this->createMock(ServerUrlHelper::class);
        $this->urlHelper       = $this->createMock(UrlHelper::class);

        $this->urlHelper->expects(self::once())->method('generate')->with(
            $route,
            $routeParams,
            $queryParams,
            $fragment,
            $options
        )->willReturn('PATH');
        $this->serverUrlHelper->expects(self::once())->method('generate')->with('PATH')->willReturn('HOST/PATH');
        $twig = $this->getTwigEnvironment();

        $this->assertSame('HOST/PATH', $twig->render('template'));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: array, 3: array, 4: string|null, 5: array}>
     */
    public static function renderUrlProvider(): array
    {
        return [
            'path'                   => [
                "{{ url('route', {'foo': 'bar'}) }}",
                'route',
                ['foo' => 'bar'],
                [],
                null,
                [],
            ],
            'path-query'             => [
                "{{ url('path-query', {'id': '3'}, {'foo': 'bar'}) }}",
                'path-query',
                ['id' => 3],
                ['foo' => 'bar'],
                null,
                [],
            ],
            'path-query-fragment'    => [
                "{{ url('path-query-fragment', {'foo': 'bar'}, {'qux': 'quux'}, 'corge') }}",
                'path-query-fragment',
                ['foo' => 'bar'],
                ['qux' => 'quux'],
                'corge',
                [],
            ],
            'path-reuse-result'      => [
                "{{ url('path-query-fragment', {}, {}, null, {'reuse_result_params': true}) }}",
                'path-query-fragment',
                [],
                [],
                null,
                ['reuse_result_params' => true],
            ],
            'path-dont-reuse-result' => [
                "{{ url('path-query-fragment', {}, {}, null, {'reuse_result_params': false}) }}",
                'path-query-fragment',
                [],
                [],
                null,
                ['reuse_result_params' => false],
            ],
        ];
    }

    public function testAbsoluteUrlFunction(): void
    {
        $this->templates       = [
            'template' => "{{ absolute_url('path/to/something') }}",
        ];
        $this->serverUrlHelper = $this->createMock(ServerUrlHelper::class);
        $this->urlHelper       = $this->createStub(UrlHelper::class);

        $this->serverUrlHelper->expects(self::once())->method('generate')->with('path/to/something')->willReturn(
            'HOST/PATH'
        );
        $twig = $this->getTwigEnvironment();

        $this->assertSame('HOST/PATH', $twig->render('template'));
    }

    public function testAssetFunction(): void
    {
        $this->serverUrlHelper = $this->createStub(ServerUrlHelper::class);
        $this->urlHelper       = $this->createStub(UrlHelper::class);
        $this->templates       = [
            'template' => "{{ asset('path/to/asset/name.ext') }}",
        ];

        $twig = $this->getTwigEnvironment();

        $this->assertSame('path/to/asset/name.ext', $twig->render('template'));
    }

    public function testVersionedAssetFunction(): void
    {
        $this->serverUrlHelper = $this->createStub(ServerUrlHelper::class);
        $this->urlHelper       = $this->createStub(UrlHelper::class);
        $this->templates       = [
            'template' => "{{ asset('path/to/asset/name.ext', version=3) }}",
        ];

        $twig = $this->getTwigEnvironment();

        $this->assertSame('path/to/asset/name.ext?v=3', $twig->render('template'));
    }
}
