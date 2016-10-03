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
        return parent::delete($tableName, $identifier, $types);
    }

    /**
     * @param string $tableName
     * @param bool $cascade
     * @return int
     */
    public function truncate(string $tableName, bool $cascade = false): int
    {
        return parent::truncate($tableName, $cascade);
    }


}
