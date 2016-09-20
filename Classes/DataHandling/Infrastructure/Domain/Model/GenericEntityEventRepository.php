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

use Ramsey\Uuid\UuidInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Model\GenericEntity;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Saga;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelector;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventStorePool;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\BaseEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Repository\EventRepository;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;
use TYPO3\CMS\DataHandling\Core\Framework\Process\Projection\ProjectionManager;

class GenericEntityEventRepository implements Instantiable, EventRepository
{
    /**
     * @param string $aggregateType
     * @return GenericEntityEventRepository
     */
    public static function create(string $aggregateType)
    {
        return GeneralUtility::makeInstance(static::class, $aggregateType);
    }

    /**
     * @return GenericEntityEventRepository
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(static::class);
    }

    /**
     * @param string $tableName
     */
    public function __construct(string $tableName)
    {
        $this->aggregateType = $tableName;
    }

    /**
     * @param EntityReference $aggregateReference
     * @param string $eventId
     * @param string $type
     * @return GenericEntity
     */
    public function findByAggregateReference(EntityReference $aggregateReference, string $eventId = '', string $type = Saga::EVENT_EXCLUDING)
    {
        $streamName = Common::STREAM_PREFIX_META . '/' . (string)$aggregateReference;
        $eventSelector = EventSelector::instance()->setStreamName($streamName);

        return Saga::instance()
            ->constraint($eventId, $type)
            ->tell(GenericEntity::instance(), $eventSelector);
    }

    /**
     * @param GenericEntity $genericEntity
     */
    public function add(GenericEntity $genericEntity)
    {
        foreach ($genericEntity->getRecordedEvents() as $event) {
            $this->addEvent($event);
        }

        ProjectionManager::provide()->projectEvents(
            $genericEntity->getRecordedEvents()
        );
        $genericEntity->purgeRecordedEvents();
    }

    /**
     * @param BaseEvent|AbstractEvent $event
     */
    public function addEvent(BaseEvent $event)
    {
        $streamName = Common::STREAM_PREFIX_META
            . '/' . (string)$event->getAggregateReference();

        $eventSelector = EventSelector::instance()
            ->setEvents([get_class($event)])
            ->setStreamName($streamName);

        EventStorePool::provide()
            ->getAllFor($eventSelector)
            ->attach($streamName, $event);
    }
}
