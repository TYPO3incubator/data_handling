<?php
namespace TYPO3\CMS\DataHandling\Core\Database;

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

use Doctrine\DBAL\Types\Type;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Database\Schema\ConnectionMigrator;
use TYPO3\CMS\Install\Service\SqlExpectedSchemaService;

class LocalStorage
{
    /**
     * @return LocalStorage
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(LocalStorage::class);
    }

    /**
     * @param string $connectionName
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function initialize(string $connectionName)
    {
        $statements = $this->getDatabaseDefinition();
        $tables = $this->getSchemaMigrator()
            ->parseCreateTableStatements($statements);

        ConnectionMigrator::create($connectionName, $tables)->install();
        $this->adjustForIncompleteInserts($connectionName);
    }

    /**
     * @param string $connectionName
     * @throws \Doctrine\DBAL\DBALException
     */
    public function purge(string $connectionName)
    {
        $connection = ConnectionPool::instance()
            ->getConnectionByName($connectionName);

        $actualSchema = $connection->getSchemaManager()->createSchema();
        $targetSchema = clone $actualSchema;

        foreach ($actualSchema->getTableNames() as $tableName) {
            $targetSchema->dropTable($tableName);
        }

        $queries = $actualSchema->getMigrateToSql(
            $targetSchema,
            $connection->getDatabasePlatform()
        );

        foreach ($queries as $query) {
            $connection->query($query);
        }
    }

    /**
     * @param string $connectionName
     * @throws \Doctrine\DBAL\DBALException
     */
    private function adjustForIncompleteInserts(string $connectionName)
    {
        $nullTypes = [
            Type::STRING,
            Type::BLOB,
            Type::TEXT,
        ];

        $connection = ConnectionPool::instance()
            ->getConnectionByName($connectionName);

        $actualSchema = $connection->getSchemaManager()->createSchema();
        $targetSchema = clone $actualSchema;

        foreach ($targetSchema->getTables() as  $table) {
            foreach ($table->getColumns() as $column) {
                if (!$column->getNotnull()) {
                    continue;
                }
                if (in_array($column->getType()->getName(), $nullTypes)) {
                    $column->setNotnull(false);
                }
            }
        }

        $queries = $actualSchema->getMigrateToSql(
            $targetSchema,
            $connection->getDatabasePlatform()
        );

        foreach ($queries as $query) {
            $connection->query($query);
        }
    }

    /**
     * @return array
     */
    private function getDatabaseDefinition(): array
    {
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        return $sqlReader->getCreateTableStatementArray(
            $sqlReader->getTablesDefinitionString()
        );
    }

    /**
     * @return SchemaMigrator
     */
    private function getSchemaMigrator()
    {
        return GeneralUtility::makeInstance(SchemaMigrator::class);
    }

    /**
     * @return SqlExpectedSchemaService
     */
    private function getSqlExpectedSchemaService()
    {
        return GeneralUtility::makeInstance(SqlExpectedSchemaService::class);
    }
}
