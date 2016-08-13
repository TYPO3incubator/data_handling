<?php
namespace TYPO3\CMS\DataHandling\Core\Database;

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
use TYPO3\CMS\DataHandling\Core\Service\FileSystemService;

class ConnectionPool extends \TYPO3\CMS\Core\Database\ConnectionPool
{
    const ORIGIN_CONNECTION_NAME = 'Origin';

    /**
     * @return ConnectionPool
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }

    /**
     * @return Connection
     */
    public function getOriginConnection(): Connection
    {
        return $this->getConnectionByName(static::ORIGIN_CONNECTION_NAME);
    }

    /**
     * @return QueryBuilder
     */
    public function getOriginQueryBuilder(): QueryBuilder
    {
        return $this->getOriginConnection()->createQueryBuilder();
    }

    /**
     * @param string $name
     * @return Connection
     */
    public function provideLocalStorageConnection(string $name): Connection
    {
        $connectionName = 'LocalStorage::' . $name;

        if (!isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][$connectionName])) {
            $filePath = GeneralUtility::getFileAbsFileName(
                'typo3temp/var/LocalStorage/' . $name . '.sqlite'
            );
            FileSystemService::instance()->ensureHtAccessDenyFile(
                dirname($filePath)
            );

            $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][$connectionName] = [
                'driver' => 'pdo_sqlite',
                'path' => $filePath,
                'memory' => false,
            ];

            LocalStorage::instance()->initialize(
                $this->getConnectionByName($connectionName)
            );
        }

        return $this->getConnectionByName($connectionName);
    }
}
