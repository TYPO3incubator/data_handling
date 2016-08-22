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

use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelector;

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
    public function attach(string $streamName, AbstractEvent $event, array $categories = []): bool
    {
        $rawEvent = [
            'event_stream' => $streamName,
            'event_categories' => (!empty($categories) ? implode(',', $categories) : null),
            'event_id' => $event->getEventId(),
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
     * @param string $streamName
     * @param array $categories
     * @return SqlDriverIterator
     */
    public function stream(string $streamName, array $categories = [])
    {
        if (empty($streamName) && empty($categories)) {
            throw new \RuntimeException('No selection criteria given', 1471441756);
        }

        $predicates = [];
        $queryBuilder = ConnectionPool::instance()->getOriginQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        if (!empty($streamName)) {
            $predicates[] = $this->createMatchesWildcardExpression($queryBuilder, 'event_stream', $streamName);
        }
        if (!empty($categories)) {
            $predicates[] = $this->createFindInListExpression($queryBuilder, 'event_categories', $categories);
        }
        // @todo Add event name selection

        $statement = $queryBuilder
            ->select('*')
            ->from('sys_event_store')
            ->where(...$predicates)
            ->execute();

        return SqlDriverIterator::create($statement);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $fieldName
     * @param string $needle
     * @return string
     */
    protected function createMatchesWildcardExpression(QueryBuilder $queryBuilder, string $fieldName, string $needle)
    {
        $comparableNeedle = EventSelector::getComparablePart($needle);
        if ($needle === $comparableNeedle) {
            $namedParameter = $queryBuilder->createNamedParameter($needle);
            $expression = $queryBuilder->expr()->eq($fieldName, $namedParameter);
        } else {
            $escapedComparableNeedle = $queryBuilder->escapeLikeWildcards($comparableNeedle);
            $expression = $queryBuilder->expr()->like($fieldName, $queryBuilder->quote($escapedComparableNeedle . '%'));
        }
        return $expression;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $fieldName
     * @param array $needles
     * @return CompositeExpression
     */
    protected function createFindInListExpression(QueryBuilder $queryBuilder, string $fieldName, array $needles)
    {
        $expression = $queryBuilder->expr()->orX();
        foreach ($needles as $needle) {
            $namedParameter = $queryBuilder->createNamedParameter($needle);
            $escapedNeedle = $queryBuilder->escapeLikeWildcards($needle);
            $expression->addMultiple([
                $queryBuilder->expr()->eq($fieldName, $namedParameter),
                $queryBuilder->expr()->like($fieldName, $queryBuilder->quote($escapedNeedle . ',%')),
                $queryBuilder->expr()->like($fieldName, $queryBuilder->quote('%,' . $escapedNeedle)),
                $queryBuilder->expr()->like($fieldName, $queryBuilder->quote('%,' . $escapedNeedle . ',%')),
            ]);
        }
        return $expression;
    }
}
