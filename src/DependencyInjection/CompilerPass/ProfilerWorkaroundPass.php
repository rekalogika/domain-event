<?php

declare(strict_types=1);

/*
 * This file is part of rekalogika/domain-event-src package.
 *
 * (c) Priyadi Iman Nurcahyo <https://rekalogika.dev>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Rekalogika\DomainEvent\DependencyInjection\CompilerPass;

use Doctrine\Bundle\DoctrineBundle\Controller\ProfilerController;
use Rekalogika\DomainEvent\DependencyInjection\Constants;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Workaround for this error:
 *
 * [ERROR] Invalid definition for service
 * "Doctrine\Bundle\DoctrineBundle\Controller\ProfilerController": argument 2 of
 * "Doctrine\Bundle\DoctrineBundle\Controller\ProfilerController::__construct()"
 * accepts "Doctrine\Bundle\DoctrineBundle\Registry",
 * "Rekalogika\DomainEvent\Doctrine\DomainEventAwareManagerRegistryImplementation"
 * passed.
 *
 * @internal
 */
final class ProfilerWorkaroundPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $doctrine = $container->getDefinition(Constants::REAL_MANAGER_REGISTRY);

        $profilerController = $container->getDefinition(ProfilerController::class);
        $profilerController->setArgument(1, $doctrine);
    }
}