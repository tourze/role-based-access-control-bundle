<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RoleBasedAccessControlBundle\DTO\BulkOperationResult;

/**
 * @internal
 */
#[CoversClass(BulkOperationResult::class)]
class BulkOperationResultTest extends TestCase
{
    protected function onSetUp(): void
    {
    }

    public function testBulkOperationResultCanBeInstantiated(): void
    {
        $result = new BulkOperationResult(5, 2, []);
        $this->assertInstanceOf(BulkOperationResult::class, $result);
    }

    public function testGetSuccessCountReturnsCorrectValue(): void
    {
        $result = new BulkOperationResult(10, 3, []);
        $this->assertEquals(10, $result->getSuccessCount());
    }

    public function testGetFailureCountReturnsCorrectValue(): void
    {
        $result = new BulkOperationResult(7, 5, []);
        $this->assertEquals(5, $result->getFailureCount());
    }

    public function testGetFailuresReturnsCorrectArray(): void
    {
        $failures = [
            ['item' => 'item1', 'error' => 'Error message 1'],
            ['item' => 'item2', 'error' => 'Error message 2'],
        ];
        $result = new BulkOperationResult(3, 2, $failures);

        $this->assertEquals($failures, $result->getFailures());
    }

    public function testGetFailuresReturnsEmptyArrayByDefault(): void
    {
        $result = new BulkOperationResult(5, 0);
        $this->assertEquals([], $result->getFailures());
    }

    public function testIsFullSuccessReturnsTrueWhenNoFailures(): void
    {
        $result = new BulkOperationResult(10, 0, []);
        $this->assertTrue($result->isFullSuccess());
    }

    public function testIsFullSuccessReturnsFalseWhenHasFailures(): void
    {
        $result = new BulkOperationResult(8, 2, []);
        $this->assertFalse($result->isFullSuccess());
    }

    public function testGetTotalCountReturnsCorrectSum(): void
    {
        $result = new BulkOperationResult(12, 4, []);
        $this->assertEquals(16, $result->getTotalCount());
    }

    public function testConstructorWithComplexFailures(): void
    {
        $failures = [
            ['item' => ['id' => 1, 'name' => 'user1'], 'error' => 'Validation failed'],
            ['item' => ['id' => 2, 'name' => 'user2'], 'error' => 'Database error'],
            ['item' => 'simple_item', 'error' => 'Processing error'],
        ];

        $result = new BulkOperationResult(15, 3, $failures);

        $this->assertEquals(15, $result->getSuccessCount());
        $this->assertEquals(3, $result->getFailureCount());
        $this->assertEquals(18, $result->getTotalCount());
        $this->assertEquals($failures, $result->getFailures());
        $this->assertFalse($result->isFullSuccess());
    }

    public function testAllCountsAreZero(): void
    {
        $result = new BulkOperationResult(0, 0, []);

        $this->assertEquals(0, $result->getSuccessCount());
        $this->assertEquals(0, $result->getFailureCount());
        $this->assertEquals(0, $result->getTotalCount());
        $this->assertTrue($result->isFullSuccess());
        $this->assertEquals([], $result->getFailures());
    }

    public function testOnlyFailures(): void
    {
        $failures = [
            ['item' => 'failed_item1', 'error' => 'Error 1'],
            ['item' => 'failed_item2', 'error' => 'Error 2'],
        ];

        $result = new BulkOperationResult(0, 2, $failures);

        $this->assertEquals(0, $result->getSuccessCount());
        $this->assertEquals(2, $result->getFailureCount());
        $this->assertEquals(2, $result->getTotalCount());
        $this->assertFalse($result->isFullSuccess());
        $this->assertEquals($failures, $result->getFailures());
    }

    public function testOnlySuccesses(): void
    {
        $result = new BulkOperationResult(25, 0);

        $this->assertEquals(25, $result->getSuccessCount());
        $this->assertEquals(0, $result->getFailureCount());
        $this->assertEquals(25, $result->getTotalCount());
        $this->assertTrue($result->isFullSuccess());
        $this->assertEquals([], $result->getFailures());
    }

    public function testMixedResults(): void
    {
        $failures = [
            ['item' => 'mixed_item1', 'error' => 'Mixed error 1'],
        ];

        $result = new BulkOperationResult(99, 1, $failures);

        $this->assertEquals(99, $result->getSuccessCount());
        $this->assertEquals(1, $result->getFailureCount());
        $this->assertEquals(100, $result->getTotalCount());
        $this->assertFalse($result->isFullSuccess());
        $this->assertEquals($failures, $result->getFailures());
    }
}
