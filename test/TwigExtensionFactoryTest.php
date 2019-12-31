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
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ProphecyInterface;
use Psr\Container\ContainerInterface;

use function sprintf;

class TwigExtensionFactoryTest extends TestCase
{
    /**
     * @var ContainerInterface|ProphecyInterface
     */
    private $container;

    /**
     * @var ServerUrlHelper|ProphecyInterface
     */
    private $serverUrlHelper;

    /**
     * @var UrlHelper|ProphecyInterface
     */
    private $urlHelper;

    public function setUp()
    {
        $this->container       = $this->prophesize(ContainerInterface::class);
        $this->serverUrlHelper = $this->prophesize(ServerUrlHelper::class);
        $this->urlHelper       = $this->prophesize(UrlHelper::class);
    }

    public function testRaisesExceptionForMissingServerUrlHelper()
    {
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Helper\ServerUrlHelper::class)->willReturn(false);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(sprintf(
            'Missing required `%s` dependency.',
            ServerUrlHelper::class
        ));

        $factory = new TwigExtensionFactory();
        $factory($this->container->reveal());
    }

    public function testRaisesExceptionForMissingUrlHelper()
    {
        $this->container->has(ServerUrlHelper::class)->willReturn(true);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Helper\UrlHelper::class)->willReturn(false);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(sprintf(
            'Missing required `%s` dependency.',
            UrlHelper::class
        ));

        $factory = new TwigExtensionFactory();
        $factory($this->container->reveal());
    }

    public function testUsesAssetsConfigurationWhenAddingTwigExtension()
    {
        $config = [
            'templates' => [
                'assets_url'     => 'http://assets.example.com/',
                'assets_version' => 'XYZ',
            ],
        ];

        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(true);
        $this->container->get(ServerUrlHelper::class)->willReturn($this->serverUrlHelper->reveal());
        $this->container->has(UrlHelper::class)->willReturn(true);
        $this->container->get(UrlHelper::class)->willReturn($this->urlHelper->reveal());
        $factory   = new TwigExtensionFactory();
        $extension = $factory($this->container->reveal());

        $this->assertInstanceOf(TwigExtension::class, $extension);
        $this->assertAttributeEquals($config['templates']['assets_url'], 'assetsUrl', $extension);
        $this->assertAttributeEquals($config['templates']['assets_version'], 'assetsVersion', $extension);
        $this->assertAttributeSame($this->serverUrlHelper->reveal(), 'serverUrlHelper', $extension);
        $this->assertAttributeSame($this->urlHelper->reveal(), 'urlHelper', $extension);
    }

    public function testConfiguresGlobals()
    {
        $config = [
            'twig' => [
                'globals' => [
                    'ga_tracking' => 'UA-XXXXX-X',
                    'foo'         => 'bar',
                ],
            ],
        ];

        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(true);
        $this->container->get(ServerUrlHelper::class)->willReturn($this->serverUrlHelper->reveal());
        $this->container->has(UrlHelper::class)->willReturn(true);
        $this->container->get(UrlHelper::class)->willReturn($this->urlHelper->reveal());
        $factory   = new TwigExtensionFactory();
        $extension = $factory($this->container->reveal());

        $this->assertInstanceOf(TwigExtension::class, $extension);
        $this->assertAttributeEquals($config['twig']['globals'], 'globals', $extension);
        $this->assertAttributeSame($this->serverUrlHelper->reveal(), 'serverUrlHelper', $extension);
        $this->assertAttributeSame($this->urlHelper->reveal(), 'urlHelper', $extension);
    }
}
