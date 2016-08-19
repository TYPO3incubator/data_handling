<?php
namespace TYPO3\CMS\DataHandling;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Generic\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\EventSourcing\EventManager;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\Driver\SqlDriver;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventStore;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventStorePool;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\GenericStream;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\StreamProvider;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

class Common
{
    const FIELD_UUID = 't3uuid';
    const FIELD_REVISION = 't3rev';

    /**
     * @var bool
     * @internal
     */
    protected static $enable = true;

    /**
     * @return Dispatcher
     */
    public static function getSignalSlotDispatcher()
    {
        return GeneralUtility::makeInstance(Dispatcher::class);
    }

    /**
     * Overrides global configuration.
     */
    public static function overrideConfiguration()
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Origin'] =
            $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];

        if (!static::$enable) {
            return;
        }

        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['wrapperClass'] =
            \TYPO3\CMS\DataHandling\Core\Compatibility\Database\ConnectionInterceptor::class;
    }

    /**
     * Defines XCLASSES & alternative implementations.
     *
     * @internal
     */
    public static function registerAlternativeImplementations()
    {
        if (!static::$enable) {
            return;
        }

        // intercepts TYPO3_DB
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Database\DatabaseConnection::class]['className']
            = \TYPO3\CMS\DataHandling\Core\Compatibility\Database\DatabaseConnectionInterceptor::class;
        // provides ProjectionContext, once workspace information is available
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class]['className']
            = \TYPO3\CMS\DataHandling\Core\Authentication\BackendUserAuthentication::class;
    }

    public static function registerUpdates()
    {
        if (!static::$enable) {
            return;
        }

        // create initial uuid and revision values
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\TYPO3\CMS\DataHandling\Install\Updates\EventInitializationUpdate::class]
            = \TYPO3\CMS\DataHandling\Install\Updates\EventInitializationUpdate::class;
    }

    public static function registerHooks()
    {
        if (!static::$enable) {
            return;
        }

        // intercepts DataHandler
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['ac3c06f089776446875c4957a7f35a56'] =
            \TYPO3\CMS\DataHandling\Integration\Hook\DataHandlerHook::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['ac3c06f089776446875c4957a7f35a56'] =
            \TYPO3\CMS\DataHandling\Integration\Hook\DataHandlerHook::class;
    }

    public static function registerSlots()
    {
        // provides new database fields
        \TYPO3\CMS\DataHandling\Common::getSignalSlotDispatcher()->connect(
            \TYPO3\CMS\Install\Service\SqlExpectedSchemaService::class, 'tablesDefinitionIsBeingBuilt',
            \TYPO3\CMS\DataHandling\Core\Slot\EventSourcingSchemaModificationSlot::class, 'generate'
        );

        if (!static::$enable) {
            return;
        }
    }

    public static function registerEventSources()
    {
        // initialize default EventStore using SqlDriver
        EventStorePool::provide()
            ->enrolStore('sql')
            ->concerning('*')
            ->setStore(
                EventStore::create(SqlDriver::instance())
            );
        // bind stream, managing generic events
        EventManager::provide()->bindCommitter(
            StreamProvider::create('generic')
                ->setEventNames([AbstractEvent::class])
                ->setStream(GenericStream::instance())
                ->setStore(EventStorePool::provide()->getDefault())
        );
    }
}
