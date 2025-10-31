<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\RoleBasedAccessControlBundle\RoleBasedAccessControlBundle;

/**
 * @internal
 * @phpstan-ignore symplify.forbiddenExtendOfNonAbstractClass
 */
#[CoversClass(RoleBasedAccessControlBundle::class)]
#[RunTestsInSeparateProcesses]
final class RoleBasedAccessControlBundleTest extends AbstractBundleTestCase
{
}
