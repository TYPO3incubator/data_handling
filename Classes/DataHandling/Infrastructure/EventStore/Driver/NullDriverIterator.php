<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Infrastructure\EventStore\Driver;

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
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Event\BaseEvent;

class NullDriverIterator extends \ArrayObject implements EventTraversable
{
    /**
     * @return NullDriverIterator
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(NullDriverIterator::class);
    }
}
