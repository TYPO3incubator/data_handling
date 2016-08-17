<?php
namespace TYPO3\CMS\DataHandling\Core\Compatibility\Database;

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
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Generic;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\EntityReference;
use TYPO3\CMS\DataHandling\Core\EventSourcing\EventManager;
use TYPO3\CMS\DataHandling\Core\Service\GenericService;

class QueryBuilderInterceptor extends QueryBuilder
{
    public function execute()
    {
        $tableName = $this->determineTableName();

        if ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::INSERT) {
            if (!GenericService::instance()->isSystemInternal($tableName)) {
                $reference = EntityReference::create($tableName);
                $values = $this->determineValues();
                $this->emitRecordEvent(
                    Generic\CreatedEvent::create($reference)
                );
                $this->emitRecordEvent(
                    Generic\ChangedEvent::create($reference, $values)
                );
                $this->set(Common::FIELD_UUID, $reference->getUuid());
            }
        }

        if ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::UPDATE) {
            if (!GenericService::instance()->isSystemInternal($tableName)) {
                foreach ($this->determineReferences() as $reference) {
                    $values = $this->determineValues();
                    if (!GenericService::instance()->isDeleteCommand($tableName, $values)) {
                        $this->emitRecordEvent(
                            Generic\ChangedEvent::create($reference, $values)
                        );
                    } else {
                        $this->emitRecordEvent(
                            Generic\DeletedEvent::create($reference)
                        );
                    }
                }
            }
        }

        if ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::DELETE) {
            if (!GenericService::instance()->isSystemInternal($tableName)) {
                foreach ($this->determineReferences() as $reference) {
                    $this->emitRecordEvent(
                        Generic\PurgedEvent::create($reference)
                    );
                }
            }
        }

        return parent::execute();
    }

    protected function emitRecordEvent(Generic\AbstractEvent $event)
    {
        $metadata = ['trigger' => QueryBuilderInterceptor::class];

        if ($event->getMetadata() === null) {
            $event->setMetadata($metadata);
        } else {
            $event->setMetadata(
                array_merge($event->getMetadata(), $metadata)
            );
        }

        EventManager::provide()->manage($event);
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

    /**
     * @return EntityReference[]
     */
    protected function determineReferences(): array
    {
        $references = [];
        $tableName = $this->determineTableName();
        $where = $this->concreteQueryBuilder->getQueryPart('where');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll();
        $statement = $queryBuilder
            ->select('uid', Common::FIELD_UUID, Common::FIELD_REVISION)
            ->from($tableName)
            ->where($where)
            ->execute();

        while ($row = $statement->fetch()) {
            $references[] = EntityReference::fromRecord($tableName, $row);
        }

        return $references;
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
