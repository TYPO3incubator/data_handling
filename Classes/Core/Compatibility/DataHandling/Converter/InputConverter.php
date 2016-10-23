<?php
namespace TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\Converter;

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

use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\PropertyReference;

class InputConverter extends AbstractConverter
{
    /**
     * @return InputConverter
     */
    public static function instance()
    {
        return new static();
    }

    public function convert(PropertyReference $reference, array $configuration, $value)
    {
        // last four parameters ($table, $id, $realPid, $field) are required for
        // + datetime values for the current DBMS in DBAL context
        // + unique value for whole table or for given page
        // = both are target to be processed during projection
        $result = $this->getLegacyDataHandler()->checkValueForInput($value, $configuration['config'], '', '', '', '');

        return $result['value'] ?? null;
    }
}
