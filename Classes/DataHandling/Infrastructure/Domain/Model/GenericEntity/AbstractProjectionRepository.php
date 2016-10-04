<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntity;

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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\DataHandling\Serializer\RelationSerializer;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\GenericEntity;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\PropertyReference;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\Sequence\RelationSequence;
use TYPO3\CMS\DataHandling\Core\Service\MetaModelService;

abstract class AbstractProjectionRepository implements ProjectionRepository
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @param int $uid
     * @return GenericEntity
     */
    public function findOneByUid(int $uid)
    {
        return $this->findOneByIdentifiers([
            'uid' => (int)$uid
        ]);
    }

    /**
     * @param UuidInterface $uuid
     * @return GenericEntity
     */
    public function findOneByUuid(UuidInterface $uuid)
    {
        return $this->findOneByIdentifiers([
            Common::FIELD_UUID => $uuid->toString()
        ]);
    }

    /**
     * @param int $identifier
     * @return array|bool
     */
    public function findRawByUid(int $identifier)
    {
        return $this->findRawByIdentifiers([
            'uid' => (int)$identifier
        ]);
    }

    /**
     * @param string $identifier
     * @return array|bool
     */
    public function findRawByUuid(string $identifier)
    {
        return $this->findRawByIdentifiers([
            Common::FIELD_UUID => $identifier
        ]);
    }

    /**
     * @param array $data
     */
    public function add(array $data)
    {
        $this->connection->beginTransaction();

        $this->connection->insert(
            $this->tableName,
            $this->sanitizeAddData($data)
        );

        try {
            $this->connection->commit();
        } catch (\RuntimeException $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    /**
     * @param string $identifier
     * @param array $data
     */
    public function update(string $identifier, array $data)
    {
        $this->connection->beginTransaction();

        $this->connection->update(
            $this->tableName,
            $this->sanitizeUpdateData($data, $identifier),
            [Common::FIELD_UUID => $identifier]
        );

        try {
            $this->connection->commit();
        } catch (\RuntimeException $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    /**
     * @param string $identifier
     */
    public function remove(string $identifier)
    {
        $deletedFieldName = MetaModelService::instance()
            ->getDeletedFieldName($this->tableName);

        if ($deletedFieldName !== null) {
            $this->update(
                $identifier,
                [$deletedFieldName => 1]
            );
        } else {
            $this->purge($identifier);
        }
    }

    /**
     * @param string $identifier
     */
    public function purge(string $identifier)
    {
        $this->connection->delete(
            $this->tableName,
            [Common::FIELD_UUID => $identifier]
        );
    }

    /**
     * @param string $identifier
     * @param PropertyReference $relationReference
     */
    public function attachRelation(string $identifier, PropertyReference $relationReference)
    {
        $rawValues = $this->findRawByUuid($identifier);
        $propertyValue = ($rawValues[$relationReference->getName()] ?? '');

        $entityReference = EntityReference::fromRecord(
            $this->tableName,
            $rawValues
        );

        $result = $this->createRelationSerializer()->attachRelation(
            $entityReference,
            $relationReference,
            $propertyValue
        );

        if ($result !== null) {
            $this->update(
                $identifier,
                [$relationReference->getName() => $result]
            );
        }
    }

    /**
     * @param string $identifier
     * @param PropertyReference $relationReference
     */
    public function removeRelation(string $identifier, PropertyReference $relationReference)
    {
        $rawValues = $this->findRawByUuid($identifier);
        $propertyValue = ($rawValues[$relationReference->getName()] ?? '');

        $entityReference = EntityReference::fromRecord(
            $this->tableName,
            $rawValues
        );

        $result = $this->createRelationSerializer()->removeRelation(
            $entityReference,
            $relationReference,
            $propertyValue
        );

        if ($result !== null) {
            $this->update(
                $identifier,
                [$relationReference->getName() => $result]
            );
        }
    }

    /**
     * @param string $identifier
     * @param RelationSequence $sequence
     */
    public function orderRelations(string $identifier, RelationSequence $sequence)
    {
        $rawValues = $this->findRawByUuid($identifier);
        $propertyValue = ($rawValues[$sequence->getName()] ?? '');

        $entityReference = EntityReference::fromRecord(
            $this->tableName,
            $rawValues
        );

        $result = $this->createRelationSerializer()->orderRelations(
            $entityReference,
            $sequence,
            $propertyValue
        );

        if ($result !== null) {
            $this->update(
                $identifier,
                [$sequence->getName() => $result]
            );
        }
    }

    /**
     * @param array $data
     * @return array
     */
    protected function sanitizeData(array $data)
    {
        $timestampFieldName = MetaModelService::instance()
            ->getTimestampFieldName($this->tableName);

        if ($timestampFieldName !== null) {
            $data[$timestampFieldName] = $GLOBALS['EXEC_TIME'];
        }

        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function sanitizeAddData(array $data)
    {
        $data = $this->sanitizeData($data);
        $data[Common::FIELD_REVISION] = 0;

        $sortingField = MetaModelService::instance()
            ->getSortingField($this->tableName);
        if ($sortingField !== null && isset($data['pid'])) {
            $queryBuilder = $this->createQueryBuilder()->setMaxResults(1);
            $queryBuilder->selectLiteral(
                'MAX(' . $queryBuilder->quoteIdentifier($sortingField) . ')'
            );
            $this->applyQueryBuildPredicates(
                $queryBuilder,
                ['pid' => (int)$data['pid']]
            );
            $sortingValue = $queryBuilder->execute()->fetchColumn(0);
            $data[$sortingField] = (int)$sortingValue + 1;
        }

        return $data;
    }

    /**
     * @param array $data
     * @param string $identifier
     * @return array
     */
    protected function sanitizeUpdateData(array $data, string $identifier)
    {
        $data = $this->sanitizeData($data);
        $revision = $this->fetchRevisionForIdentifier($identifier);
        $data[Common::FIELD_REVISION] = ($revision ?? -1) + 1;

        if (isset($data[Common::FIELD_UUID])) {
            unset($data[Common::FIELD_UUID]);
        }

        return $data;
    }

    /**
     * @param array $data
     * @return GenericEntity
     */
    private function buildOne(array $data)
    {
        if (empty($data)) {
            return null;
        }

        return GenericEntity::buildFromProjection(
            $this->connection,
            $this->tableName,
            $data
        );
    }

    /**
     * @param array $identifiers
     * @return GenericEntity
     */
    private function findOneByIdentifiers(array $identifiers)
    {
        return $this->buildOne(
            $this->findRawByIdentifiers($identifiers)
        );
    }

    /**
     * @param array $identifiers
     * @return array|bool
     */
    private function findRawByIdentifiers(array $identifiers)
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('*');
        $this->applyQueryBuildPredicates($queryBuilder, $identifiers);
        return $queryBuilder->setMaxResults(1)->execute()->fetch();
    }

    /**
     * @param string $identifier
     * @return bool|string
     */
    private function fetchRevisionForIdentifier(string $identifier)
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select(Common::FIELD_REVISION);
        $identifiers = [Common::FIELD_UUID => $identifier];
        $this->applyQueryBuildPredicates($queryBuilder, $identifiers);
        return $queryBuilder->setMaxResults(1)->execute()->fetchColumn(0);
    }

    /**
     * @return RelationSerializer
     */
    private function createRelationSerializer()
    {
        return RelationSerializer::create($this->connection);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $identifiers
     */
    private function applyQueryBuildPredicates(QueryBuilder $queryBuilder, array $identifiers)
    {
        $predicates = [];
        foreach ($identifiers as $propertyName => $propertyValue) {
            if (is_integer($propertyValue)) {
                $predicates[] = $queryBuilder->expr()->eq(
                    $propertyName,
                    (int)$propertyValue
                );
            } else {
                $predicates[] = $queryBuilder->expr()->eq(
                    $propertyName,
                    $queryBuilder->createNamedParameter($propertyValue)
                );
            }
        }
        $queryBuilder->where(...$predicates);
    }

    /**
     * @return QueryBuilder
     */
    private function createQueryBuilder()
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder->from($this->tableName);
    }
}
