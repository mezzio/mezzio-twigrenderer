<?php

declare(strict_types=1);

namespace MezzioTest\Twig;

use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Twig\Exception\InvalidConfigException;
use Mezzio\Twig\TwigExtension;
use Mezzio\Twig\TwigExtensionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function sprintf;

class TwigExtensionFactoryTest extends TestCase
{
    /** @var MockObject<ContainerInterface> */
    private $container;

    /** @var MockObject<ServerUrlHelper> */
    private $serverUrlHelper;

    /** @var MockObject<UrlHelper> */
    private $urlHelper;

    protected function setUp(): void
    {
        $this->container       = $this->createMock(ContainerInterface::class);
        $this->serverUrlHelper = $this->createMock(ServerUrlHelper::class);
        $this->urlHelper       = $this->createMock(UrlHelper::class);
    }

    public function testRaisesExceptionForMissingServerUrlHelper(): void
    {
        $this->container->expects(self::atLeastOnce())->method('has')->willReturn(false);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Missing required `%s` dependency.',
                ServerUrlHelper::class
            )
        );

        $factory = new TwigExtensionFactory();
        $factory($this->container);
    }

    public function testRaisesExceptionForMissingUrlHelper(): void
    {
        $this->container->expects(self::atLeastOnce())->method('has')->willReturnMap([
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
        $factory($this->container);
    }

    public function testUsesAssetsConfigurationWhenAddingTwigExtension(): void
    {
        $config = [
            'templates' => [
                'assets_url'     => 'https://assets.example.com/',
                'assets_version' => 'XYZ',
            ],
        ];

        $this->container->expects(self::atLeastOnce())->method('has')->willReturn(true);
        $this->container->expects(self::atLeastOnce())->method('get')->willReturnMap([
            ['config', $config],
            [ServerUrlHelper::class, $this->serverUrlHelper],
            [UrlHelper::class, $this->urlHelper],
        ]);

        $factory   = new TwigExtensionFactory();
        $extension = $factory($this->container);

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

        $this->container->expects(self::atLeastOnce())->method('has')->willReturn(true);
        $this->container->expects(self::atLeastOnce())->method('get')->willReturnMap([
            ['config', $config],
            [ServerUrlHelper::class, $this->serverUrlHelper],
            [UrlHelper::class, $this->urlHelper],
        ]);

        $factory   = new TwigExtensionFactory();
        $extension = $factory($this->container);

        $this->assertInstanceOf(TwigExtension::class, $extension);
        $this->assertEquals($config['twig']['globals'], $extension->getGlobals());
    }
}
