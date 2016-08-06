<?php
namespace TYPO3\CMS\DataHandling\Core\MetaModel;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

class Map
{
    /**
     * @return Map
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(Map::class);
    }

    /**
     * @return string
     */
    public static function calculateHash(): string
    {
        return sha1(
            serialize($GLOBALS['TCA'])
        );
    }

    /**
     * @var string
     */
    protected $hash;

    /**
     * @var Schema[]
     */
    protected $schemas = [];

    public function __construct()
    {
        $this->build();
    }

    /**
     * @return bool
     */
    public function isCurrent(): bool
    {
        return ($this->hash === static::calculateHash());
    }

    /**
     * @param string $name
     * @return null|Schema
     */
    public function getSchema(string $name) {
        return ($this->schemas[$name] ?? null);
    }

    protected function build()
    {
        // store hash of current configuration
        $this->hash = static::calculateHash();
        // first build all available schemas and properties
        foreach ($GLOBALS['TCA'] as $tableName => $tableConfiguration) {
            $schema = Schema::instance()->setName($tableName);
            $this->schemas[$tableName] = $schema;

            foreach (array_keys($tableConfiguration['columns']) as $columnName) {
                $schema->addProperty(
                    Property::instance()->setName($columnName)
                );
            }
        }
        // then continue to analyze relations
        foreach ($this->schemas as $schema) {
            foreach ($schema->getProperties() as $property) {
                $this->buildRelations($property);
            }
        }
    }

    protected function buildRelations(Property $property)
    {
        if (!$property->isRelationProperty()) {
            return;
        }

        $tableNames = [];
        $configuration = $property->getConfiguration();
        // type=select, with special type languages
        if ($configuration['config']['type'] === 'select' && ($configuration['config']['special'] ?? null) === 'languages') {
            $tableNames = ['sys_language'];
        // type=select, type=inline
        } elseif ($configuration['config']['type'] === 'select' || $configuration['config']['type'] === 'inline') {
            $tableNames = [$configuration['config']['foreign_table']];
        // type=group
        } elseif ($configuration['config']['type'] === 'group') {
            $allowedTables = GeneralUtility::trimExplode(',', $configuration['config']['allowed'], true);
            if (in_array('*', $allowedTables)) {
                $tableNames = array_keys($this->schemas);
            } else {
                $tableNames = $allowedTables;
            }
        }

        foreach ($tableNames as $tableName) {
            $passiveSchema = $this->getSchema($tableName);
            if ($passiveSchema === null) {
                continue;
            }

            $property->addRelation(
                ActiveRelation::instance()->setTo($passiveSchema)
            );

            if ($configuration['config']['type'] === 'inline' && !empty($configuration['config']['foreign_field'])) {
                $foreignFieldName =  $configuration['config']['foreign_field'];
                $passiveProperty = $passiveSchema->getProperty($foreignFieldName);

                if ($passiveProperty === null) {
                    $passiveProperty = Property::instance()->setName($foreignFieldName);
                    $passiveSchema->addProperty($passiveProperty);
                }

                $passiveProperty->addRelation(
                    PassiveRelation::instance()->setFrom($property)
                );
            }
        }
    }
}