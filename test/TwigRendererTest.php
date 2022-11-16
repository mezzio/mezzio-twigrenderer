<?php

declare(strict_types=1);

namespace MezzioTest\Twig;

use ArrayObject;
use Mezzio\Template\Exception;
use Mezzio\Template\TemplatePath;
use Mezzio\Twig\TwigRenderer;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

use function file_get_contents;
use function sprintf;
use function str_replace;
use function uniqid;
use function var_export;

class TwigRendererTest extends TestCase
{
    private Environment $twigEnvironment;

    protected function setUp(): void
    {
        $this->twigEnvironment = new Environment(new FilesystemLoader());
    }

    public function assertEqualTemplatePath(
        TemplatePath $expected,
        TemplatePath $received,
        ?string $message = null
    ): void {
        $message = $message ?: 'Failed to assert TemplatePaths are equal';
        if (
            $expected->getPath() !== $received->getPath()
            || $expected->getNamespace() !== $received->getNamespace()
        ) {
            $this->fail($message);
        }
    }

    public function testCanPassEngineToConstructor(): void
    {
        $renderer = new TwigRenderer($this->twigEnvironment);
        $this->assertInstanceOf(TwigRenderer::class, $renderer);
        $this->assertEmpty($renderer->getPaths());
    }

    public function testInstantiatingWithoutEngineLazyLoadsOne(): void
    {
        $renderer = new TwigRenderer();
        $this->assertInstanceOf(TwigRenderer::class, $renderer);
        $this->assertEmpty($renderer->getPaths());
    }

    public function testCanAddPathWithEmptyNamespace(): void
    {
        $renderer = new TwigRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $paths = $renderer->getPaths();
        $this->assertIsArray($paths);
        $this->assertCount(1, $paths);
        $this->assertTemplatePath(__DIR__ . '/TestAsset', $paths[0]);
        $this->assertTemplatePathString(__DIR__ . '/TestAsset', $paths[0]);
        $this->assertEmptyTemplatePathNamespace($paths[0]);
    }

    public function assertTemplatePath(string $path, TemplatePath $templatePath, ?string $message = null): void
    {
        $message = $message ?: sprintf('Failed to assert TemplatePath contained path %s', $path);
        $this->assertEquals($path, $templatePath->getPath(), $message);
    }

    public function assertTemplatePathString(string $path, TemplatePath $templatePath, ?string $message = null): void
    {
        $message = $message ?: sprintf('Failed to assert TemplatePath casts to string path %s', $path);
        $this->assertEquals($path, (string) $templatePath, $message);
    }

    public function assertEmptyTemplatePathNamespace(TemplatePath $templatePath, ?string $message = null): void
    {
        $message = $message ?: 'Failed to assert TemplatePath namespace was empty';
        $this->assertEmpty($templatePath->getNamespace(), $message);
    }

    public function testCanAddPathWithNamespace(): void
    {
        $renderer = new TwigRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset', 'test');
        $paths = $renderer->getPaths();
        $this->assertIsArray($paths);
        $this->assertCount(1, $paths);
        $this->assertTemplatePath(__DIR__ . '/TestAsset', $paths[0]);
        $this->assertTemplatePathString(__DIR__ . '/TestAsset', $paths[0]);
        $this->assertTemplatePathNamespace('test', $paths[0]);
    }

    public function assertTemplatePathNamespace(
        string $namespace,
        TemplatePath $templatePath,
        ?string $message = null
    ): void {
        $message = $message ?: sprintf(
            'Failed to assert TemplatePath namespace matched %s',
            var_export($namespace, true)
        );
        $this->assertEquals($namespace, $templatePath->getNamespace(), $message);
    }

    public function testDelegatesRenderingToUnderlyingImplementation(): void
    {
        $renderer = new TwigRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name   = 'Twig';
        $result = $renderer->render('twig.html', ['name' => $name]);
        $this->assertStringContainsString($name, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/twig.html');
        $content = str_replace('{{ name }}', $name, $content);
        $this->assertEquals($content, $result);
    }

    /**
     * @return array<string, array<bool|int|string>>
     */
    public function invalidParameterValues(): array
    {
        return [
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'string'     => ['value'],
        ];
    }

    /**
     * @dataProvider invalidParameterValues
     */
    public function testRenderRaisesExceptionForInvalidParameterTypes(mixed $params): void
    {
        $renderer = new TwigRenderer();
        $this->expectException(Exception\InvalidArgumentException::class);
        $renderer->render('foo', $params);
    }

    public function testCanRenderWithNullParams(): void
    {
        $renderer = new TwigRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $result  = $renderer->render('twig-null.html', null);
        $content = file_get_contents(__DIR__ . '/TestAsset/twig-null.html');
        $this->assertEquals($content, $result);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function objectParameterValues(): array
    {
        $names = [
            'stdClass'    => uniqid(),
            'ArrayObject' => uniqid(),
        ];

        return [
            'stdClass'    => [(object) ['name' => $names['stdClass']], $names['stdClass']],
            'ArrayObject' => [new ArrayObject(['name' => $names['ArrayObject']]), $names['ArrayObject']],
        ];
    }

    /**
     * @dataProvider objectParameterValues
     */
    public function testCanRenderWithParameterObjects(object $params, string $search): void
    {
        $renderer = new TwigRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $result = $renderer->render('twig.html', $params);
        $this->assertStringContainsString($search, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/twig.html');
        $content = str_replace('{{ name }}', $search, $content);
        $this->assertEquals($content, $result);
    }

    /**
     * @group namespacing
     */
    public function testProperlyResolvesNamespacedTemplate(): void
    {
        $renderer = new TwigRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset/test', 'test');

        $expected = file_get_contents(__DIR__ . '/TestAsset/test/test.html');
        $test     = $renderer->render('test::test');

        $this->assertSame($expected, $test);
    }

    /**
     * @group namespacing
     */
    public function testResolvesNamespacedTemplateWithSuffix(): void
    {
        $renderer = new TwigRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset/test', 'test');

        $expected = file_get_contents(__DIR__ . '/TestAsset/test/test.json');
        $test     = $renderer->render('test::test.json');

        $this->assertSame($expected, $test);
    }

    public function testAddParameterToOneTemplate(): void
    {
        $renderer = new TwigRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name = 'Twig';
        $renderer->addDefaultParam('twig', 'name', $name);
        $result = $renderer->render('twig');

        $content = file_get_contents(__DIR__ . '/TestAsset/twig.html');
        $content = str_replace('{{ name }}', $name, $content);
        $this->assertEquals($content, $result);
    }

    public function testAddSharedParameters(): void
    {
        $renderer = new TwigRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name = 'Twig';
        $renderer->addDefaultParam($renderer::TEMPLATE_ALL, 'name', $name);
        $result  = $renderer->render('twig');
        $content = file_get_contents(__DIR__ . '/TestAsset/twig.html');
        $content = str_replace('{{ name }}', $name, $content);
        $this->assertEquals($content, $result);

        $result  = $renderer->render('twig-2');
        $content = file_get_contents(__DIR__ . '/TestAsset/twig-2.html');
        $content = str_replace('{{ name }}', $name, $content);
        $this->assertEquals($content, $result);
    }

    public function testOverrideSharedParametersPerTemplate(): void
    {
        $renderer = new TwigRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name  = 'Twig';
        $name2 = 'Template';
        $renderer->addDefaultParam($renderer::TEMPLATE_ALL, 'name', $name);
        $renderer->addDefaultParam('twig-2', 'name', $name2);
        $result  = $renderer->render('twig');
        $content = file_get_contents(__DIR__ . '/TestAsset/twig.html');
        $content = str_replace('{{ name }}', $name, $content);
        $this->assertEquals($content, $result);

        $result  = $renderer->render('twig-2');
        $content = file_get_contents(__DIR__ . '/TestAsset/twig-2.html');
        $content = str_replace('{{ name }}', $name2, $content);
        $this->assertEquals($content, $result);
    }

    public function testOverrideSharedParametersAtRender(): void
    {
        $renderer = new TwigRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name  = 'Twig';
        $name2 = 'Template';
        $renderer->addDefaultParam($renderer::TEMPLATE_ALL, 'name', $name);
        $result  = $renderer->render('twig', ['name' => $name2]);
        $content = file_get_contents(__DIR__ . '/TestAsset/twig.html');
        $content = str_replace('{{ name }}', $name2, $content);
        $this->assertEquals($content, $result);
    }
}
