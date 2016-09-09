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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\Change;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;

class CommandMapperScope
{
    /**
     * @return CommandMapperScope
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(CommandMapperScope::class);
    }

    /**
     * @var EntityReference[]
     */
    public $newEntityReferences = [];

    /**
     * @var Change[]
     */
    public $relationChangesMap = [];

}
