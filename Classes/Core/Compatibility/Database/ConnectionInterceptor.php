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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;

class ConnectionInterceptor extends Connection
{
    /**
     * @return QueryBuilderInterceptor|QueryBuilder
     * @see https://wiki.php.net/rfc/return_types
     */
    public function createQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(QueryBuilderInterceptor::class, $this);
    }

    /**
     * @param string $tableName
     * @param array $data
     * @param array $types
     * @return int
     */
    public function insert($tableName, array $data, array $types = []): int
    {
        ConnectionTranslator::instance()->createEntity(
            EntityReference::create($tableName),
            $data
        );
        return parent::insert($tableName, $data, $types);
    }

    /**
     * @param string $tableName
     * @param array $data
     * @param array $columns
     * @param array $types
     * @return int
     */
    public function bulkInsert(string $tableName, array $data, array $columns = [], array $types = []): int
    {
        foreach ($data as $singleData) {
            $entityData = array_combine($columns, $singleData);
            ConnectionTranslator::instance()->createEntity(
                EntityReference::create($tableName),
                $entityData
            );
        }
        return parent::bulkInsert($tableName, $data, $columns, $types);
    }

    /**
     * @param string $tableName
     * @param array $data
     * @param array $identifier
     * @param array $types
     * @return int
     */
    public function update($tableName, array $data, array $identifier, array $types = []): int
    {
        foreach ($this->determineReferences($tableName, $identifier) as $reference) {
            ConnectionTranslator::instance()->modifyEntity(
                $reference,
                $data
            );
        }
        return parent::update($tableName, $data, $identifier, $types);
    }

    /**
     * @param string $tableName
     * @param array $identifier
     * @param array $types
     * @return int
     */
    public function delete($tableName, array $identifier, array $types = []): int
    {
        foreach ($this->determineReferences($tableName, $identifier) as $reference) {
            ConnectionTranslator::instance()->purgeEntity(
                $reference
            );
        }
        return parent::delete($tableName, $identifier, $types);
    }

    /**
     * @param string $tableName
     * @param bool $cascade
     * @return int
     */
    public function truncate(string $tableName, bool $cascade = false): int
    {
        foreach ($this->determineReferences($tableName, []) as $reference) {
            ConnectionTranslator::instance()->purgeEntity(
                $reference
            );
        }
        return parent::truncate($tableName, $cascade);
    }

    /**
     * @param string $tableName
     * @param array $identifier
     * @return EntityReference[]
     */
    private function determineReferences(string $tableName, array $identifier)
    {
        $references = [];

        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        foreach ($identifier as $propertyName => $propertyName) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $propertyName,
                    $queryBuilder->createNamedParameter($propertyName)
                )
            );
        }

        $statement = $queryBuilder
            ->select('uid', Common::FIELD_UUID, Common::FIELD_REVISION)
            ->from($tableName)
            ->execute();

        if ($statement === false) {
            return $references;
        }

        foreach ($statement as $row) {
            $references[] = EntityReference::fromArray($row);
        }

        return $references;
    }
}
