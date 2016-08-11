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
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Record;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\EntityReference;
use TYPO3\CMS\DataHandling\Core\Service\GenericService;

class DatabaseConnectionInterceptor extends DatabaseConnection
{
    public function exec_INSERTquery($table, $fields_values, $no_quote_fields = false)
    {
        if (!GenericService::instance()->isSystemInternal($table)) {
            $reference = EntityReference::create($table);
            $this->emitRecordEvent(
                Record\CreatedEvent::instance($reference)
            );
            $this->emitRecordEvent(
                Record\ChangedEvent::instance($reference, $fields_values)
            );
            $fields_values[Common::FIELD_UUID] = $reference->getUuid();
        }

        return parent::exec_INSERTquery($table, $fields_values, $no_quote_fields);
    }

    public function exec_INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = false)
    {
        if (!GenericService::instance()->isSystemInternal($table)) {
            foreach ($rows as $index => $row) {
                $reference = EntityReference::create($table);
                $fieldValues = array_combine($fields, $row);
                $this->emitRecordEvent(
                    Record\CreatedEvent::instance($reference)
                );
                $this->emitRecordEvent(
                    Record\ChangedEvent::instance($reference, $fieldValues)
                );
                $rows[$index][] = $reference->getUuid();
            }
            $fields[] = Common::FIELD_UUID;
        }

        return parent::exec_INSERTmultipleRows($table, $fields, $rows, $no_quote_fields);
    }

    public function exec_UPDATEquery($table, $where, $fields_values, $no_quote_fields = false)
    {
        if (!GenericService::instance()->isSystemInternal($table)) {
            foreach ($this->determineReferences($table, $where) as $reference) {
                if (!GenericService::instance()->isDeleteCommand($table, $fields_values)) {
                    $this->emitRecordEvent(
                        Record\ChangedEvent::instance($reference, $fields_values)
                    );
                } else {
                    $this->emitRecordEvent(
                        Record\DeletedEvent::instance($reference)
                    );
                }
            }
        }

        return parent::exec_UPDATEquery($table, $where, $fields_values, $no_quote_fields);
    }

    public function exec_DELETEquery($table, $where)
    {
        if (!GenericService::instance()->isSystemInternal($table)) {
            foreach ($this->determineReferences($table, $where) as $reference) {
                $this->emitRecordEvent(
                    Record\PurgedEvent::instance($reference)
                );
            }
        }

        return parent::exec_DELETEquery($table, $where);
    }

    protected function emitRecordEvent(Record\AbstractEvent $event)
    {
        $metadata = ['trigger' => DatabaseConnectionInterceptor::class];

        if ($event->getMetadata() === null) {
            $event->setMetadata($metadata);
        } else {
            $event->setMetadata(
                array_merge($event->getMetadata(), $metadata)
            );
        }

        Record\EventEmitter::instance()->emitRecordEvent($event);
    }

    /**
     * @param string $tableName
     * @param string $whereClause
     * @return EntityReference[]
     */
    protected function determineReferences($tableName, $whereClause): array
    {
        $references = [];
        $rows = $this->exec_SELECTgetRows('uid,' . Common::FIELD_UUID, $tableName, $whereClause);
        foreach ($rows as $row) {
            $references[] = EntityReference::fromRecord($tableName, $row);
        }
        return $references;
    }
}
