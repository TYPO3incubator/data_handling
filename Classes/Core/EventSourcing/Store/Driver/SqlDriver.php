<?php
namespace TYPO3\CMS\DataHandling\Core\EventSourcing\Store\Driver;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;

class SqlDriver implements DriverInterface
{
    const FORMAT_DATETIME = 'Y-m-d H:i:s.u';

    /**
     * @return SqlDriver
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(SqlDriver::class);
    }

    /**
     * @param string $streamName
     * @param AbstractEvent $event
     * @return bool
     */
    public function append(string $streamName, AbstractEvent $event): bool
    {
        $rawEvent = [
            'event_stream' => $streamName,
            'event_uuid' => $event->getUuid(),
            'event_name' => get_class($event),
            'event_date' => $event->getDate()->format(static::FORMAT_DATETIME),
            'data' => $event->exportData(),
            'metadata' => $event->getMetadata(),
        ];

        foreach ($rawEvent as $propertyName => $propertyValue) {
            if ($propertyValue === null) {
                unset($rawEvent[$propertyName]);
            } elseif (is_array($propertyValue)) {
                $rawEvent[$propertyName] = json_encode($propertyValue);
            }
        }

        $result = ConnectionPool::instance()
            ->getOriginConnection()
            ->insert('sys_event_store', $rawEvent);

        return ($result > 0);
    }

    /**
     * @param string $eventStream
     * @return SqlDriverIterator
     */
    public function open(string $eventStream)
    {
        $queryBuilder = ConnectionPool::instance()->getOriginQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->from('sys_event_store')
            ->where(
                $queryBuilder->expr()->eq(
                    'event_stream',
                    $queryBuilder->createNamedParameter($eventStream)
                )
            );

        return SqlDriverIterator::create($queryBuilder->execute());
    }
}
