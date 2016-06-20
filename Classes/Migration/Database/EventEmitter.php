<?php
namespace TYPO3\CMS\DataHandling\Migration\Database;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Event\Record\ChangedEvent;
use TYPO3\CMS\DataHandling\Event\Record\CreatedEvent;
use TYPO3\CMS\DataHandling\Event\Record\DeletedEvent;
use TYPO3\CMS\DataHandling\Event\Record\PurgedEvent;
use TYPO3\CMS\DataHandling\Store\EventStore;

class EventEmitter implements SingletonInterface
{
    /**
     * @return EventEmitter
     */
    static public function getInstance()
    {
        return GeneralUtility::makeInstance(EventEmitter::class);
    }

    public function emitCreatedEvent(string $tableName, array $fieldValues)
    {
        $event = GeneralUtility::makeInstance(CreatedEvent::class);
        $event->setTableName($tableName);
        $event->setData($fieldValues);

        EventStore::getInstance()->append('content-' . $tableName, $event);
    }

    public function emitChangedEvent(string $tableName, array $fieldValues, int $identifier)
    {
        $event = GeneralUtility::makeInstance(ChangedEvent::class);
        $event->setTableName($tableName);
        $event->setData($fieldValues);

        EventStore::getInstance()->append('content-' . $tableName . '-' . $identifier, $event);
    }

    public function emitDeletedEvent(string $tableName, array $fieldValues, int $identifier)
    {
        $event = GeneralUtility::makeInstance(DeletedEvent::class);
        $event->setTableName($tableName);
        $event->setData($fieldValues);

        EventStore::getInstance()->append('content-' . $tableName . '-' . $identifier, $event);
    }

    public function emitPurgeEvent(string $tableName, int $identifier)
    {
        $event = GeneralUtility::makeInstance(PurgedEvent::class);
        $event->setTableName($tableName);

        EventStore::getInstance()->append('content-' . $tableName . '-' . $identifier, $event);
    }

    static public function isDeleteCommand(string $tableName, array $fieldValues): bool
    {
        $fieldName = MetaModelService::getInstance()->getDeletedFieldName($tableName);
        return (
            !empty($fieldName) && !empty($fieldValues[$fieldName])
        );
    }

    static public function isDisableCommand(string $tableName, array $fieldValues): bool
    {
        $fieldName = MetaModelService::getInstance()->getDisabledFieldName($tableName);
        return (
            !empty($fieldName) && !empty($fieldValues[$fieldName])
        );
    }

    static public function isSystemInternal(string $tableName): bool
    {
        $systemInternalTables = [
            'sys_event_store',
            'tx_rsaauth_keys',
            'be_sessions',
            'fe_session_data',
            'fe_sessions',
        ];

        $nonSystemInternalTables = [
            'sys_category',
            'sys_category_record_mm',
            'sys_domain',
            'sys_file',
            'sys_file_metadata',
            'sys_file_reference',
            'sys_file_storage',
            'sys_language',
            'sys_news',
            'sys_note',
            'sys_template',
        ];

        return (
            empty($tableName)
            || strpos($tableName, 'cf_') === 0
            || in_array($tableName, $systemInternalTables)
            || strpos($tableName, 'sys_') === 0 && !in_array($tableName, $nonSystemInternalTables)
        );
    }
}
