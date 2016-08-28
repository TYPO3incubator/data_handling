<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Repository\Meta;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Extbase\DomainObject\AbstractProjectableEntity;
use TYPO3\CMS\DataHandling\Extbase\Persistence\ProjectionRepository;

class GenericEntityProjectionRepository implements ProjectionRepository
{
    /**
     * @param string $aggregateType
     * @param Connection $connection
     * @return GenericEntityProjectionRepository
     */
    public static function create(string $aggregateType, Connection $connection = null)
    {
        return GeneralUtility::makeInstance(
            GenericEntityProjectionRepository::class,
            $aggregateType,
            $connection
        );
    }

    /**
     * @var string
     */
    protected $aggregateType;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param string $tableName
     * @param Connection $connection
     */
    public function __construct(string $tableName, Connection $connection = null)
    {
        $this->aggregateType = $tableName;
        $this->connection = ($connection ?? ConnectionPool::instance()->getOriginConnection());
    }

    public function makeProjectable(AbstractProjectableEntity $subject)
    {
        // TODO: Implement makeProjectable() method.
    }

    /**
     * @param UuidInterface $uuid
     * @return bool|array
     */
    public function findByUuid(UuidInterface $uuid)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $statement = $queryBuilder
            ->select('*')
            ->from($this->aggregateType)
            ->where(
                $queryBuilder->expr()->eq(
                    Common::FIELD_UUID,
                    $queryBuilder->createNamedParameter($uuid->toString())
                )
            )
            ->setMaxResults(1)
            ->execute();
        return $statement->fetch();
    }

    public function add($object)
    {
        // TODO: Implement add() method.
    }

    public function remove($object)
    {
        // TODO: Implement remove() method.
    }

    public function update($modifiedObject)
    {
        // TODO: Implement update() method.
    }

    public function findAll()
    {
        // TODO: Implement findAll() method.
    }

    public function countAll()
    {
        // TODO: Implement countAll() method.
    }

    public function removeAll()
    {
        // TODO: Implement removeAll() method.
    }

    public function findByUid($uid)
    {
        // TODO: Implement findByUid() method.
    }

    public function findByIdentifier($identifier)
    {
        // TODO: Implement findByIdentifier() method.
    }

    public function setDefaultOrderings(array $defaultOrderings)
    {
        // TODO: Implement setDefaultOrderings() method.
    }

    public function setDefaultQuerySettings(
        \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $defaultQuerySettings
    ) {
        // TODO: Implement setDefaultQuerySettings() method.
    }

    public function createQuery()
    {
        // TODO: Implement createQuery() method.
    }
}
