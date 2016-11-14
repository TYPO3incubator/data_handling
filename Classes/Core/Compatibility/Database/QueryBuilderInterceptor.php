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

use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Meta\EventSourcingMap;

class QueryBuilderInterceptor extends QueryBuilder
{
    public function execute()
    {
        $tableName = $this->determineTableName();

        if ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::INSERT) {
            if (EventSourcingMap::provide()->shallListen($tableName)) {
                ConnectionTranslator::instance()->createEntity(
                    EntityReference::create($tableName),
                    $this->determineValues()
                );
            }
        }

        if ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::UPDATE) {
            if (EventSourcingMap::provide()->shallListen($tableName)) {
                foreach ($this->determineReferences() as $reference) {
                    ConnectionTranslator::instance()->modifyEntity(
                        $reference,
                        $this->determineValues()
                    );
                }
            }
        }

        if ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::DELETE) {
            if (EventSourcingMap::provide()->shallListen($tableName)) {
                foreach ($this->determineReferences() as $reference) {
                    ConnectionTranslator::instance()->purgeEntity(
                        $reference
                    );
                }
            }
        }

        return parent::execute();
    }

    /**
     * @param string $tableName
     * @return string
     */
    private function sanitizeReference(string $tableName): string
    {
        return preg_replace('#^(`|\'|")([^`\'"]+)(\1)$#', '$2', $tableName);
    }

    private function determineTableName(): string
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
    private function determineReferences(): array
    {
        $references = [];

        $tableName = $this->determineTableName();
        $where = $this->concreteQueryBuilder->getQueryPart('where');

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        $statement = $queryBuilder
            ->select('uid', Common::FIELD_UUID, Common::FIELD_REVISION)
            ->from($tableName)
            ->where($where)
            ->execute();

        if ($statement === false) {
            return $references;
        }

        foreach ($statement as $row) {
            $references[] = EntityReference::fromRecord($tableName, $row);
        }

        return $references;
    }

    /**
     * @return array|null
     */
    private function determineValues()
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
