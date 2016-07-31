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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\LegacyDataHandler;
use TYPO3\CMS\DataHandling\Domain\Object\Property\Reference;

abstract class AbstractConverter
{
    abstract public function convert(Reference $reference, array $configuration, $value);

    protected function getLegacyDataHandler(): LegacyDataHandler
    {
        return GeneralUtility::makeInstance(LegacyDataHandler::class);
    }
}
