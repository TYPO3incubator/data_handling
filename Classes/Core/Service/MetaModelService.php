<?php
namespace TYPO3\CMS\DataHandling\Core\Service;

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

class MetaModelService implements SingletonInterface
{
    /**
     * @return MetaModelService
     */
    static public function instance()
    {
        return GeneralUtility::makeInstance(MetaModelService::class);
    }

    public function getDeletedFieldName(string $tableName)
    {
        return ($GLOBALS['TCA'][$tableName]['ctrl']['delete'] ?? null);
    }

    public function getDisabledFieldName(string $tableName)
    {
        return ($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['disabled'] ?? null);
    }

    public function getColumnConfiguration(string $tableName, string $propertyName)
    {
        return ($GLOBALS['TCA'][$tableName]['columns'][$propertyName] ?? null);
    }

    public function isInvalidValueProperty(string $tableName, string $propertyName): bool
    {
        return (
            $this->isInvalidChangeProperty($tableName, $propertyName)
            || $this->isRelationProperty($tableName, $propertyName)
        );
    }

    public function isInvalidChangeProperty(string $tableName, string $propertyName): bool
    {
        return (
            $this->isSystemProperty($tableName, $propertyName)
            || $this->isActionProperty($tableName, $propertyName)
            || $this->isVisibilityProperty($tableName, $propertyName)
            || $this->isRestrictionProperty($tableName, $propertyName)
        );
    }

    // @todo Analyse group/file with MM references
    public function isRelationProperty(string $tableName, string $propertyName): bool
    {
        if (empty($GLOBALS['TCA'][$tableName]['columns'][$propertyName]['config']['type'])) {
            return false;
        }

        $configuration = $GLOBALS['TCA'][$tableName]['columns'][$propertyName]['config'];

        return (
            $configuration['type'] === 'group'
                && ($configuration['internal_type'] ?? null) === 'db'
                && !empty($configuration['allowed'])
            || $configuration['type'] === 'select'
                && (
                    !empty($configuration['foreign_table'])
                        && !empty($GLOBALS['TCA'][$configuration['foreign_table']])
                    || ($configuration['special'] ?? null) === 'languages'
                )
            || $configuration['type'] === 'inline'
                && !empty($configuration['foreign_table'])
                && !empty($GLOBALS['TCA'][$configuration['foreign_table']])
        );
    }

    public function isSystemProperty(string $tableName, string $propertyName): bool
    {
        $denyPropertyNames = ['uid', 'pid'];
        $ctrlNames = ['tstamp', 'crdate', 'cruser_id', 'editlock', 'origUid'];
        foreach ($ctrlNames as $ctrlName) {
            if (!empty($GLOBALS['TCA'][$tableName]['ctrl'][$ctrlName])) {
                $denyPropertyNames[] = $GLOBALS['TCA'][$tableName]['ctrl'][$ctrlName];
            }
        }

        return (
            in_array($propertyName, $denyPropertyNames)
            || strpos($propertyName, 't3ver_') === 0
        );
    }

    public function isActionProperty(string $tableName, string $propertyName): bool
    {
        $denyPropertyNames = [];
        $ctrlNames = ['sortby', 'delete'];
        foreach ($ctrlNames as $ctrlName) {
            if (!empty($GLOBALS['TCA'][$tableName]['ctrl'][$ctrlName])) {
                $denyPropertyNames[] = $GLOBALS['TCA'][$tableName]['ctrl'][$ctrlName];
            }
        }

        return (
            in_array($propertyName, $denyPropertyNames)
        );
    }

    public function isVisibilityProperty(string $tableName, string $propertyName): bool
    {
        $denyPropertyNames = [];
        $ctrlEnableNames = ['disabled'];
        foreach ($ctrlEnableNames as $ctrlEnableName) {
            if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'][$ctrlEnableName])) {
                $denyPropertyNames[] = $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'][$ctrlEnableName];
            }
        }

        return (
            in_array($propertyName, $denyPropertyNames)
        );
    }

    public function isRestrictionProperty(string $tableName, string $propertyName): bool
    {
        $denyPropertyNames = [];
        $ctrlEnableNames = ['starttime', 'endtime', 'fe_group'];
        foreach ($ctrlEnableNames as $ctrlEnableName) {
            if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'][$ctrlEnableName])) {
                $denyPropertyNames[] = $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'][$ctrlEnableName];
            }
        }

        return (
            in_array($propertyName, $denyPropertyNames)
        );
    }
}
