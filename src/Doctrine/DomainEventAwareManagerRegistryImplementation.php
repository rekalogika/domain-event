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

namespace Rekalogika\DomainEvent\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Rekalogika\DomainEvent\DomainEventAwareManagerRegistry as DomainEventDomainEventAwareManagerRegistry;
use Rekalogika\DomainEvent\DomainEventAwareObjectManager;
use Rekalogika\DomainEvent\DomainEventManagerInterface;
use Symfony\Contracts\Service\ResetInterface;

final class DomainEventAwareManagerRegistryImplementation extends AbstractManagerRegistryDecorator implements
    DomainEventDomainEventAwareManagerRegistry,
    ResetInterface
{
    /**
     * @var \WeakMap<ObjectManager,DomainEventManagerInterface>
     */
    private \WeakMap $objectManagerToDecoratedObjectManager;

    /**
     * @param iterable<DomainEventManagerInterface> $decoratedObjectManagers
     */
    public function __construct(
        private ManagerRegistry $wrapped,
        iterable $decoratedObjectManagers
    ) {
        parent::__construct($wrapped);

        /** @var \WeakMap<ObjectManager,DomainEventManagerInterface> */
        $weakMap = new \WeakMap();
        $this->objectManagerToDecoratedObjectManager = $weakMap;

        foreach ($decoratedObjectManagers as $decoratedObjectManager) {
            if ($decoratedObjectManager instanceof DomainEventAwareEntityManager) {
                $this->objectManagerToDecoratedObjectManager[$decoratedObjectManager->getObjectManager()] =  $decoratedObjectManager;
            }
        }
    }

    public function getDomainEventAwareManagers(): array
    {
        $managers = $this->getManagers();
        $domainEventAwareManagers = [];

        foreach ($managers as $name => $manager) {
            $domainEventAwareManagers[$name] = $this->getDomainEventAwareManager($manager);
        }

        return $domainEventAwareManagers;
    }

    public function getDomainEventAwareManagerForClass(
        string $class
    ): ?DomainEventAwareObjectManager {
        $manager = $this->getManagerForClass($class);

        if ($manager === null) {
            return null;
        }

        return $this->getDomainEventAwareManager($manager);
    }

    public function getRealRegistry(): ManagerRegistry
    {
        return $this->wrapped;
    }

    public function reset(): void
    {
        if ($this->wrapped instanceof ResetInterface) {
            $this->wrapped->reset();
        }
    }

    public function getManager(?string $name = null): ObjectManager
    {
        $manager = parent::getManager($name);

        return $this->getDomainEventAwareManager($manager);
    }

    public function getDomainEventAwareManager(
        ObjectManager $objectManager
    ): DomainEventAwareObjectManager {
        if ($objectManager instanceof DomainEventAwareObjectManager) {
            return $objectManager;
        }

        $domainEventManager = $this->objectManagerToDecoratedObjectManager[$objectManager] ?? null;

        if ($domainEventManager === null) {
            throw new \InvalidArgumentException('Object manager is not decorated');
        }

        if (!$domainEventManager instanceof DomainEventAwareObjectManager) {
            throw new \InvalidArgumentException('Object manager is not decorated');
        }

        return $domainEventManager;
    }

    /**
     * @return array<string,ObjectManager>
     */
    public function getManagers(): array
    {
        $managers = parent::getManagers();

        foreach ($managers as $name => $manager) {
            $managers[$name] = $this->getDomainEventAwareManager($manager);
        }

        return $managers;
    }

    public function resetManager(?string $name = null): ObjectManager
    {
        $manager = parent::resetManager($name);

        return $this->getDomainEventAwareManager($manager);
    }

    public function getManagerForClass(string $class): ?ObjectManager
    {
        $manager = parent::getManagerForClass($class);

        if ($manager === null) {
            return null;
        }

        return $this->getDomainEventAwareManager($manager);
    }

    public function getRepository(
        string $persistentObject,
        ?string $persistentManagerName = null
    ): ObjectRepository {
        return $this
            ->selectManager($persistentObject, $persistentManagerName)
            ->getRepository($persistentObject);
    }

    /**
     * @param class-string $persistentObject
     * */
    private function selectManager(
        string $persistentObject,
        ?string $persistentManagerName = null
    ): ObjectManager {
        if ($persistentManagerName !== null) {
            return $this->getManager($persistentManagerName);
        }

        return $this->getManagerForClass($persistentObject) ?? $this->getManager();
    }
}
