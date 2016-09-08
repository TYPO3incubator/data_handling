<?php
namespace TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling;

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

use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\DataHandling\DataHandler;

class LegacyDataHandler extends DataHandler
{
    /**
     * @param string $value
     * @param array $tcaFieldConf
     * @param string $table
     * @param int $id
     * @param int $realPid
     * @param string $field
     * @return array
     */
    public function checkValueForInput($value, $tcaFieldConf, $table, $id, $realPid, $field)
    {
        $value = $this->modifyDateTimeValue($value, $tcaFieldConf);
        return parent::checkValueForInput($value, $tcaFieldConf, $table, $id, $realPid, $field);
    }

    /**
     * @param string $value
     * @param $configuration
     * @return string
     */
    private function modifyDateTimeValue(string $value, array $configuration)
    {
        $dateTimeFormats = QueryHelper::getDateTimeFormats();
        $databaseType = ($configuration['dbType'] ?? null);
        if ($databaseType === 'date' || $databaseType === 'datetime') {
            $emptyValue = $dateTimeFormats[$databaseType]['empty'];
            $format = $dateTimeFormats[$databaseType]['format'];
            $value = (!empty($value) && $value !== $emptyValue ? gmdate($format, $value) : $emptyValue);
        }
        return $value;
    }
}
