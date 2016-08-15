<?php
defined('TYPO3_MODE') or die();

// XCLASSES & alternative implementations

$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Origin'] =
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['wrapperClass'] =
    \TYPO3\CMS\DataHandling\Core\Compatibility\Database\ConnectionInterceptor::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Database\DatabaseConnection::class]['className']
    = \TYPO3\CMS\DataHandling\Core\Compatibility\Database\DatabaseConnectionInterceptor::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class]['className']
    = \TYPO3\CMS\DataHandling\Core\Authentication\BackendUserAuthentication::class;

/*
 * ext:core
 */

// provide new database fields
\TYPO3\CMS\DataHandling\Common::getSignalSlotDispatcher()->connect(
    \TYPO3\CMS\Install\Service\SqlExpectedSchemaService::class, 'tablesDefinitionIsBeingBuilt',
    \TYPO3\CMS\DataHandling\Core\Slot\EventSourcingSchemaModificationSlot::class, 'generate'
);

/*
 * ext:install
 */

// create initial uuid values
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\TYPO3\CMS\DataHandling\Install\Updates\EventInitializationUpdate::class]
    = \TYPO3\CMS\DataHandling\Install\Updates\EventInitializationUpdate::class;

// integration: hooks & slots
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['ac3c06f089776446875c4957a7f35a56'] =
    \TYPO3\CMS\DataHandling\Integration\Hook\DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['ac3c06f089776446875c4957a7f35a56'] =
    \TYPO3\CMS\DataHandling\Integration\Hook\DataHandlerHook::class;

// initialize default EventStore using SqlDriver
\TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventStorePool::provide()
    ->registerDefault(
        \TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventStore::create(
            \TYPO3\CMS\DataHandling\Core\EventSourcing\Store\Driver\SqlDriver::instance()
        )
    );

// bind stream, managing generic events
\TYPO3\CMS\DataHandling\Core\EventSourcing\EventManager::provide()->bind(
    \TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\StreamProvider::create('generic')
        ->setStore(\TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventStorePool::provide()->getDefault())
        ->setStream(\TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\GenericStream::instance())
        ->setEventNames([\TYPO3\CMS\DataHandling\Core\Domain\Event\Generic\AbstractEvent::class])
);