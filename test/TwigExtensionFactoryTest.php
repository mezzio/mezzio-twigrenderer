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
    /** @var ContainerInterface|MockObject */
    private $container;

    /** @var ServerUrlHelper|MockObject */
    private $serverUrlHelper;

    /** @var UrlHelper|MockObject */
    private $urlHelper;

    protected function setUp(): void
    {
        $this->container       = $this->createMock(ContainerInterface::class);
        $this->serverUrlHelper = $this->createMock(ServerUrlHelper::class);
        $this->urlHelper       = $this->createMock(UrlHelper::class);
    }

    public function testRaisesExceptionForMissingServerUrlHelper()
    {
        $this->container
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                [ServerUrlHelper::class, false],
                [\Zend\Expressive\Helper\UrlHelper::class, false],
            ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(sprintf(
            'Missing required `%s` dependency.',
            ServerUrlHelper::class
        ));

        $factory = new TwigExtensionFactory();
        $factory($this->container);
    }

    public function testRaisesExceptionForMissingUrlHelper()
    {
        $this->container
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                [ServerUrlHelper::class, true],
                [UrlHelper::class, false],
                [\Zend\Expressive\Helper\UrlHelper::class, false],
            ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(sprintf(
            'Missing required `%s` dependency.',
            UrlHelper::class
        ));

        $factory = new TwigExtensionFactory();
        $factory($this->container);
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

        $this->container
            ->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [ServerUrlHelper::class, true],
                [UrlHelper::class, true],
            ]);

        $this->container
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['config', $config],
                [ServerUrlHelper::class, $this->serverUrlHelper],
                [UrlHelper::class, $this->urlHelper],
            ]);

        $factory   = new TwigExtensionFactory();
        $extension = $factory($this->container);

        $this->assertInstanceOf(TwigExtension::class, $extension);
        $this->assertSame($config['twig']['globals'], $extension->getGlobals());
    }
}
