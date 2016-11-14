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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Compatibility\Database\ConnectionInterceptor;
use TYPO3\CMS\EventSourcing\Core\Database\ConnectionPool;
use TYPO3\CMS\EventSourcing\Core\Database\LocalStorage;
use TYPO3\CMS\EventSourcing\Core\Service\FileSystemService;

class LocalStoragePool
{
    /**
     * @return LocalStoragePool
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(static::class);
    }

    /**
     * Provides a local storage connection for context based projections.
     *
     * @param string $name Name of the LocalStorage (e.g. 'workspace-0')
     * @param bool $write Whether to allow write access (e.g. for projection)
     * @return Connection
     */
    public function provideConnection(string $name, bool $write = false)
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
                $this->reinitialize($name);
                GeneralUtility::fixPermissions(dirname($filePath));
                GeneralUtility::fixPermissions($filePath);
            }
        }

        return ConnectionPool::instance()->getConnectionByName($connectionName);
    }

    /**
     * Reinitialize a particular local storage.
     * (clears all tables and provides local SQLite storage)
     *
     * @param string $name
     */
    public function reinitialize(string $name)
    {
        $connectionName = $this->getLocalStorageName($name, true);
        $this->provideConnection($name, true);

        LocalStorage::instance()->purge($connectionName);
        LocalStorage::instance()->initialize($connectionName);
    }

    /**
     * Defines a particular local storage as default connection.
     *
     * @param string $name
     */
    public function setAsDefault(string $name)
    {
        $this->provideConnection($name);
        $connectionName = $this->getLocalStorageName($name);
        ConnectionPool::instance()->setDefaultConnectionName($connectionName);
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
