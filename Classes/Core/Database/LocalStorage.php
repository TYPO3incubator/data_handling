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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
     * @param Connection $connection
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function initialize(Connection $connection)
    {
        $actualSchema = $connection->getSchemaManager()->createSchema();
        $targetSchema = clone $actualSchema;

        $expectedSchema = $this->normalizeSchema(
            $this->getSqlExpectedSchemaService()->getExpectedDatabaseSchema()
        );

        foreach ($expectedSchema as $tableName => $tableParts) {
            // @todo Find a better way to exclude the event store
            if ($tableName === 'sys_event_store') {
                continue;
            }

            if (!$targetSchema->hasTable($tableName)) {
                $targetSchema->createTable($tableName);
            }
            $targetTable = $targetSchema->getTable($tableName);
            foreach ($tableParts['fields'] as $fieldName => $fieldDefinition) {
                if (!$targetTable->hasColumn($fieldName)) {
                    $targetTable->addColumn($fieldName, $fieldDefinition['type'], $fieldDefinition['options']);
                } else {
                    $targetColumn = $targetTable->getColumn($fieldName);
                    $targetColumn->setType(Type::getType($fieldDefinition['type']));
                    $targetColumn->setOptions($fieldDefinition['options']);
                }
            }
        }

        $queries = $actualSchema->getMigrateToSql($targetSchema, $connection->getDatabasePlatform());

        foreach ($queries as $query) {
            $connection->query($query);
        }
    }

    /**
     * @param array $schema
     * @return array
     */
    protected function normalizeSchema(array $schema)
    {
        foreach ($schema as $tableName => $tableParts) {
            foreach ($tableParts['fields'] as $fieldName => $fieldDefinition) {
                $normalizedFieldDefinition = [
                    'options' => []
                ];
                if (preg_match('#^(?P<type>[a-z]+)(?:\s*\((?P<length>\d+)\))?(?:\s+(?P<unsigned>unsigned))?(?:\s+(?P<notNull>NOT\s+NULL))?(?:\s+default\s+(?P<default>(?:\'[^\']*\'|"[^"]*"|\d+|NULL)))?(?:\s+(?P<autoincrement>auto_increment))?#i', $fieldDefinition, $matches)) {
                    $normalizedFieldDefinition['type'] = $this->normalizeType($matches['type']);

                    if (!empty($matches['length'])) {
                        $normalizedFieldDefinition['options']['length'] = $matches['length'];
                    }
                    if (!empty($matches['default'])) {
                        $normalizedFieldDefinition['options']['default'] = trim($matches['default'], '\'"');
                    }
                    if (!empty($matches['unsigned'])) {
                        $normalizedFieldDefinition['options']['unsigned'] = true;
                    }
                    if (!empty($matches['notNull'])) {
                        $normalizedFieldDefinition['options']['notnull'] = true;
                    }
                    if (!empty($matches['autoincrement'])) {
                        $normalizedFieldDefinition['options']['autoincrement'] = true;
                    }

                    $normalizedFieldDefinition = $this->adjustDefinitions(
                        $normalizedFieldDefinition
                    );
                }
                $schema[$tableName]['fields'][$fieldName] = $normalizedFieldDefinition;
            }
        }

        return $schema;
    }

    /**
     * @param string $type
     * @return string
     */
    protected function normalizeType(string $type)
    {
        /*
            self::DATETIME => 'Doctrine\DBAL\Types\DateTimeType',
            self::DATETIMETZ => 'Doctrine\DBAL\Types\DateTimeTzType',
            self::DATE => 'Doctrine\DBAL\Types\DateType',
            self::TIME => 'Doctrine\DBAL\Types\TimeType',
            self::DECIMAL => 'Doctrine\DBAL\Types\DecimalType',
            self::BINARY => 'Doctrine\DBAL\Types\BinaryType',
            self::GUID => 'Doctrine\DBAL\Types\GuidType',
        */

        $mapping = [
            Type::BOOLEAN => ['bool'],
            Type::SMALLINT => ['tinyint'],
            Type::INTEGER => ['mediumint', 'int'],
            Type::STRING => ['char', 'varchar', 'tinytext'],
            Type::TEXT => ['mediumtext', 'longtext'],
            Type::BLOB => ['mediumblob', 'longblob'],
            Type::FLOAT => ['double'],
        ];

        foreach ($mapping as $definedType => $subTypes) {
            if (in_array($type, $subTypes, true)) {
                return $definedType;
            }
        }

        return $type;
    }

    private function adjustDefinitions(array $definitions)
    {
        $nullableTypes = [
            Type::STRING,
            Type::BLOB,
            Type::TEXT,
        ];

        if (in_array($definitions['type'], $nullableTypes)) {
            $definitions['options']['notnull'] = false;
        }

        if (
            !empty($definitions['options']['default'])
            && strtoupper($definitions['options']['default']) === 'NULL'
        ) {
            $definitions['options']['notnull'] = false;
        }

        return $definitions;
    }

    /**
     * @return SqlExpectedSchemaService
     */
    protected function getSqlExpectedSchemaService()
    {
        return GeneralUtility::makeInstance(SqlExpectedSchemaService::class);
    }
}
