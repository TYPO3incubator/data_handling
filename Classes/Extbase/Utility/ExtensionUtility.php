<?php
namespace TYPO3\CMS\DataHandling\Extbase\Utility;

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

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Object\Instantiable;
use TYPO3\CMS\DataHandling\Core\Utility\ClassNamingUtility;

class ExtensionUtility implements Instantiable
{
    /**
     * @return ExtensionUtility
     */
    public static function instance()
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionUtility::class);
    }

    /**
     * @param string $tableName
     * @param string $className
     * @return ExtensionUtility
     */
    public function addMapping(string $tableName, string $className)
    {
        $prefix = 'persistence.classes.' .$className . '.mapping.';

        $settings = implode("\n\t", [
            $prefix . 'tableName = ' . $tableName,
            $prefix . 'columns.' . Common::FIELD_UUID . '.mapOnProperty = uuid',
            $prefix . 'columns.' . Common::FIELD_REVISION . '.mapOnProperty = revision',
        ]);

        $typoScript = sprintf(
            "config.tx_extbase {\n\t%s\n}\n", $settings
        );

        ExtensionManagementUtility::addTypoScriptSetup($typoScript);

        $validationClassName = ClassNamingUtility::buildValidationModelClassName($className);
        if ($validationClassName !== null) {
            static::addMapping($tableName, $validationClassName);
        }

        return $this;
    }
}
