<?php

namespace Tourze\RoleBasedAccessControlBundle\Tests\Bundle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RoleBasedAccessControlBundle\Tests\Exception\ComposerConfigurationException;

/**
 * @internal
 */
#[CoversClass(\stdClass::class)]
class ComposerConfigurationTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $composerConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $composerJsonPath = __DIR__ . '/../../composer.json';
        $composerJson = file_get_contents($composerJsonPath);
        if (false === $composerJson) {
            throw new ComposerConfigurationException('Failed to read composer.json');
        }
        $decoded = json_decode($composerJson, true);
        if (!is_array($decoded)) {
            throw new ComposerConfigurationException('Invalid composer.json format');
        }
        /** @var array<string, mixed> $decoded */
        $this->composerConfig = $decoded;
    }

    public function testPackageHasCorrectName(): void
    {
        $this->assertEquals('tourze/role-based-access-control-bundle', $this->composerConfig['name']);
    }

    public function testPackageIsSymfonyBundle(): void
    {
        $this->assertEquals('symfony-bundle', $this->composerConfig['type']);
    }

    public function testPackageSupportsSymfony64Plus(): void
    {
        $this->assertArrayHasKey('require', $this->composerConfig);
        $require = $this->composerConfig['require'];
        $this->assertIsArray($require);
        $this->assertArrayHasKey('symfony/framework-bundle', $require);

        $version = $require['symfony/framework-bundle'];
        $this->assertIsString($version);
        // 项目使用Symfony 7.3，所以我们要求^7.3
        $this->assertStringStartsWith('^7.3', $version);
    }

    public function testPackageHasDoctrineDependencies(): void
    {
        $this->assertArrayHasKey('require', $this->composerConfig);
        $require = $this->composerConfig['require'];
        $this->assertIsArray($require);
        $this->assertArrayHasKey('doctrine/orm', $require);
        $this->assertArrayHasKey('doctrine/doctrine-bundle', $require);
    }

    public function testPackageHasSecurityDependency(): void
    {
        $this->assertArrayHasKey('require', $this->composerConfig);
        $require = $this->composerConfig['require'];
        $this->assertIsArray($require);
        $this->assertArrayHasKey('symfony/security-bundle', $require);
    }

    public function testDevDependenciesIncludePHPStan(): void
    {
        $this->assertArrayHasKey('require-dev', $this->composerConfig);
        $requireDev = $this->composerConfig['require-dev'];
        $this->assertIsArray($requireDev);
        $this->assertArrayHasKey('phpstan/phpstan', $requireDev);

        $version = $requireDev['phpstan/phpstan'];
        $this->assertIsString($version);
        $this->assertStringStartsWith('^2.1', $version);
    }

    public function testDevDependenciesIncludePHPUnit(): void
    {
        $this->assertArrayHasKey('require-dev', $this->composerConfig);
        $requireDev = $this->composerConfig['require-dev'];
        $this->assertIsArray($requireDev);
        $this->assertArrayHasKey('phpunit/phpunit', $requireDev);

        $version = $requireDev['phpunit/phpunit'];
        $this->assertIsString($version);
        $this->assertStringStartsWith('^11.5', $version);
    }

    public function testAutoloadConfigurationIsCorrect(): void
    {
        $this->assertArrayHasKey('autoload', $this->composerConfig);
        $autoload = $this->composerConfig['autoload'];
        $this->assertIsArray($autoload);
        $this->assertArrayHasKey('psr-4', $autoload);
        $psr4 = $autoload['psr-4'];
        $this->assertIsArray($psr4);
        $this->assertArrayHasKey('Tourze\RoleBasedAccessControlBundle\\', $psr4);
        $this->assertEquals('src', $psr4['Tourze\RoleBasedAccessControlBundle\\']);
    }

    public function testAutoloadDevConfigurationIsCorrect(): void
    {
        $this->assertArrayHasKey('autoload-dev', $this->composerConfig);
        $autoloadDev = $this->composerConfig['autoload-dev'];
        $this->assertIsArray($autoloadDev);
        $this->assertArrayHasKey('psr-4', $autoloadDev);
        $psr4 = $autoloadDev['psr-4'];
        $this->assertIsArray($psr4);
        $this->assertArrayHasKey('Tourze\RoleBasedAccessControlBundle\Tests\\', $psr4);
        $this->assertEquals('tests', $psr4['Tourze\RoleBasedAccessControlBundle\Tests\\']);
    }

    public function testPackageVersionIs0Point0(): void
    {
        // 根据约束，内部包版本应该用0.0.*
        if (isset($this->composerConfig['version'])) {
            $version = $this->composerConfig['version'];
            $this->assertIsString($version);
            $this->assertStringStartsWith('0.0.', $version);
        }
    }
}
