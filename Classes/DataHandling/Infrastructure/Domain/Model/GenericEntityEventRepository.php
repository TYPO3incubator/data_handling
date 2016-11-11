<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model;

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

use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event\OriginatedEntityEvent;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\GenericEntity;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\EventSourcing\DataHandling\Infrastructure\EventStore\Saga;
use TYPO3\CMS\EventSourcing\DataHandling\Infrastructure\EventStore\EventSelector;
use TYPO3\CMS\EventSourcing\DataHandling\Infrastructure\EventStore\EventStorePool;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Event\BaseEvent;
use TYPO3\CMS\EventSourcing\DataHandling\Infrastructure\Domain\Model\Base\EventRepository;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Common\Instantiable;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Projection\ProjectionManager;

class GenericEntityEventRepository implements Instantiable, EventRepository
{
    /**
     * @return GenericEntityEventRepository
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * @param EntityReference $aggregateReference
     * @return EventSelector
     */
    public static function createEventSelector(
        EntityReference $aggregateReference
    ) {
        $streamName = Common::STREAM_PREFIX_META . '/' . (string)$aggregateReference;
        return EventSelector::instance()->setStreamName($streamName);
    }

    /**
     * @param EntityReference $aggregateReference
     * @param string $eventId
     * @param string $type
     * @return GenericEntity
     */
    public function findByAggregateReference(
        EntityReference $aggregateReference,
        string $eventId = '',
        string $type = Saga::EVENT_EXCLUDING
    ) {
        $eventSelector = static::createEventSelector($aggregateReference);
        $saga = Saga::create($eventSelector)->constraint($eventId, $type);
        return GenericEntity::buildFromSaga($saga);
    }

    /**
     * @param GenericEntity $genericEntity
     */
    public function commit(GenericEntity $genericEntity)
    {
        foreach ($genericEntity->getRecordedEvents() as $event) {
            $this->commitEvent($event);
        }

        ProjectionManager::provide()->projectEvents(
            $genericEntity->getRecordedEvents()
        );
        $genericEntity->purgeRecordedEvents();
    }

    /**
     * @param BaseEvent|AbstractEvent $event
     */
    public function commitEvent(BaseEvent $event)
    {
        if ($event instanceof OriginatedEntityEvent) {
            $streamName = Common::STREAM_PREFIX_META_ORIGIN;
        } else {
            $streamName = Common::STREAM_PREFIX_META
                . '/' . (string)$event->getAggregateReference();
        }

        $eventSelector = EventSelector::instance()
            ->setEvents([get_class($event)])
            ->setStreamName($streamName);

        EventStorePool::provide()
            ->getAllFor($eventSelector)
            ->attach($streamName, $event);
    }
}
