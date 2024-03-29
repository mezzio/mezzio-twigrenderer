<?php

declare(strict_types=1);

namespace MezzioTest\Twig;

use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Twig\TwigExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;

class TwigExtensionFunctionsRenderTest extends TestCase
{
    /** @var string[] */
    protected $templates;
    /** @var MockObject&LoaderInterface */
    protected $twigLoader;
    /** @var MockObject&ServerUrlHelper */
    protected $serverUrlHelper;
    /** @var MockObject&UrlHelper */
    protected $urlHelper;

    protected function setUp(): void
    {
        $this->twigLoader      = $this->createMock(LoaderInterface::class);
        $this->serverUrlHelper = $this->createMock(ServerUrlHelper::class);
        $this->urlHelper       = $this->createMock(UrlHelper::class);

        $this->templates = [
            'template' => "{{ path('route') }}",
        ];
    }

    public function testEnvironmentCreation(): void
    {
        $twig = $this->getTwigEnvironment();

        $this->assertInstanceOf(Environment::class, $twig);
    }

    protected function getTwigEnvironment(string $assetsUrl = '', string $assetsVersion = ''): Environment
    {
        $loader = new ArrayLoader($this->templates);

        $twig = new Environment($loader, ['debug' => true, 'cache' => false]);
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
        $this->templates = [
            'template' => $template,
        ];
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
     * @return array<string, array<mixed>>
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
        $this->templates = [
            'template' => $template,
        ];

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
     * @return array<string, array<mixed>>
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
        $this->templates = [
            'template' => "{{ absolute_url('path/to/something') }}",
        ];

        $this->serverUrlHelper->expects(self::once())->method('generate')->with('path/to/something')->willReturn(
            'HOST/PATH'
        );
        $twig = $this->getTwigEnvironment();

        $this->assertSame('HOST/PATH', $twig->render('template'));
    }

    public function testAssetFunction(): void
    {
        $this->templates = [
            'template' => "{{ asset('path/to/asset/name.ext') }}",
        ];

        $twig = $this->getTwigEnvironment();

        $this->assertSame('path/to/asset/name.ext', $twig->render('template'));
    }

    public function testVersionedAssetFunction(): void
    {
        $this->templates = [
            'template' => "{{ asset('path/to/asset/name.ext', version=3) }}",
        ];

        $twig = $this->getTwigEnvironment();

        $this->assertSame('path/to/asset/name.ext?v=3', $twig->render('template'));
    }
}
