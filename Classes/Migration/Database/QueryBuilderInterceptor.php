<?php
namespace TYPO3\CMS\DataHandling\Migration\Database;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Event\Record\AbstractEvent;

class QueryBuilderInterceptor extends QueryBuilder
{
    public function execute()
    {
        $tableName = $this->determineTableName();

        if ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::INSERT) {
            if (!EventEmitter::isSystemInternal($tableName)) {
                $values = $this->determineValues();
                $event = EventFactory::getInstance()->createCreatedEvent($tableName, $values);
                $this->emitRecordEvent($event);
            }
        }

        if ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::UPDATE) {
            if (!EventEmitter::isSystemInternal($tableName)) {
                $identifier = $this->determineIdentifier();
                if (!empty($identifier)) {
                    $values = $this->determineValues();
                    if (!EventEmitter::isDeleteCommand($tableName, $values)) {
                        $event = EventFactory::getInstance()->createChangedEvent($tableName, $values, $identifier);
                    } else {
                        $event = EventFactory::getInstance()->createDeletedEvent($tableName, $values, $identifier);
                    }
                    $this->emitRecordEvent($event);
                }
            }
        }

        if ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::DELETE) {
            if (!EventEmitter::isSystemInternal($tableName)) {
                $identifier = $this->determineIdentifier();
                if (!empty($identifier)) {
                    $event = EventFactory::getInstance()->createPurgeEvent($tableName, $identifier);
                    $this->emitRecordEvent($event);
                }
            }
        }

        return parent::execute();
    }

    protected function emitRecordEvent(AbstractEvent $event)
    {
        $metadata = ['trigger' => QueryBuilderInterceptor::class];

        if ($event->getMetadata() === null) {
            $event->setMetadata($metadata);
        } else {
            $event->setMetadata(
                array_merge($event->getMetadata(), $metadata)
            );
        }

        EventEmitter::getInstance()->emitRecordEvent($event);
    }

    /**
     * @param string $tableName
     * @return string
     */
    protected function sanitizeReference(string $tableName): string
    {
        return preg_replace('#^(`|\'|")([^`\'"]+)(\1)$#', '$2', $tableName);
    }

    protected function determineTableName(): string
    {
        $from = $this->concreteQueryBuilder->getQueryPart('from');
        if (isset($from[0]['table'])) {
            $tableName = $from[0]['table'];
        } else {
            $tableName = $from['table'];
        }
        $tableName = $this->sanitizeReference($tableName);
        return $tableName;
    }

    protected function determineIdentifier()
    {
        $tableName = $this->determineTableName();
        $where = $this->concreteQueryBuilder->getQueryPart('where');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll();
        $rows = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where($where)
            ->execute()
            ->fetchAll();

        if (count($rows) !== 1 || empty($rows[0]['uid'])) {
            return null;
        }
        return (int)$rows[0]['uid'];
    }

    /**
     * @return array|null
     */
    protected function determineValues()
    {
        $values = $this->concreteQueryBuilder->getQueryPart('values');

        if (!empty($values)) {
            return $values;
        }

        $set = $this->concreteQueryBuilder->getQueryPart('set');

        if (!empty($set)) {
            $values = [];
            foreach ($set as $value) {
                if (preg_match('#^([^\s]+)\s*=\s*:(.+)$#', $value, $matches)) {
                    $values[$this->sanitizeReference($matches[1])]
                        = $this->concreteQueryBuilder->getParameter($matches[2]);
                }
            }
            return $values;
        }

        return null;
    }
}
