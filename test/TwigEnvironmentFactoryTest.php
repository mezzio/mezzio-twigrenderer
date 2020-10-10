<?php

/**
 * @see       https://github.com/mezzio/mezzio-twigrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-twigrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-twigrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Twig;

use DateTimeZone;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Twig\Exception\InvalidConfigException;
use Mezzio\Twig\Exception\InvalidExtensionException;
use Mezzio\Twig\Exception\InvalidRuntimeLoaderException;
use Mezzio\Twig\TwigEnvironmentFactory;
use Mezzio\Twig\TwigExtension;
use Mezzio\Twig\TwigExtensionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\CoreExtension;
use Twig\Extension\EscaperExtension;
use Twig\Extension\OptimizerExtension;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

use function is_string;

class TwigEnvironmentFactoryTest extends TestCase
{
    /**
     * @var ContainerInterface|MockObject
     */
    private $container;

    protected function setUp() : void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testCallingFactoryWithNoConfigReturnsTwigEnvironmentInstance()
    {
        $this->container
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['config', false],
                [TwigExtension::class, false],
                [\Zend\Expressive\Twig\TwigExtension::class, false],
                [ServerUrlHelper::class, false],
                [\Zend\Expressive\Helper\ServerUrlHelper::class, false],
                [UrlHelper::class, false],
                [\Zend\Expressive\Helper\UrlHelper::class, false],
            ]);

        $factory     = new TwigEnvironmentFactory();
        $environment = $factory($this->container);

        $this->assertInstanceOf(TwigEnvironment::class, $environment);

        return $environment;
    }

    public function testUsesDebugConfiguration()
    {
        $config = ['debug' => true];

        $this->container
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [TwigExtension::class, false],
                [\Zend\Expressive\Twig\TwigExtension::class, false],
                [ServerUrlHelper::class, false],
                [\Zend\Expressive\Helper\ServerUrlHelper::class, false],
                [UrlHelper::class, false],
                [\Zend\Expressive\Helper\UrlHelper::class, false],
            ]);

        $this->container
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['config', $config],
            ]);

        $factory     = new TwigEnvironmentFactory();
        $environment = $factory($this->container);

        $this->assertTrue($environment->isDebug());
        $this->assertFalse($environment->getCache());
        $this->assertTrue($environment->isStrictVariables());
        $this->assertTrue($environment->isAutoReload());
    }

    /**
     * @depends testCallingFactoryWithNoConfigReturnsTwigEnvironmentInstance
     *
     * @param TwigEnvironment $environment
     */
    public function testDebugDisabledSetsUpEnvironmentForProduction(TwigEnvironment $environment)
    {
        $this->assertFalse($environment->isDebug());
        $this->assertFalse($environment->isStrictVariables());
        $this->assertFalse($environment->isAutoReload());
    }

    public function testCanSpecifyCacheDirectoryViaConfiguration()
    {
        $config = ['templates' => ['cache_dir' => __DIR__]];

        $this->container
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [TwigExtension::class, false],
                [\Zend\Expressive\Twig\TwigExtension::class, false],
                [ServerUrlHelper::class, false],
                [\Zend\Expressive\Helper\ServerUrlHelper::class, false],
                [UrlHelper::class, false],
                [\Zend\Expressive\Helper\UrlHelper::class, false],
            ]);

        $this->container
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['config', $config],
            ]);

        $factory     = new TwigEnvironmentFactory();
        $environment = $factory($this->container);

        $this->assertEquals($config['templates']['cache_dir'], $environment->getCache());
    }

    public function testAddsTwigExtensionIfRouterIsInContainer()
    {
        $serverUrlHelper = $this->createMock(ServerUrlHelper::class);
        $urlHelper       = $this->createMock(UrlHelper::class);

        $this->container
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['config', false],
                [TwigExtension::class, true],
                [ServerUrlHelper::class, true],
                [UrlHelper::class, true],
            ]);

        $this->container
            ->expects($this->any())
            ->method('get')
            ->withConsecutive([TwigExtension::class], [ServerUrlHelper::class], [UrlHelper::class])
            ->willReturnOnConsecutiveCalls(
                $this->returnCallback(function () {
                    $twigExtensionFactory = new TwigExtensionFactory();
                    return $twigExtensionFactory($this->container);
                }),
                $serverUrlHelper,
                $urlHelper
            );

        $factory     = new TwigEnvironmentFactory();
        $environment = $factory($this->container);

        $this->assertTrue($environment->hasExtension(TwigExtension::class));
    }

    public function invalidExtensions()
    {
        return [
            'null'                  => [null],
            'true'                  => [true],
            'false'                 => [false],
            'zero'                  => [0],
            'int'                   => [1],
            'zero-float'            => [0.0],
            'float'                 => [1.1],
            'non-service-string'    => ['not-an-extension'],
            'array'                 => [['not-an-extension']],
            'non-extensions-object' => [(object) ['extension' => 'not-an-extension']],
        ];
    }

    /**
     * @dataProvider invalidExtensions
     *
     * @param mixed $extension
     */
    public function testRaisesExceptionForInvalidExtensions($extension)
    {
        $config = [
            'templates' => [],
            'twig'      => [
                'extensions' => [$extension],
            ],
        ];

        $this->container
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [TwigExtension::class, false],
                [\Zend\Expressive\Twig\TwigExtension::class, false],
                [ServerUrlHelper::class, false],
                [\Zend\Expressive\Helper\ServerUrlHelper::class, false],
                [UrlHelper::class, false],
                [\Zend\Expressive\Helper\UrlHelper::class, false],
                [$extension, false],
            ]);

        $this->container
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['config', $config],
            ]);

        $factory = new TwigEnvironmentFactory();

        $this->expectException(InvalidExtensionException::class);
        $factory($this->container);
    }

    public function invalidConfiguration()
    {
        //                        [Config value, Type]
        return [
            'true'             => [true, 'boolean'],
            'false'            => [false, 'boolean'],
            'zero'             => [0, 'integer'],
            'int'              => [1, 'integer'],
            'zero-float'       => [0.0, 'double'],
            'float'            => [1.1, 'double'],
            'string'           => ['not-configuration', 'string'],
            'non-array-object' => [(object) ['not' => 'configuration'], 'stdClass'],
        ];
    }

    /**
     * @dataProvider invalidConfiguration
     *
     * @param mixed $config
     * @param string $contains
     */
    public function testRaisesExceptionForInvalidConfigService($config, $contains)
    {
        $this->container->method('has')->with('config')->willReturn(true);
        $this->container->method('get')->with('config')->willReturn($config);
        $factory = new TwigEnvironmentFactory();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($contains);
        $factory($this->container);
    }

    public function testUsesTimezoneConfiguration()
    {
        $tz = DateTimeZone::listIdentifiers()[0];
        $config = [
            'twig' => [
                'timezone' => $tz,
            ],
        ];

        $this->container
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [TwigExtension::class, false],
                [\Zend\Expressive\Twig\TwigExtension::class, false],
                [ServerUrlHelper::class, false],
                [\Zend\Expressive\Helper\ServerUrlHelper::class, false],
                [UrlHelper::class, false],
                [\Zend\Expressive\Helper\UrlHelper::class, false],
            ]);

        $this->container
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['config', $config],
            ]);

        $factory = new TwigEnvironmentFactory();
        $environment = $factory($this->container);
        $fetchedTz = $environment->getExtension(CoreExtension::class)->getTimezone();
        $this->assertEquals(new DateTimeZone($tz), $fetchedTz);
    }

    public function testRaisesExceptionForInvalidTimezone()
    {
        $tz = 'Luna/Copernicus_Crater';
        $config = [
            'twig' => [
                'timezone' => $tz,
            ],
        ];

        $this->container
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [TwigExtension::class, false],
                [\Zend\Expressive\Twig\TwigExtension::class, false],
                [ServerUrlHelper::class, false],
                [\Zend\Expressive\Helper\ServerUrlHelper::class, false],
                [UrlHelper::class, false],
                [\Zend\Expressive\Helper\UrlHelper::class, false],
            ]);

        $this->container
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['config', $config],
            ]);

        $factory = new TwigEnvironmentFactory();

        $this->expectException(InvalidConfigException::class);
        $factory($this->container);
    }

    public function testRaisesExceptionForNonStringTimezone()
    {
        $config = [
            'twig' => [
                'timezone' => new DateTimeZone('UTC'),
            ],
        ];
        $this->container->method('has')->with('config')->willReturn(true);
        $this->container->method('get')->with('config')->willReturn($config);
        $factory = new TwigEnvironmentFactory();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('"timezone" configuration value must be a string');

        $factory($this->container);
    }

    public function invalidRuntimeLoaders()
    {
        return [
            'null'                  => [null],
            'true'                  => [true],
            'false'                 => [false],
            'zero'                  => [0],
            'int'                   => [1],
            'zero-float'            => [0.0],
            'float'                 => [1.1],
            'non-service-string'    => ['not-an-runtime-loader'],
            'array'                 => [['not-an-runtime-loader']],
            'non-extensions-object' => [(object) ['extension' => 'not-an-runtime-loader']],
        ];
    }

    /**
     * @dataProvider invalidRuntimeLoaders
     *
     * @param mixed $runtimeLoader
     */
    public function testRaisesExceptionForInvalidRuntimeLoaders($runtimeLoader)
    {
        $config = [
            'templates' => [],
            'twig' => [
                'runtime_loaders' => [$runtimeLoader],
            ],
        ];

        $this->container
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [TwigExtension::class, false],
                [\Zend\Expressive\Twig\TwigExtension::class, false],
                [ServerUrlHelper::class, false],
                [\Zend\Expressive\Helper\ServerUrlHelper::class, false],
                [UrlHelper::class, false],
                [\Zend\Expressive\Helper\UrlHelper::class, false],
                [$runtimeLoader, false]
            ]);

        $this->container
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['config', $config],
            ]);

        $factory = new TwigEnvironmentFactory();

        $this->expectException(InvalidRuntimeLoaderException::class);
        $factory($this->container);
    }

    public function testInjectsCustomRuntimeLoadersIntoTwigEnvironment()
    {
        $fooRuntime = $this->createMock(RuntimeLoaderInterface::class);
        $fooRuntime->load('Test\Runtime\FooRuntime')->willReturn('foo-runtime');
        $fooRuntime->load('Test\Runtime\BarRuntime')->willReturn(null);

        $barRuntime = $this->createMock(RuntimeLoaderInterface::class);
        $barRuntime->load('Test\Runtime\BarRuntime')->willReturn('bar-runtime');
        $barRuntime->load('Test\Runtime\FooRuntime')->willReturn(null);

        $config = [
            'templates' => [],
            'twig' => [
                'runtime_loaders' => [
                    $fooRuntime,
                    'Test\Runtime\BarRuntimeLoader',
                ],
            ],
        ];

        $this->container
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [TwigExtension::class, false],
                [\Zend\Expressive\Twig\TwigExtension::class, false],
                [ServerUrlHelper::class, false],
                [\Zend\Expressive\Helper\ServerUrlHelper::class, false],
                [UrlHelper::class, false],
                [\Zend\Expressive\Helper\UrlHelper::class, false],
                ['Test\Runtime\BarRuntimeLoader', true]
            ]);

        $this->container
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['config', $config],
                ['Test\Runtime\BarRuntimeLoader', $barRuntime]
            ]);

        $factory = new TwigEnvironmentFactory();
        $environment = $factory($this->container);

        $this->assertInstanceOf(TwigEnvironment::class, $environment);
        $this->assertEquals('bar-runtime', $environment->getRuntime('Test\Runtime\BarRuntime'));
        $this->assertEquals('foo-runtime', $environment->getRuntime('Test\Runtime\FooRuntime'));
    }

    public function testUsesOptimizationsConfiguration()
    {
        $config = [
            'twig' => [
                'optimizations' => 0,
            ]
        ];

        $this->container
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [TwigExtension::class, false],
                [\Zend\Expressive\Twig\TwigExtension::class, false],
            ]);

        $this->container
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['config', $config],
            ]);

        $factory     = new TwigEnvironmentFactory();
        $environment = $factory($this->container);

        $extension = $environment->getExtension(OptimizerExtension::class);
        $property = new \ReflectionProperty($extension, 'optimizers');
        $property->setAccessible(true);

        $this->assertSame(0, $property->getValue($extension));
    }

    public function testUsesAutoescapeConfiguration()
    {
        $config = [
            'twig' => [
                'autoescape' => false,
            ]
        ];

        $this->container
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [TwigExtension::class, false],
                [\Zend\Expressive\Twig\TwigExtension::class, false],
            ]);

        $this->container
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['config', $config],
            ]);

        $factory     = new TwigEnvironmentFactory();
        $environment = $factory($this->container);
        $extension = $environment->getExtension(EscaperExtension::class);
        $this->assertFalse($extension->getDefaultStrategy('template::name'));
    }

    public function testAutoReloadIgnoreDebugConfiguration()
    {
        $config = [
            'debug' => true,
            'twig'  => [
                'auto_reload' => false,
            ],
        ];

        $this->container
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [TwigExtension::class, false],
                [\Zend\Expressive\Twig\TwigExtension::class, false],
            ]);

        $this->container
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['config', $config],
            ]);

        $factory = new TwigEnvironmentFactory();
        $environment = $factory($this->container);

        $this->assertFalse($environment->isAutoReload());
        $this->assertTrue($environment->isDebug());
    }

    public function testAutoReloadUsesConfiguration()
    {
        $config = [
            'debug' => false,
            'twig'  => [
                'auto_reload' => true,
            ],
        ];

        $this->container
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [TwigExtension::class, false],
                [\Zend\Expressive\Twig\TwigExtension::class, false],
            ]);

        $this->container
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['config', $config],
            ]);

        $factory = new TwigEnvironmentFactory();
        $environment = $factory($this->container);

        $this->assertTrue($environment->isAutoReload());
        $this->assertFalse($environment->isDebug());
    }
}
