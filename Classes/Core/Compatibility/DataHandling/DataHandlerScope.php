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

use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\Change;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;

class DataHandlerScope
{
    /**
     * @return DataHandlerScope
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * @var EntityReference[]
     */
    public $acceptedNewEntityReferences = [];

    /**
     * @var EntityReference[]
     * @deprecated Not used anymore
     */
    public $ignoredNewEntityReferences = [];

    /**
     * @var Change[]
     */
    public $relationChangesMap = [];

}
