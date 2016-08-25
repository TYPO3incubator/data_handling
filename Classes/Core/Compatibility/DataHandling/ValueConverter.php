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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\Converter\AbstractConverter;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\PropertyReference;

class ValueConverter
{
    /**
     * @return ValueConverter
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(ValueConverter::class);
    }

    public function convert(PropertyReference $reference, $value)
    {
        $configuration = $this->getPropertyConfiguration($reference);
        $converter = $this->determineConverter($configuration['config']['type']);

        $value = $converter->convert($reference, $configuration, $value);

        return $value;
    }

    /**
     * @param string $type
     * @return AbstractConverter
     * @todo Add some kind of type -> converter registry
     */
    protected function determineConverter(string $type): AbstractConverter
    {
        switch ($type) {
            case 'input':
                return \TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\Converter\InputConverter::instance();
            case 'passthrough':
            default:
                return \TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\Converter\PassThroughConverter::instance();
        }
    }

    /**
     * @param PropertyReference $reference
     * @return null|array
     */
    protected function getPropertyConfiguration(PropertyReference $reference)
    {
        $tableName = $reference->getEntityReference()->getName();
        $columnName = $reference->getName();

        if (empty($GLOBALS['TCA'][$tableName]['columns'][$columnName]['config']['type'])) {
            return null;
        }

        // use whole column configuration, not only 'config' index
        return $GLOBALS['TCA'][$tableName]['columns'][$columnName];
    }
}
