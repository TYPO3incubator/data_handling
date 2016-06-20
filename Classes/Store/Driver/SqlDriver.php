<?php
namespace TYPO3\CMS\DataHandling\Store\Driver;

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

use Rhumsaa\Uuid\Uuid;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Store\EventSerializer;

class SqlDriver implements DriverInterface
{
    /**
     * @var EventSerializer
     */
    protected $eventSerializer;

    public function append(string $streamName, AbstractEvent $event)
    {
        $rawEvent = $event->toArray();
        $rawEvent['event_stream'] = $streamName;
        $rawEvent['event_id'] = Uuid::uuid4();

        $this->getQueryBuilder()
            ->insert('sys_event_store')
            ->values($rawEvent)
            ->execute();
    }

    public function open(string $eventStream)
    {

    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_event_store');
        $queryBuilder->getRestrictions()
            ->removeAll();

        return $queryBuilder;
    }

    /**
     * @return EventSerializer
     * @deprecated Currently not used
     */
    protected function getEventSerializer()
    {
        if (isset($this->eventSerializer)) {
            return $this->eventSerializer;
        }

        $this->eventSerializer = GeneralUtility::makeInstance(EventSerializer::class);
        return $this->eventSerializer;
    }
}
