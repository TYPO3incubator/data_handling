<?php
namespace TYPO3\CMS\DataHandling\Alternative\Database;

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

class ConnectionPool extends \TYPO3\CMS\Core\Database\ConnectionPool
{
    /**
     * @return ConnectionPool
     */
    public static function create()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }

    public function getDefaultConnection(): Connection
    {
        return $this->getConnectionByName(static::DEFAULT_CONNECTION_NAME);
    }

    public function getDefaultQueryBuilder(): QueryBuilder
    {
        return $this->getDefaultConnection()->createQueryBuilder();
    }

    public function getQueryBuilderForTable(string $tableName): QueryBuilder
    {
        return parent::getQueryBuilderForTable($tableName);
    }
}
