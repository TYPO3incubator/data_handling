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
     * @param string[] $categories
     * @return bool
     */
    public function append(string $streamName, AbstractEvent $event, array $categories = []): bool
    {
        $rawEvent = [
            'event_stream' => $streamName,
            'event_categories' => (!empty($categories) ? implode(',', $categories) : null),
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
     * @param array $categories
     * @return SqlDriverIterator
     */
    public function open(string $eventStream, array $categories = [])
    {
        if (empty($eventStream) && empty($categories)) {
            throw new \RuntimeException('No selection criteria given', 1471441756);
        }

        $predicates = [];
        $queryBuilder = ConnectionPool::instance()->getOriginQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        if (!empty($eventStream)) {
            $namedParameter = $queryBuilder->createNamedParameter($eventStream);
            $predicates[] = $queryBuilder->expr()->eq('event_stream', $namedParameter);
        }
        if (!empty($categories)) {
            $expression = $queryBuilder->expr()->orX();
            foreach ($categories as $category) {
                $namedParameter = $queryBuilder->createNamedParameter($category);
                $escapedCategory = $queryBuilder->escapeLikeWildcards($category);
                $expression->addMultiple([
                    $queryBuilder->expr()->eq('event_categories', $namedParameter),
                    $queryBuilder->expr()->like('event_categories', $queryBuilder->quote($escapedCategory . ',%')),
                    $queryBuilder->expr()->like('event_categories', $queryBuilder->quote('%,' . $escapedCategory)),
                    $queryBuilder->expr()->like('event_categories', $queryBuilder->quote('%,' . $escapedCategory . ',%')),
                ]);
            }
            $predicates[] = $expression;
        }

        $statement = $queryBuilder
            ->select('*')
            ->from('sys_event_store')
            ->where(...$predicates)
            ->execute();

        return SqlDriverIterator::create($statement);
    }
}
