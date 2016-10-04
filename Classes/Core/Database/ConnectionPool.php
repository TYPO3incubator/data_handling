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
use TYPO3\CMS\DataHandling\Core\Service\FileSystemService;

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

    /**
     * @param bool $originAsDefault
     * @return bool
     */
    public static function originAsDefault(bool $originAsDefault)
    {
        $currentValue = static::$originAsDefault;
        static::$originAsDefault = $originAsDefault;
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
     * @param string $tableName
     * @return Connection
     */
    public function getConnectionForTable(string $tableName): Connection
    {
        if (!EventSourcingMap::provide()->shallProject($tableName)) {
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
     * @param string $name
     */
    public function setLocalStorageAsDefault(string $name)
    {
        $connectionName = $this->getLocalStorageName($name);
        $this->provideLocalStorageConnection($name);

        static::map(static::DEFAULT_CONNECTION_NAME, $connectionName);
    }

    /**
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
     * @param string $name
     * @param bool $write
     * @return string
     */
    private function getLocalStorageName(string $name, bool $write = false)
    {
        return 'LocalStorage::' . $name . ($write ? '::write' : '');
    }
}
