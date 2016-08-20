<?php
namespace TYPO3\CMS\DataHandling\Core\EventSourcing\Store\Driver;

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
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;

class NullDriver implements DriverInterface
{
    /**
     * @return NullDriver
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(NullDriver::class);
    }

    /**
     * @param string $streamName
     * @param AbstractEvent $event
     * @param string[] $categories
     * @return bool
     */
    public function append(string $streamName, AbstractEvent $event, array $categories = []): bool
    {
        return true;
    }

    /**
     * @param string $streamName
     * @param string[] $categories
     * @return \ArrayIterator
     */
    public function open(string $streamName, array $categories = [])
    {
        return new \ArrayIterator([]);
    }
}
