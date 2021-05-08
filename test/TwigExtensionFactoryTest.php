<?php

/**
 * @see       https://github.com/mezzio/mezzio-twigrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-twigrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-twigrenderer/blob/master/LICENSE.md New BSD License
 */

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

    public function testRaisesExceptionForMissingServerUrlHelper(): void
    {
        $this->container->expects(self::exactly(2))->method('has')->withConsecutive(
            [ServerUrlHelper::class],
            [\Zend\Expressive\Helper\ServerUrlHelper::class]
        )->willReturn(false);

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
        $this->container->expects(self::exactly(3))->method('has')->withConsecutive(
            [ServerUrlHelper::class],
            [UrlHelper::class],
            [\Zend\Expressive\Helper\UrlHelper::class]
        )->willReturnOnConsecutiveCalls(true, false, false);

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

        $this->container->expects(self::exactly(5))->method('has')->withConsecutive(
            [ServerUrlHelper::class],
            [UrlHelper::class],
            ['config'],
            [ServerUrlHelper::class],
            [UrlHelper::class],
        )->willReturn(true);
        $this->container->expects(self::exactly(3))->method('get')->withConsecutive(
            ['config'],
            [ServerUrlHelper::class],
            [UrlHelper::class],
        )->willReturnOnConsecutiveCalls(
            $config,
            $this->serverUrlHelper,
            $this->urlHelper,
        );

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

        $this->container->expects(self::exactly(5))->method('has')->withConsecutive(
            [ServerUrlHelper::class],
            [UrlHelper::class],
            ['config'],
            [ServerUrlHelper::class],
            [UrlHelper::class],
        )->willReturn(true);
        $this->container->expects(self::exactly(3))->method('get')->withConsecutive(
            ['config'],
            [ServerUrlHelper::class],
            [UrlHelper::class],
        )->willReturnOnConsecutiveCalls(
            $config,
            $this->serverUrlHelper,
            $this->urlHelper,
        );

        $factory   = new TwigExtensionFactory();
        $extension = $factory($this->container);

        $this->assertInstanceOf(TwigExtension::class, $extension);
        $this->assertEquals($config['twig']['globals'], $extension->getGlobals());
    }

    protected function setUp(): void
    {
        $this->container       = $this->createMock(ContainerInterface::class);
        $this->serverUrlHelper = $this->createMock(ServerUrlHelper::class);
        $this->urlHelper       = $this->createMock(UrlHelper::class);
    }
}
