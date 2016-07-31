<?php
namespace TYPO3\CMS\DataHandling\Core\Compatibility\Database;

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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Record\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Record\EventEmitter;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Record\EventFactory;
use TYPO3\CMS\DataHandling\Core\Service\GenericService;

class DatabaseConnectionInterceptor extends DatabaseConnection
{
    public function exec_INSERTquery($table, $fields_values, $no_quote_fields = false)
    {
        if (!GenericService::instance()->isSystemInternal($table)) {
            $event = EventFactory::instance()->createCreatedEvent($table, $fields_values);
            $this->emitRecordEvent($event);
        }
        return parent::exec_INSERTquery($table, $fields_values, $no_quote_fields);
    }

    public function exec_INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = false)
    {
        if (!GenericService::instance()->isSystemInternal($table)) {
            foreach ($rows as $row) {
                $fieldValues = array_combine($fields, $row);
                $event = EventFactory::instance()->createCreatedEvent($table, $fieldValues);
                $this->emitRecordEvent($event);
            }
        }

        return parent::exec_INSERTmultipleRows($table, $fields, $rows, $no_quote_fields);
    }

    public function exec_UPDATEquery($table, $where, $fields_values, $no_quote_fields = false)
    {
        if (!GenericService::instance()->isSystemInternal($table)) {
            $identifier = $this->determineIdentifier($table, $where);
            if (!empty($identifier)) {
                if (!GenericService::instance()->isDeleteCommand($table, $fields_values)) {
                    $event = EventFactory::instance()->createChangedEvent($table, $fields_values, $identifier);
                } else {
                    $event = EventFactory::instance()->createDeletedEvent($table, $fields_values, $identifier);
                }
                $this->emitRecordEvent($event);
            }
        }
        return parent::exec_UPDATEquery($table, $where, $fields_values, $no_quote_fields);
    }

    public function exec_DELETEquery($table, $where)
    {
        if (!GenericService::instance()->isSystemInternal($table)) {
            $identifier = $this->determineIdentifier($table, $where);
            if (!empty($identifier)) {
                $event = EventFactory::instance()->createPurgeEvent($table, $identifier);
                $this->emitRecordEvent($event);
            }
        }
        return parent::exec_DELETEquery($table, $where);
    }

    protected function emitRecordEvent(AbstractEvent $event)
    {
        $metadata = ['trigger' => DatabaseConnectionInterceptor::class];

        if ($event->getMetadata() === null) {
            $event->setMetadata($metadata);
        } else {
            $event->setMetadata(
                array_merge($event->getMetadata(), $metadata)
            );
        }

        EventEmitter::instance()->emitRecordEvent($event);
    }

    /**
     * @param string $tableName
     * @param string $whereClause
     * @return null|int
     */
    protected function determineIdentifier($tableName, $whereClause)
    {
        $rows = $this->exec_SELECTgetRows('*', $tableName, $whereClause);
        if (count($rows) !== 1 || empty($rows[0]['uid'])) {
            return null;
        }
        return (int)$rows[0]['uid'];
    }
}
