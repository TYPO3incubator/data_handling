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

use TYPO3\CMS\Core\DataHandling\DataHandler;

class LegacyDataHandler extends DataHandler
{
    public function checkValueForInput($value, $tcaFieldConf, $table, $id, $realPid, $field)
    {
        return parent::checkValueForInput($value, $tcaFieldConf, $table, $id, $realPid, $field);
    }
}
