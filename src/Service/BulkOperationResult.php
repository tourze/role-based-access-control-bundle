<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Service;

class BulkOperationResult
{
    /**
     * @param int $successCount 成功数量
     * @param int $failureCount 失败数量
     * @param array<array{item: mixed, error: string}> $failures 失败详情
     */
    public function __construct(
        private readonly int $successCount,
        private readonly int $failureCount,
        private readonly array $failures = [],
    ) {
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    /**
     * @return array<array{item: mixed, error: string}>
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    public function isFullSuccess(): bool
    {
        return 0 === $this->failureCount;
    }

    public function getTotalCount(): int
    {
        return $this->successCount + $this->failureCount;
    }
}
