<?php
namespace TYPO3\CMS\DataHandling\Core\Utility;

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


class ClassNamingUtility extends \TYPO3\CMS\Core\Utility\ClassNamingUtility
{
    /**
     * @param object|string $object
     * @return string
     */
    public static function getLastPart($object): string
    {
        if (is_object($object)) {
            $className = get_class($object);
        } else {
            $className = (string)$object;
        }

        $classNameParts = explode('\\', $className);
        return $classNameParts[count($classNameParts)-1];
    }
}
