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
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GeneralService implements SingletonInterface
{
    /**
     * @return GeneralService
     */
    static public function getInstance()
    {
        return GeneralUtility::makeInstance(GeneralService::class);
    }

    public function isDeleteCommand(string $tableName, array $fieldValues): bool
    {
        $fieldName = MetaModelService::getInstance()->getDeletedFieldName($tableName);
        return (
            !empty($fieldName) && !empty($fieldValues[$fieldName])
        );
    }

    public function isDisableCommand(string $tableName, array $fieldValues): bool
    {
        $fieldName = MetaModelService::getInstance()->getDisabledFieldName($tableName);
        return (
            !empty($fieldName) && !empty($fieldValues[$fieldName])
        );
    }

    public function isSystemInternal(string $tableName): bool
    {
        $systemInternalTables = [
            'sys_event_store',
            'tx_rsaauth_keys',
            'be_sessions',
            'fe_session_data',
            'fe_sessions',
        ];

        $nonSystemInternalTables = [
            'sys_category',
            'sys_category_record_mm',
            'sys_domain',
            'sys_file',
            'sys_file_metadata',
            'sys_file_reference',
            'sys_file_storage',
            'sys_language',
            'sys_news',
            'sys_note',
            'sys_template',
        ];

        return (
            empty($tableName)
            || strpos($tableName, 'cf_') === 0
            || in_array($tableName, $systemInternalTables)
            || strpos($tableName, 'sys_') === 0 && !in_array($tableName, $nonSystemInternalTables)
        );
    }
}
