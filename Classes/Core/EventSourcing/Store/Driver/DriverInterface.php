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

use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;

interface DriverInterface
{
    /**
     * @param string $streamName
     * @param AbstractEvent $event
     * @param string[] $categories
     * @return bool
     */
    public function append(string $streamName, AbstractEvent $event, array $categories = []): bool;

    /**
     * @param string $eventStream
     * @param string[] $categories
     * @return \Iterator
     */
    public function open(string $eventStream, array $categories = []);
}
