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
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EventSourcingMap;
use TYPO3\CMS\DataHandling\Core\Service\DatabaseService;
use TYPO3\CMS\DataHandling\Core\Service\FileSystemService;

/**
 * Extends to regular connection pool by intercepting the default
 * connection and providing a specific local storage instead. Besides
 * that, origin connection (prior default connection) can be accessed.
 */
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
            unset(self::$mapping[$sourceName]);
        } else {
            self::$mapping[$sourceName] = $targetName;
        }
    }

    /**
     * Defines whether to use origin as default.
     *
     * This might be useful if setting up test-cases and install
     * tool modifications - thus, local storage can be overruled.
     *
     * @param bool $originAsDefault
     * @return bool
     */
    public static function originAsDefault(bool $originAsDefault)
    {
        $currentValue = self::$originAsDefault;
        self::$originAsDefault = $originAsDefault;
        return $currentValue;
    }

    /**
     * @return ConnectionPool
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(static::class);
    }

    /**
     * Creates a connection object based on the specified table name.
     *
     * @param string $tableName
     * @return Connection
     */
    public function getConnectionForTable(string $tableName): Connection
    {
        // use local stroage for
        // + tables that shall be projected
        // + tables that belong to caching framework
        $useLocalStorage = (
            EventSourcingMap::provide()->shallProject($tableName)
            || DatabaseService::instance()->isCacheTable($tableName)
        );

        if (!$useLocalStorage) {
            return $this->getOriginConnection();
        }

        return parent::getConnectionForTable($tableName);
    }

    /**
     * Creates a connection object based on the specified identifier.
     *
     * @param string $connectionName
     * @return Connection
     */
    public function getConnectionByName(string $connectionName): Connection
    {
        if (
            self::$originAsDefault
            && $connectionName === static::DEFAULT_CONNECTION_NAME
        ) {
            $connectionName = static::ORIGIN_CONNECTION_NAME;
        } elseif (isset(self::$mapping[$connectionName])) {
            $connectionName = self::$mapping[$connectionName];
        }

        return parent::getConnectionByName($connectionName);
    }

    /**
     * Gets the origin connection, previously know as default connection.
     *
     * @return Connection
     */
    public function getOriginConnection(): Connection
    {
        return $this->getConnectionByName(static::ORIGIN_CONNECTION_NAME);
    }

    /**
     * Gets a new query build instance for origin connection.
     *
     * @return QueryBuilder
     */
    public function getOriginQueryBuilder(): QueryBuilder
    {
        return $this->getOriginConnection()->createQueryBuilder();
    }

    /**
     * Provides a local storage connection for context based projections.
     *
     * @param string $name Name of the LocalStorage (e.g. 'workspace-0')
     * @param bool $write Whether to allow write access (e.g. for projection)
     * @return Connection
     */
    public function provideLocalStorageConnection(string $name, bool $write = false)
    {
        $connectionName = $this->getLocalStorageName($name, $write);

        if (!isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][$connectionName])) {
            $filePath = GeneralUtility::getFileAbsFileName(
                'typo3temp/var/LocalStorage/' . $name . '.sqlite'
            );
            FileSystemService::instance()->ensureHtAccessDenyFile(
                dirname($filePath)
            );

            $connectionConfiguration = [
                'driver' => 'pdo_sqlite',
                'path' => $filePath,
                'memory' => false,
            ];
            if (!$write) {
                $connectionConfiguration['wrapperClass']= ConnectionInterceptor::class;
            }
            $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][$connectionName] = $connectionConfiguration;

            if (!file_exists($filePath)) {
                $this->reinitializeLocalStorage($name);
                GeneralUtility::fixPermissions(dirname($filePath));
                GeneralUtility::fixPermissions($filePath);
            }
        }

        return $this->getConnectionByName($connectionName);
    }

    /**
     * Defines a particular local storage as default connection.
     *
     * @param string $name
     */
    public function setLocalStorageAsDefault(string $name)
    {
        $connectionName = $this->getLocalStorageName($name);
        $this->provideLocalStorageConnection($name);

        static::map(static::DEFAULT_CONNECTION_NAME, $connectionName);
    }

    /**
     * Reinitialize a particular local storage.
     * (clears all tables and provides local SQLite storage)
     *
     * @param string $name
     */
    public function reinitializeLocalStorage(string $name)
    {
        $connectionName = $this->getLocalStorageName($name, true);
        $this->provideLocalStorageConnection($name, true);

        LocalStorage::instance()->purge($connectionName);
        LocalStorage::instance()->initialize($connectionName);
    }

    /**
     * Unifies the generation of a local storage name.
     * This identifier is used a connection name as well.
     *
     * @param string $name
     * @param bool $write
     * @return string
     */
    private function getLocalStorageName(string $name, bool $write = false)
    {
        return 'LocalStorage::' . $name . ($write ? '::write' : '');
    }
}
