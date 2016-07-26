<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Database\Query\QueryBuilder::class]['className']
    = \TYPO3\CMS\DataHandling\Migration\Database\QueryBuilderInterceptor::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Database\DatabaseConnection::class]['className']
    = \TYPO3\CMS\DataHandling\Migration\Database\ConnectionInterceptor::class;


\TYPO3\CMS\DataHandling\Common::getSignalSlotDispatcher()->connect(
    \TYPO3\CMS\Install\Service\SqlExpectedSchemaService::class, 'tablesDefinitionIsBeingBuilt',
    \TYPO3\CMS\DataHandling\Slot\UuidSchemaModificationSlot::class, 'generate'
);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\TYPO3\CMS\DataHandling\Updates\UuidSchemaUpdate::class]
    = \TYPO3\CMS\DataHandling\Updates\UuidSchemaUpdate::class;

