<?php
namespace TYPO3\CMS\DataHandling\Core\Database\Schema;

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

use Doctrine\DBAL\Schema\SchemaDiff;
use TYPO3\CMS\Core\Database\Schema\Comparator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConnectionMigrator extends \TYPO3\CMS\Core\Database\Schema\ConnectionMigrator
{
    /**
     * @param string $tableName
     * @return string
     */
    protected function getConnectionNameForTable(string $tableName): string
    {
        return $this->connectionName;
    }

    /**
     * If the schema is not for the Default connection remove all tables from the schema
     * that have no mapping in the TYPO3 configuration. This avoids update suggestions
     * for tables that are in the database but have no direct relation to the TYPO3 instance.
     *
     * @param bool $renameUnused
     * @return \Doctrine\DBAL\Schema\SchemaDiff
     * @throws \Doctrine\DBAL\DBALException
     * @throws \InvalidArgumentException
     */
    protected function buildSchemaDiff(bool $renameUnused = true): SchemaDiff
    {
        // Build the schema definitions
        $fromSchema = $this->connection->getSchemaManager()->createSchema();
        $toSchema = $this->buildExpectedSchemaDefinitions($this->connectionName);

        // Add current table options to the fromSchema
        $tableOptions = $this->getTableOptions($fromSchema->getTableNames());
        foreach ($fromSchema->getTables() as $table) {
            $tableName = $table->getName();
            if (!array_key_exists($tableName, $tableOptions)) {
                continue;
            }
            foreach ($tableOptions[$tableName] as $optionName => $optionValue) {
                $table->addOption($optionName, $optionValue);
            }
        }

        // Build SchemaDiff and handle renames of tables and colums
        $comparator = GeneralUtility::makeInstance(Comparator::class);
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);

        if ($renameUnused) {
            $schemaDiff = $this->migrateUnprefixedRemovedTablesToRenames($schemaDiff);
            $schemaDiff = $this->migrateUnprefixedRemovedFieldsToRenames($schemaDiff);
        }

        return $schemaDiff;
    }
}
