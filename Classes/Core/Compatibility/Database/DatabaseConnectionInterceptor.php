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
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EventSourcingMap;
use TYPO3\CMS\DataHandling\Core\Service\GenericService;

class DatabaseConnectionInterceptor extends DatabaseConnection
{
    public function exec_INSERTquery($table, $fields_values, $no_quote_fields = false)
    {
        if (EventSourcingMap::provide()->shallListen($table)) {
            ConnectionTranslator::instance()->createEntity(
                EntityReference::create($table),
                $fields_values
            );
        }

        return parent::exec_INSERTquery($table, $fields_values, $no_quote_fields);
    }

    public function exec_INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = false)
    {
        if (EventSourcingMap::provide()->shallListen($table)) {
            foreach ($rows as $index => $row) {
                $fieldValues = array_combine($fields, $row);
                ConnectionTranslator::instance()->createEntity(
                    EntityReference::create($table),
                    $fieldValues
                );
            }
        }

        return parent::exec_INSERTmultipleRows($table, $fields, $rows, $no_quote_fields);
    }

    public function exec_UPDATEquery($table, $where, $fields_values, $no_quote_fields = false)
    {
        if (EventSourcingMap::provide()->shallListen($table)) {
            foreach ($this->determineReferences($table, $where) as $reference) {
                if (!GenericService::instance()->isDeleteCommand($table, $fields_values)) {
                    ConnectionTranslator::instance()->modifyEntity(
                        $reference,
                        $fields_values
                    );
                } else {
                    ConnectionTranslator::instance()->purgeEntity(
                        $reference
                    );
                }
            }
        }

        return parent::exec_UPDATEquery($table, $where, $fields_values, $no_quote_fields);
    }

    public function exec_DELETEquery($table, $where)
    {
        if (EventSourcingMap::provide()->shallListen($table)) {
            foreach ($this->determineReferences($table, $where) as $reference) {
                ConnectionTranslator::instance()->purgeEntity(
                    $reference
                );
            }
        }

        return parent::exec_DELETEquery($table, $where);
    }

    /**
     * @param string $tableName
     * @param string $whereClause
     * @return EntityReference[]
     */
    private function determineReferences($tableName, $whereClause): array
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
