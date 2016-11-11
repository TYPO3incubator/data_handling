<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Projection;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event;
use TYPO3\CMS\EventSourcing\DataHandling\Infrastructure\EventStore\Saga;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Event\BaseEvent;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Projection\Projection;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntityEventRepository;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\TableVersionProjectionRepository;

class TableVersionProjection implements Projection
{
    public function listensTo()
    {
        return [
            Event\CreatedEntityEvent::class,
            Event\BranchedEntityFromEvent::class,
            // @todo Add DiscardedBranch
            // @todo Add MergedBranch
        ];
    }

    /**
     * @param BaseEvent|Event\AbstractEvent $event
     */
    public function project(BaseEvent $event)
    {
        // only project workspace related data
        if ($event->getContext()->getWorkspaceId() === 0) {
            return;
        }

        if ($event instanceof Event\CreatedEntityEvent) {
            $this->projectCreatedEntityEvent($event);
        }
        if ($event instanceof Event\BranchedEntityFromEvent) {
            $this->projectBranchedEntityFromEvent($event);
        }

        // $this->projectDiscardedEntityEvent($event);
        // $this->projectMergedEntityEvent($event);
    }

    /**
     * @param Event\CreatedEntityEvent $event
     * @throws \Exception
     */
    private function projectCreatedEntityEvent(Event\CreatedEntityEvent $event)
    {
        TableVersionProjectionRepository::instance()->increment(
            $event->getContext()->getWorkspaceId(),
            $event->getNodeReference()->getUid(),
            $event->getAggregateType()
        );
    }

    /**
     * @param Event\BranchedEntityFromEvent $event
     * @throws \Exception
     */
    private function projectBranchedEntityFromEvent(Event\BranchedEntityFromEvent $event)
    {
        $genericEntity = GenericEntityEventRepository::instance()
            ->findByAggregateReference(
                $event->getAggregateReference(),
                $event->getEventId(),
                Saga::EVENT_INCLUDING
            );

        TableVersionProjectionRepository::instance()->increment(
            $event->getContext()->getWorkspaceId(),
            $genericEntity->getNode()->getUid(),
            $event->getAggregateType()
        );
    }
}
