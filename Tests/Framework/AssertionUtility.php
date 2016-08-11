<?php
namespace TYPO3\CMS\DataHandling\Tests\Framework;

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

use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Tests initialization of events for existing records.
 */
class AssertionUtility
{
    /**
     * @param array $expectations
     * @param object|array|\ArrayAccess $subject
     * @return bool
     */
    public static function matchesExpectations(array $expectations, $subject): bool
    {
        $matches = 0;
        foreach ($expectations as $expectationPath => $expectationValue) {
            $actualValue = ObjectAccess::getPropertyPath($subject, $expectationPath);
            // UUID4: 850358f1-0aee-445e-b462-cdb3440c1bc0
            if ($expectationValue === '@@UUID@@') {
                if (preg_match('#^[a-z0-9]{8}-(?:[a-z0-9]{4}-){3}[a-z0-9]{12}$#i',
                    $actualValue)) {
                    $matches++;
                }
            } elseif ($expectationValue === '@@VALUE@@') {
                if (!empty($actualValue)) {
                    $matches++;
                }
            } elseif ($actualValue === $expectationValue) {
                $matches++;
            }
        }
        return ($matches === count($expectations));
    }
}
