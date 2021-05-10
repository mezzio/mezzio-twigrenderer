<?php

declare(strict_types=1);

namespace MezzioTest\Twig;

use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Twig\TwigExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

use function sprintf;

class TwigExtensionTest extends TestCase
{
    /** @var MockObject<ServerUrlHelper> */
    private $serverUrlHelper;

    /** @var MockObject<UrlHelper> */
    private $urlHelper;

    protected function setUp(): void
    {
        $this->serverUrlHelper = $this->createMock(ServerUrlHelper::class);
        $this->urlHelper       = $this->createMock(UrlHelper::class);
    }

    public function testRegistersTwigFunctions(): void
    {
        $extension = $this->createExtension('', '');
        $functions = $extension->getFunctions();
        $this->assertFunctionExists('path', $functions);
        $this->assertFunctionExists('url', $functions);
        $this->assertFunctionExists('absolute_url', $functions);
        $this->assertFunctionExists('asset', $functions);
    }

    /**
     * @param null|string|int $assetsVersion
     */
    public function createExtension(?string $assetsUrl, $assetsVersion): TwigExtension
    {
        return new TwigExtension(
            $this->serverUrlHelper,
            $this->urlHelper,
            $assetsUrl,
            $assetsVersion
        );
    }

    public function assertFunctionExists(string $name, array $functions, ?string $message = null): void
    {
        $message  = $message ?: sprintf('Failed to identify function by name %s', $name);
        $function = $this->findFunction($name, $functions);
        $this->assertInstanceOf(TwigFunction::class, $function, $message);
    }

    /**
     * @param array $functions
     * @return false|TwigFunction
     */
    public function findFunction(string $name, array $functions)
    {
        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
            if ($function->getName() === $name) {
                return $function;
            }
        }

        return false;
    }

    public function testMapsTwigFunctionsToExpectedMethods(): void
    {
        $extension = $this->createExtension('', '');
        $functions = $extension->getFunctions();
        $this->assertSame(
            [$extension, 'renderUri'],
            $this->findFunction('path', $functions)->getCallable(),
            'Received different path function than expected'
        );
        $this->assertSame(
            [$extension, 'renderUrl'],
            $this->findFunction('url', $functions)->getCallable(),
            'Received different url function than expected'
        );
        $this->assertSame(
            [$extension, 'renderUrlFromPath'],
            $this->findFunction('absolute_url', $functions)->getCallable(),
            'Received different path function than expected'
        );
        $this->assertSame(
            [$extension, 'renderAssetUrl'],
            $this->findFunction('asset', $functions)->getCallable(),
            'Received different asset function than expected'
        );
    }

    public function testRenderUriDelegatesToComposedUrlHelper(): void
    {
        $this->urlHelper->expects(self::once())->method('generate')->with('foo', ['id' => 1], [], null, [])->willReturn(
            'URL'
        );
        $extension = $this->createExtension('', '');
        $this->assertSame('URL', $extension->renderUri('foo', ['id' => 1]));
    }

    public function testRenderUrlDelegatesToComposedUrlHelperAndServerUrlHelper(): void
    {
        $this->urlHelper->expects(self::once())->method('generate')->with('foo', ['id' => 1], [], null, [])->willReturn(
            'PATH'
        );
        $this->serverUrlHelper->expects(self::once())->method('generate')->with('PATH')->willReturn('HOST/PATH');
        $extension = $this->createExtension('', '');
        $this->assertSame('HOST/PATH', $extension->renderUrl('foo', ['id' => 1]));
    }

    public function testRenderUrlFromPathDelegatesToComposedServerUrlHelper(): void
    {
        $this->serverUrlHelper->expects(self::once())->method('generate')->with('PATH')->willReturn('HOST/PATH');
        $extension = $this->createExtension('', '');
        $this->assertSame('HOST/PATH', $extension->renderUrlFromPath('PATH'));
    }

    public function testRenderAssetUrlUsesComposedAssetUrlAndVersionToGenerateUrl(): void
    {
        $extension = $this->createExtension('https://images.example.com/', 'XYZ');
        $this->assertSame('https://images.example.com/foo.png?v=XYZ', $extension->renderAssetUrl('foo.png'));
    }

    public function testRenderAssetUrlUsesProvidedVersionToGenerateUrl(): void
    {
        $extension = $this->createExtension('https://images.example.com/', 'XYZ');
        $this->assertSame(
            'https://images.example.com/foo.png?v=ABC',
            $extension->renderAssetUrl('foo.png', 'ABC')
        );
    }

    /**
     * @return array<string, array<null|string>>
     */
    public function emptyAssetVersions(): array
    {
        return [
            'null'         => [null],
            'empty-string' => [''],
        ];
    }

    /**
     * @dataProvider emptyAssetVersions
     */
    public function testRenderAssetUrlWithoutProvidedVersion(?string $emptyValue): void
    {
        $extension = $this->createExtension('https://images.example.com/', $emptyValue);
        $this->assertSame(
            'https://images.example.com/foo.png',
            $extension->renderAssetUrl('foo.png')
        );
    }

    /**
     * @return array<string, array<int|string>>
     */
    public function zeroAssetVersions(): array
    {
        return [
            'zero'        => [0],
            'zero-string' => ['0'],
        ];
    }

    /**
     * @dataProvider zeroAssetVersions
     * @param int|string $zeroValue
     */
    public function testRendersZeroVersionAssetUrl($zeroValue): void
    {
        $extension = $this->createExtension('https://images.example.com/', $zeroValue);
        $this->assertSame(
            'https://images.example.com/foo.png?v=0',
            $extension->renderAssetUrl('foo.png')
        );
    }
}
