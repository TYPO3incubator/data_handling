<?php
namespace TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\InputConverter;

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

class InputConverter extends AbstractConverter
{
    /**
     * @return InputConverter
     */
    public static function create()
    {
        return GeneralUtility::makeInstance(InputConverter::class);
    }

    public function convert($value, array $configuration = null)
    {
        // last four parameters ($table, $id, $realPid, $field) are required for
        // + datetime values for the current DBMS in DBAL context
        // + unique value for whole table or for given page
        // = both are target to be processed during projection
        $result = $this->getLegacyDataHandler()->checkValueForInput($value, $configuration['config'], '', '', '', '');

        return $result['value'] ?? null;
    }
}
