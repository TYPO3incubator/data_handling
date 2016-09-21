<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model;

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
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Event;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Repository\ProjectionRepository;

class TableVersionProjectionRepository implements ProjectionRepository
{
    const TABLE_NAME = 'projection_table_version';

    public static function instance()
    {
        return new static();
    }

    /**
     * @var Connection
     */
    private $connection;

    public function __construct()
    {
        $this->connection = ConnectionPool::instance()->getOriginConnection();
    }

    /**
     * @param int $workspaceId
     * @param int $pageId
     * @return null|int
     */
    public function findByWorkspaceAndPage(int $workspaceId, int $pageId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $predicates = [
            $queryBuilder->expr()->eq(
                'workspace_id',
                (int)$workspaceId
            ),
            $queryBuilder->expr()->eq(
                'page_id',
                (int)$pageId
            ),
        ];

        $statement = $queryBuilder
            ->select('SUM(version_count)')
            ->from(static::TABLE_NAME)
            ->where(...$predicates)
            ->execute();

        if ($statement->rowCount() === 0) {
            return null;
        }

        return (int)$statement->fetchColumn(0);
    }

    /**
     * @param int $workspaceId
     * @param int $pageId
     * @param string $tableName
     * @return null|int
     */
    public function findByWorkspaceAndPageAndTable(int $workspaceId, int $pageId, string $tableName)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $predicates = [
            $queryBuilder->expr()->eq(
                'workspace_id',
                (int)$workspaceId
            ),
            $queryBuilder->expr()->eq(
                'page_id',
                (int)$pageId
            ),
            $queryBuilder->expr()->eq(
                'table_name',
                $queryBuilder->createNamedParameter($tableName)
            ),
        ];

        $statement = $queryBuilder
            ->select('version_count')
            ->from(static::TABLE_NAME)
            ->where(...$predicates)
            ->execute();

        if ($statement->rowCount() === 0) {
            return null;
        }

        return (int)$statement->fetchColumn(0);
    }

    public function increment(int $workspaceId, int $pageId, string $tableName)
    {
        $this->connection->beginTransaction();

        try {
            $versionCount = $this->findByWorkspaceAndPageAndTable(
                $workspaceId,
                $pageId,
                $tableName
            );
            if ($versionCount === null) {
                $this->connection->insert(
                    static::TABLE_NAME,
                    [
                        'workspace_id' => $workspaceId,
                        'page_id' => $pageId,
                        'table_name' => $tableName,
                        'version_count' => 1,
                    ]
                );
            } else {
                $this->connection->update(
                    static::TABLE_NAME,
                    [
                        'version_count' => $versionCount + 1,
                    ],
                    [
                        'workspace_id' => $workspaceId,
                        'page_id' => $pageId,
                        'table_name' => $tableName,
                    ]
                );
            }
            $this->connection->commit();
        } catch (\Exception $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function decrement(int $workspaceId, int $pageId, string $tableName)
    {
        try {
            $versionCount = $this->findByWorkspaceAndPageAndTable(
                $workspaceId,
                $pageId,
                $tableName
            );
            if ($versionCount > 1) {
                $this->connection->update(
                    static::TABLE_NAME,
                    [
                        'version_count' => $versionCount - 1,
                    ],
                    [
                        'workspace_id' => $workspaceId,
                        'page_id' => $pageId,
                        'table_name' => $tableName,
                    ]
                );
            } elseif ($versionCount === 1) {
                $this->connection->delete(
                    static::TABLE_NAME,
                    [
                        'workspace_id' => $workspaceId,
                        'page_id' => $pageId,
                        'table_name' => $tableName,
                    ]
                );
            }
            $this->connection->commit();
        } catch (\Exception $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function purgeAll()
    {
        $this->connection->truncate(static::TABLE_NAME);
    }

    /**
     * @param array $data
     */
    public function add(array $data)
    {
        throw new \BadFunctionCallException(
            'Use either increment or decrement'
        );
    }

    /**
     * @param string $identifier
     * @param array $data
     */
    public function update(string $identifier, array $data)
    {
        throw new \BadFunctionCallException(
            'Use either increment or decrement'
        );
    }
}
