<?php

declare(strict_types=1);

namespace MezzioTest\Twig;

use Mezzio\Template\TemplatePath;
use Mezzio\Twig\Exception\InvalidConfigException;
use Mezzio\Twig\TwigEnvironmentFactory;
use Mezzio\Twig\TwigExtension;
use Mezzio\Twig\TwigRenderer;
use Mezzio\Twig\TwigRendererFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Twig\Environment;

use function restore_error_handler;
use function set_error_handler;
use function sprintf;

use const E_USER_DEPRECATED;

class TwigRendererFactoryTest extends TestCase
{
    /** @var MockObject&ContainerInterface */
    private ContainerInterface $container;

    /** @var callable */
    private $errorHandler;

    protected function setUp(): void
    {
        $this->restoreErrorHandler();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    protected function tearDown(): void
    {
        $this->restoreErrorHandler();
    }

    /**
     * @return mixed
     */
    public function fetchTwigEnvironment(TwigRenderer $twig)
    {
        $r = new ReflectionProperty($twig, 'template');
        $r->setAccessible(true);

        return $r->getValue($twig);
    }

    public function testCallingFactoryWithNoConfigReturnsTwigInstance(): TwigRenderer
    {
        $this->container->expects(self::atLeastOnce())->method('has')->willReturnMap([
            ['config', false],
            [Environment::class, true],
            [TwigExtension::class, false],
        ]);
        $environmentFactory = new TwigEnvironmentFactory();
        $container          = $this->container;
        $this->container->expects(self::atLeastOnce())->method('get')->with(Environment::class)->willReturnCallback(
            static fn() => $environmentFactory($container)
        );

        $factory = new TwigRendererFactory();
        $twig    = $factory($this->container);
        $this->assertInstanceOf(TwigRenderer::class, $twig);

        return $twig;
    }

    /**
     * @depends testCallingFactoryWithNoConfigReturnsTwigInstance
     */
    public function testUnconfiguredTwigInstanceContainsNoPaths(TwigRenderer $twig): void
    {
        $paths = $twig->getPaths();
        $this->assertIsArray($paths);
        $this->assertEmpty($paths);
    }

    public function testConfiguresTemplateSuffix(): void
    {
        $config = [
            'templates' => [
                'extension' => 'tpl',
            ],
        ];
        $this->container->expects(self::atLeastOnce())->method('has')->willReturnMap([
            ['config', true],
            [Environment::class, true],
            [TwigExtension::class, false],
        ]);

        $environmentFactory = new TwigEnvironmentFactory();
        $container          = $this->container;
        $this->container->expects(self::atLeastOnce())->method('get')->willReturnCallback(
            static function (string $id) use ($config, $environmentFactory, $container) {
                switch ($id) {
                    case 'config':
                        return $config;
                    case Environment::class:
                        return $environmentFactory($container);
                }
                return null;
            }
        );
        $factory = new TwigRendererFactory();
        $twig    = $factory($this->container);

        $this->assertEquals('test.tpl', $twig->normalizeTemplate('test'));
    }

    public function testUsesGlobalsConfigurationWhenAddingTwigExtension(): void
    {
        $config = [
            'templates' => [
                'paths' => $this->getConfigurationPaths(),
            ],
        ];
        $this->container->expects(self::atLeastOnce())->method('has')->willReturnMap([
            ['config', true],
            [Environment::class, true],
            [TwigExtension::class, false],
        ]);

        $environmentFactory = new TwigEnvironmentFactory();
        $container          = $this->container;
        $this->container->expects(self::atLeastOnce())->method('get')->willReturnCallback(
            static function (string $id) use ($config, $environmentFactory, $container) {
                switch ($id) {
                    case 'config':
                        return $config;
                    case Environment::class:
                        return $environmentFactory($container);
                }
                return null;
            }
        );
        $factory = new TwigRendererFactory();
        $twig    = $factory($this->container);

        $paths = $twig->getPaths();
        $this->assertPathsHasNamespace('foo', $paths);
        $this->assertPathsHasNamespace('bar', $paths);
        $this->assertPathsHasNamespace(null, $paths);

        $this->assertPathNamespaceCount(1, 'foo', $paths);
        $this->assertPathNamespaceCount(2, 'bar', $paths);
        $this->assertPathNamespaceCount(3, null, $paths);

        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/bar', 'foo', $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/baz', 'bar', $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/bat', 'bar', $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/one', null, $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/two', null, $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/three', null, $paths);
    }

    /**
     * @return array<string|int, string|string[]>
     */
    public function getConfigurationPaths(): array
    {
        return [
            'foo' => __DIR__ . '/TestAsset/bar',
            1     => __DIR__ . '/TestAsset/one',
            'bar' => [
                __DIR__ . '/TestAsset/baz',
                __DIR__ . '/TestAsset/bat',
            ],
            0     => [
                __DIR__ . '/TestAsset/two',
                __DIR__ . '/TestAsset/three',
            ],
        ];
    }

    public function assertPathsHasNamespace(?string $namespace, array $paths, ?string $message = null): void
    {
        $message = $message ?: sprintf('Paths do not contain namespace %s', $namespace ?: 'null');

        $found = false;
        foreach ($paths as $path) {
            $this->assertInstanceOf(TemplatePath::class, $path, 'Non-TemplatePath found in paths list');
            if ($path->getNamespace() === $namespace) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, $message);
    }

    public function assertPathNamespaceCount(
        int $expected,
        ?string $namespace,
        array $paths,
        ?string $message = null
    ): void {
        $message = $message ?: sprintf('Did not find %d paths with namespace %s', $expected, $namespace ?: 'null');

        $count = 0;
        foreach ($paths as $path) {
            $this->assertInstanceOf(TemplatePath::class, $path, 'Non-TemplatePath found in paths list');
            if ($path->getNamespace() === $namespace) {
                $count += 1;
            }
        }
        $this->assertSame($expected, $count, $message);
    }

    public function assertPathNamespaceContains(
        string $expected,
        ?string $namespace,
        array $paths,
        ?string $message = null
    ): void {
        $message = $message ?: sprintf('Did not find path %s in namespace %s', $expected, $namespace ?: null);

        $found = [];
        foreach ($paths as $path) {
            $this->assertInstanceOf(TemplatePath::class, $path, 'Non-TemplatePath found in paths list');
            if ($path->getNamespace() === $namespace) {
                $found[] = $path->getPath();
            }
        }
        $this->assertContains($expected, $found, $message);
    }

    public function testCallingFactoryWithoutTwigEnvironmentServiceEmitsDeprecationNotice(): void
    {
        $this->container->expects(self::exactly(4))->method('has')->willReturn(false);

        $factory = new TwigRendererFactory();

        $this->errorHandler = set_error_handler(
            function ($errno, $errstr): bool {
                $this->assertStringContainsString(Environment::class, $errstr);
                return true;
            },
            E_USER_DEPRECATED
        );

        $twig = $factory($this->container);
        $this->assertInstanceOf(TwigRenderer::class, $twig);
    }

    public function testMergeConfigRaisesExceptionForInvalidConfig(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Config service MUST be an array or ArrayObject; received string');

        /** @noinspection PhpParamsInspection */
        TwigRendererFactory::mergeConfig('foo');
    }

    public function testMergesConfigCorrectly(): void
    {
        $config = [
            'templates' => [
                'extension' => 'file extension used by templates; defaults to html.twig',
                'paths'     => [],
            ],
            'twig'      => [
                'cache_dir'       => 'path to cached templates',
                'assets_url'      => 'base URL for assets',
                'assets_version'  => 'base version for assets',
                'extensions'      => [],
                'runtime_loaders' => [],
                'globals'         => ['ga_tracking' => 'UA-XXXXX-X'],
                'timezone'        => 'default timezone identifier, e.g.: America/New_York',
            ],
        ];

        $mergedConfig = TwigRendererFactory::mergeConfig($config);

        $this->assertArrayHasKey('extension', $mergedConfig);
        $this->assertArrayHasKey('paths', $mergedConfig);
        $this->assertArrayHasKey('cache_dir', $mergedConfig);
        $this->assertArrayHasKey('assets_version', $mergedConfig);
        $this->assertArrayHasKey('runtime_loaders', $mergedConfig);
        $this->assertArrayHasKey('globals', $mergedConfig);
        $this->assertArrayHasKey('timezone', $mergedConfig);
    }

    public function restoreErrorHandler(): void
    {
        if ($this->errorHandler) {
            restore_error_handler();
            $this->errorHandler = null;
        }
    }
}
