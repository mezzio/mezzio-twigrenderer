<?php

declare(strict_types=1);

namespace MezzioTest\Twig;

use Mezzio\Twig\ConfigProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    private ConfigProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ConfigProvider();
    }

    /**
     * @return array<string, mixed>
     */
    public function testInvocationReturnsArray(): array
    {
        $config = ($this->provider)();
        $this->assertIsArray($config);

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     */
    #[Depends('testInvocationReturnsArray')]
    public function testReturnedArrayContainsDependencies(array $config): void
    {
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('templates', $config);
        $this->assertIsArray($config['dependencies']);
    }
}
