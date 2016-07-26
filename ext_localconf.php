<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Origin'] =
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['wrapperClass'] =
    \TYPO3\CMS\DataHandling\Migration\Database\ConnectionInterceptor::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Database\DatabaseConnection::class]['className']
    = \TYPO3\CMS\DataHandling\Migration\Database\DatabaseConnectionInterceptor::class;


\TYPO3\CMS\DataHandling\Common::getSignalSlotDispatcher()->connect(
    \TYPO3\CMS\Install\Service\SqlExpectedSchemaService::class, 'tablesDefinitionIsBeingBuilt',
    \TYPO3\CMS\DataHandling\Slot\UuidSchemaModificationSlot::class, 'generate'
);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\TYPO3\CMS\DataHandling\Updates\UuidSchemaUpdate::class]
    = \TYPO3\CMS\DataHandling\Updates\UuidSchemaUpdate::class;

