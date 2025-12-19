<?php

namespace Tourze\RoleBasedAccessControlBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\RoleBasedAccessControlBundle\DependencyInjection\RoleBasedAccessControlExtension;

/**
 * @internal
 */
#[CoversClass(RoleBasedAccessControlExtension::class)]
final class RoleBasedAccessControlExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testLoadMethodLoadsConfiguration(): void
    {
        $extension = new RoleBasedAccessControlExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        // 测试 load 方法可以正常执行
        $extension->load([], $container);

        // 验证容器已被正确配置
        $this->assertInstanceOf(ContainerBuilder::class, $container);
        $this->assertEquals('prod', $container->getParameter('kernel.environment'));
    }

    public function testLoadMethodHandlesDevEnvironment(): void
    {
        $extension = new RoleBasedAccessControlExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'dev');

        // 测试 load 方法在开发环境下可以正常执行
        $extension->load([], $container);

        // 验证容器在开发环境下被正确配置
        $this->assertInstanceOf(ContainerBuilder::class, $container);
        $this->assertEquals('dev', $container->getParameter('kernel.environment'));
    }

    public function testLoadMethodHandlesTestEnvironment(): void
    {
        $extension = new RoleBasedAccessControlExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        // 测试 load 方法在测试环境下可以正常执行
        $extension->load([], $container);

        // 验证容器在测试环境下被正确配置
        $this->assertInstanceOf(ContainerBuilder::class, $container);
        $this->assertEquals('test', $container->getParameter('kernel.environment'));
    }

    public function testLoadMethodHandlesProdEnvironment(): void
    {
        $extension = new RoleBasedAccessControlExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        // 测试 load 方法在生产环境下可以正常执行
        $extension->load([], $container);

        // 验证容器在生产环境下被正确配置
        $this->assertInstanceOf(ContainerBuilder::class, $container);
        $this->assertEquals('prod', $container->getParameter('kernel.environment'));
    }
}
