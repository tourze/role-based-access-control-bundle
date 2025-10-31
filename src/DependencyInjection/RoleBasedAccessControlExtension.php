<?php

namespace Tourze\RoleBasedAccessControlBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class RoleBasedAccessControlExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
