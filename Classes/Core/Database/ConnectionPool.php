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
use TYPO3\CMS\DataHandling\Core\Compatibility\Database\ConnectionInterceptor;
use TYPO3\CMS\DataHandling\Core\Context\ProjectionContext;
use TYPO3\CMS\DataHandling\Core\Service\FileSystemService;
use TYPO3\CMS\DataHandling\Core\Service\GenericService;

class ConnectionPool extends \TYPO3\CMS\Core\Database\ConnectionPool
{
    const ORIGIN_CONNECTION_NAME = 'Origin';

    /**
     * @var array
     */
    private static $mapping = [];

    /**
     * @var bool
     */
    private static $originAsDefault = false;

    /**
     * @param string $sourceName
     * @param string|null $targetName
     */
    public static function map(string $sourceName, string $targetName = null)
    {
        if ($targetName === null) {
            unset(static::$mapping[$sourceName]);
        } else {
            static::$mapping[$sourceName] = $targetName;
        }
    }

    public static function originAsDefault(bool $originAsDefault)
    {
        static::$originAsDefault = $originAsDefault;
    }

    /**
     * @return ConnectionPool
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(static::class);
    }

    /**
     * @param string $tableName
     * @return Connection
     */
    public function getConnectionForTable(string $tableName): Connection
    {
        if (GenericService::instance()->isSystemInternal($tableName)) {
            return $this->getOriginConnection();
        }

        return parent::getConnectionForTable($tableName);
    }

    /**
     * @param string $connectionName
     * @return Connection
     */
    public function getConnectionByName(string $connectionName): Connection
    {
        if (
            static::$originAsDefault
            && $connectionName === static::DEFAULT_CONNECTION_NAME
        ) {
            $connectionName = static::ORIGIN_CONNECTION_NAME;
        } elseif (isset(static::$mapping[$connectionName])) {
            $connectionName = static::$mapping[$connectionName];
        }

        return parent::getConnectionByName($connectionName);
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
    public function provideLocalStorageConnection(string $name)
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
                // 'wrapperClass' => ConnectionInterceptor::class
            ];

            if (!file_exists($filePath)) {
                LocalStorage::instance()->initialize($connectionName);
                GeneralUtility::fixPermissions(dirname($filePath));
                GeneralUtility::fixPermissions($filePath);
                var_dump(file_exists($filePath));
                var_dump($filePath);
            }
        }

        return $this->getConnectionByName($connectionName);
    }

    /**
     * @param string $name
     */
    public function setLocalStorageAsDefault(string $name)
    {
        $connectionName = 'LocalStorage::' . $name;
        $this->provideLocalStorageConnection($name);

        static::map(static::DEFAULT_CONNECTION_NAME, $connectionName);
    }

    /**
     * @param string $name
     */
    public function reinitializeLocalStorage(string $name)
    {
        $connectionName = 'LocalStorage::' . $name;
        $this->provideLocalStorageConnection($name);

        LocalStorage::instance()->purge($connectionName);
        LocalStorage::instance()->initialize($connectionName);
    }
}
