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

class ConnectionInterceptor extends \TYPO3\CMS\Core\Database\DatabaseConnection
{
    public function exec_INSERTquery($table, $fields_values, $no_quote_fields = false)
    {
        if (!EventEmitter::isSystemInternal($table)) {
            EventEmitter::getInstance()->emitCreateEvent($table, $fields_values);
        }
        return parent::exec_INSERTquery($table, $fields_values, $no_quote_fields);
    }

    public function exec_INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = false)
    {
        if (!EventEmitter::isSystemInternal($table)) {
            foreach ($rows as $row) {
                $fieldValues = array_combine($fields, $row);
                EventEmitter::getInstance()->emitCreateEvent($table, $fieldValues);
            }
        }

        return parent::exec_INSERTmultipleRows($table, $fields, $rows, $no_quote_fields);
    }

    public function exec_UPDATEquery($table, $where, $fields_values, $no_quote_fields = false)
    {
        if (!EventEmitter::isSystemInternal($table)) {
        }
        return parent::exec_UPDATEquery($table, $where, $fields_values, $no_quote_fields);
    }

    public function exec_DELETEquery($table, $where)
    {
        if (!EventEmitter::isSystemInternal($table)) {
        }
        return parent::exec_DELETEquery($table, $where);
    }
}
