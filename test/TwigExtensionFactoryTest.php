<?php

declare(strict_types=1);

namespace MezzioTest\Twig;

use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Twig\Exception\InvalidConfigException;
use Mezzio\Twig\TwigExtension;
use Mezzio\Twig\TwigExtensionFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function sprintf;

final class TwigExtensionFactoryTest extends TestCase
{
    public function testRaisesExceptionForMissingServerUrlHelper(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::atLeastOnce())->method('has')->willReturn(false);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Missing required `%s` dependency.',
                ServerUrlHelper::class
            )
        );

        $factory = new TwigExtensionFactory();
        $factory($container);
    }

    public function testRaisesExceptionForMissingUrlHelper(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::atLeastOnce())->method('has')->willReturnMap([
            [ServerUrlHelper::class, true],
            [UrlHelper::class, false],
            ['Zend\Expressive\Helper\UrlHelper', false],
        ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Missing required `%s` dependency.',
                UrlHelper::class
            )
        );

        $factory = new TwigExtensionFactory();
        $factory($container);
    }

    public function testUsesAssetsConfigurationWhenAddingTwigExtension(): void
    {
        $config = [
            'templates' => [
                'assets_url'     => 'https://assets.example.com/',
                'assets_version' => 'XYZ',
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::atLeastOnce())->method('has')->willReturn(true);
        $container->expects(self::atLeastOnce())->method('get')->willReturnMap([
            ['config', $config],
            [ServerUrlHelper::class, $this->createStub(ServerUrlHelper::class)],
            [UrlHelper::class, $this->createStub(UrlHelper::class)],
        ]);

        $factory   = new TwigExtensionFactory();
        $extension = $factory($container);

        $this->assertInstanceOf(TwigExtension::class, $extension);
        $this->assertEquals('https://assets.example.com/test?v=XYZ', $extension->renderAssetUrl('test'));
    }

    public function testConfiguresGlobals(): void
    {
        $config = [
            'twig' => [
                'globals' => [
                    'ga_tracking' => 'UA-XXXXX-X',
                    'foo'         => 'bar',
                ],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::atLeastOnce())->method('has')->willReturn(true);
        $container->expects(self::atLeastOnce())->method('get')->willReturnMap([
            ['config', $config],
            [ServerUrlHelper::class, $this->createStub(ServerUrlHelper::class)],
            [UrlHelper::class, $this->createStub(UrlHelper::class)],
        ]);

        $factory   = new TwigExtensionFactory();
        $extension = $factory($container);

        $this->assertInstanceOf(TwigExtension::class, $extension);
        $this->assertEquals($config['twig']['globals'], $extension->getGlobals());
    }
}
