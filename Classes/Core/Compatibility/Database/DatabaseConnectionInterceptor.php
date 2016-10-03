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
use TYPO3\CMS\DataHandling\Core\Domain\Model\Event;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\EventSourcing\EventManager;
use TYPO3\CMS\DataHandling\Core\MetaModel\EventSourcingMap;
use TYPO3\CMS\DataHandling\Core\Service\GenericService;

class DatabaseConnectionInterceptor extends DatabaseConnection
{
    public function exec_INSERTquery($table, $fields_values, $no_quote_fields = false)
    {
        if (EventSourcingMap::provide()->shallRecord($table)) {
            $reference = EntityReference::create($table);
            $this->emitRecordEvent(
                Event\CreatedEntityEvent::create($reference)
            );
            $this->emitRecordEvent(
                Event\ModifiedEntityEvent::create($reference, $fields_values)
            );
            $fields_values[Common::FIELD_UUID] = $reference->getUuid();
        }

        return parent::exec_INSERTquery($table, $fields_values, $no_quote_fields);
    }

    public function exec_INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = false)
    {
        if (EventSourcingMap::provide()->shallRecord($table)) {
            foreach ($rows as $index => $row) {
                $reference = EntityReference::create($table);
                $fieldValues = array_combine($fields, $row);
                $this->emitRecordEvent(
                    Event\CreatedEntityEvent::create($reference)
                );
                $this->emitRecordEvent(
                    Event\ModifiedEntityEvent::create($reference, $fieldValues)
                );
                $rows[$index][] = $reference->getUuid();
            }
            $fields[] = Common::FIELD_UUID;
        }

        return parent::exec_INSERTmultipleRows($table, $fields, $rows, $no_quote_fields);
    }

    public function exec_UPDATEquery($table, $where, $fields_values, $no_quote_fields = false)
    {
        if (EventSourcingMap::provide()->shallRecord($table)) {
            foreach ($this->determineReferences($table, $where) as $reference) {
                if (!GenericService::instance()->isDeleteCommand($table, $fields_values)) {
                    $this->emitRecordEvent(
                        Event\ModifiedEntityEvent::create($reference, $fields_values)
                    );
                } else {
                    $this->emitRecordEvent(
                        Event\DeletedEntityEvent::create($reference)
                    );
                }
            }
        }

        return parent::exec_UPDATEquery($table, $where, $fields_values, $no_quote_fields);
    }

    public function exec_DELETEquery($table, $where)
    {
        if (EventSourcingMap::provide()->shallRecord($table)) {
            foreach ($this->determineReferences($table, $where) as $reference) {
                $this->emitRecordEvent(
                    Event\PurgedEntityEvent::create($reference)
                );
            }
        }

        return parent::exec_DELETEquery($table, $where);
    }

    protected function emitRecordEvent(Event\AbstractEvent $event)
    {
        $metadata = ['trigger' => DatabaseConnectionInterceptor::class];

        if ($event->getMetadata() === null) {
            $event->setMetadata($metadata);
        } else {
            $event->setMetadata(
                array_merge($event->getMetadata(), $metadata)
            );
        }

        EventManager::provide()->manage($event);
    }

    /**
     * @param string $tableName
     * @param string $whereClause
     * @return EntityReference[]
     */
    protected function determineReferences($tableName, $whereClause): array
    {
        $references = [];
        $fieldNames = ['uid', Common::FIELD_UUID, Common::FIELD_REVISION];
        $rows = $this->exec_SELECTgetRows(implode(',', $fieldNames), $tableName, $whereClause);
        foreach ($rows as $row) {
            $references[] = EntityReference::fromRecord($tableName, $row);
        }
        return $references;
    }
}
