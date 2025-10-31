<?php

namespace Tourze\RoleBasedAccessControlBundle\Tests\DependencyInjection;

use Doctrine\ORM\Mapping as ORM;
use League\ConstructFinder\ConstructFinder;
use Monolog\Attribute\WithMonologChannel;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\PHPUnitBase\TestCaseHelper;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

/**
 * 修复PHP 8.4弃用警告的AbstractDependencyInjectionExtensionTestCase版本
 *
 * 注意：此类故意不继承AbstractDependencyInjectionExtensionTestCase，
 * 因为原基类包含在PHP 8.4中会产生弃用警告的代码。此类修复了这些问题。
 *
 * @internal
 */
#[CoversNothing]
abstract class AbstractExtensionTestCase extends TestCase
{
    /**
     * 这个场景，没必要使用 RunTestsInSeparateProcesses 注解的
     */
    #[Test]
    final public function testShouldNotHaveRunTestsInSeparateProcesses(): void
    {
        $currentClass = get_called_class();
        $reflection = new \ReflectionClass($currentClass);
        $this->assertEmpty($reflection->getAttributes(RunTestsInSeparateProcesses::class), $currentClass . '这个测试用例，不应使用 RunTestsInSeparateProcesses 注解');
    }

    /**
     * 要求所有的基类都要继承 \Tourze\SymfonyDependencyServiceLoader\AutoExtension，以统计加载逻辑
     */
    #[Test]
    final public function testExtendsCorrectBaseClass(): void
    {
        $currentClass = get_called_class();
        $className = TestCaseHelper::extractCoverClass(new \ReflectionClass($currentClass));
        $this->assertNotNull($className, '请使用 \PHPUnit\Framework\Attributes\CoversClass 注解声明当前的测试目标类');
        $this->assertTrue(is_subclass_of($className, AutoExtension::class), "{$className}必须继承" . AutoExtension::class);
    }

    /**
     * 有一些固定的目录，是一定会注册成服务的
     *
     * @return iterable<string>
     */
    protected function provideServiceDirectories(): iterable
    {
        yield 'Controller';
        yield 'Command';
        yield 'Service';
        yield 'Repository';
        yield 'EventSubscriber';
        yield 'MessageHandler';
        yield 'Procedure';
    }

    /**
     * 确认这个服务配置是加载OK的
     */
    #[Test]
    final public function testLoadShouldRegisterServices(): void
    {
        $currentClass = get_called_class();
        $className = TestCaseHelper::extractCoverClass(new \ReflectionClass($currentClass));
        $this->assertIsString($className, 'Class name must be string');
        /** @var class-string $className */
        $reflection = new \ReflectionClass($className);

        $extension = new $className();
        $this->assertInstanceOf(Extension::class, $extension, 'Extension must be an instance of Extension class');

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension->load([], $container);

        // Assert - 检查扩展加载成功
        $this->assertTrue($container->isTrackingResources());

        $fileName = $reflection->getFileName();
        $this->assertNotFalse($fileName, 'File name must not be false');
        $sourceDir = dirname(dirname($fileName));

        foreach ($this->provideServiceDirectories() as $serviceDir) {
            if (!is_dir("{$sourceDir}/{$serviceDir}")) {
                continue;
            }
            $constructs = ConstructFinder::locatedIn("{$sourceDir}/{$serviceDir}")->findClasses();
            foreach ($constructs as $construct) {
                $reflection = new \ReflectionClass($construct->name());
                if ($reflection->isAbstract()) {
                    continue;
                }

                $this->assertTrue($container->hasDefinition($construct->name()), "应该注册 {$construct->name()} 服务，请检查服务配置文件");
            }
        }
    }

    /**
     * 测试是否注入了正确的 LoggerInterface
     */
    #[Test]
    final public function testServiceInjectedLoggerMustUseWithMonologChannelAttribute(): void
    {
        $currentClass = get_called_class();
        $className = TestCaseHelper::extractCoverClass(new \ReflectionClass($currentClass));
        $this->assertIsString($className, 'Class name must be string');

        $extension = new $className();
        $this->assertInstanceOf(Extension::class, $extension);

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension->load([], $container);

        // Assert - 检查扩展加载成功
        $this->assertTrue($container->isTrackingResources());

        foreach ($container->getDefinitions() as $definition) {
            $class = $definition->getClass();
            if (null === $class || !class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            // NOTE: 当前实现仅在Bundle内生效，作用范围受限
            // NOTE: 特殊的服务定义方式可能导致检查错误

            // 检查构造函数是否存在
            $constructor = $reflection->getConstructor();
            if (null === $constructor) {
                continue;
            }

            // 检查构造函数参数是否包含 LoggerInterface
            $hasLoggerInterface = false;
            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();
                if ($type instanceof \ReflectionNamedType
                    && LoggerInterface::class === $type->getName()) {
                    $hasLoggerInterface = true;
                    break;
                }
            }

            // 如果使用了 LoggerInterface，检查是否有 WithMonologChannel 注解
            if ($hasLoggerInterface) {
                $attributes = $reflection->getAttributes(WithMonologChannel::class);
                $this->assertTrue(
                    0 !== count($attributes),
                    sprintf(
                        "服务类 %s 的构造函数使用了 LoggerInterface，但未使用 WithMonologChannel 注解，请使用 `#[WithMonologChannel(channel: '{$extension->getAlias()}')]`",
                        $class
                    )
                );
            }
        }
    }

    /**
     * Bundle类本身，不应该是服务
     *
     * 修复PHP 8.4中正确处理null类值的版本
     */
    #[Test]
    final public function noRegisteredServicesAreBundle(): void
    {
        $currentClass = get_called_class();
        $className = TestCaseHelper::extractCoverClass(new \ReflectionClass($currentClass));
        $this->assertIsString($className, 'Class name must be string');

        $extension = new $className();
        $this->assertInstanceOf(Extension::class, $extension, 'Extension must be an instance of Extension class');

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension->load([], $container);
        $this->assertNotEmpty($container->getDefinitions());

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();
            if (null === $class && class_exists($id)) {
                $class = $id;
            }

            // 修复：在调用class_exists()和interface_exists()前检查null
            if (null === $class || (!class_exists($class) && !interface_exists($class))) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            $this->assertFalse(
                $reflection->isSubclassOf(Bundle::class),
                "Service '{$id}' with class '{$class}' should not in container, remove it from the container"
            );
        }
    }

    /**
     * Entity不应该被注册为服务
     *
     * 修复PHP 8.4中正确处理null类值的版本
     */
    #[Test]
    final public function noRegisteredServicesAreEntity(): void
    {
        $currentClass = get_called_class();
        $className = TestCaseHelper::extractCoverClass(new \ReflectionClass($currentClass));
        $this->assertIsString($className, 'Class name must be string');

        $extension = new $className();
        $this->assertInstanceOf(Extension::class, $extension, 'Extension must be an instance of Extension class');

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension->load([], $container);
        $this->assertNotEmpty($container->getDefinitions());

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();
            if (null === $class && class_exists($id)) {
                $class = $id;
            }

            // 修复：在调用class_exists()和interface_exists()前检查null
            if (null === $class || (!class_exists($class) && !interface_exists($class))) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            $this->assertEmpty(
                $reflection->getAttributes(ORM\Entity::class),
                "Service '{$id}' with class '{$class}' should not in container, remove it from the container"
            );
        }
    }
}
