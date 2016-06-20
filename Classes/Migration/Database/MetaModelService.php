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

class MetaModelService implements SingletonInterface
{
    /**
     * @return MetaModelService
     */
    static public function getInstance()
    {
        return GeneralUtility::makeInstance(MetaModelService::class);
    }

    public function getDeletedFieldName(string $tableName)
    {
        if (empty($GLOBALS['TCA'][$tableName]['ctrl']['delete'])) {
            return null;
        }
        return $GLOBALS['TCA'][$tableName]['ctrl']['delete'];
    }

    public function getDisabledFieldName(string $tableName)
    {
        if (empty($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['disabled'])) {
            return null;
        }
        return $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['disabled'];
    }
}
