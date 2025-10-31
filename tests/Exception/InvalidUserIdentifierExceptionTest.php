<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RoleBasedAccessControlBundle\Exception\InvalidUserIdentifierException;

/**
 * @internal
 */
#[CoversClass(InvalidUserIdentifierException::class)]
class InvalidUserIdentifierExceptionTest extends AbstractExceptionTestCase
{
}
