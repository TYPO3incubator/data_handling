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

use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Event\BaseEvent;

interface PersistableDriver
{
    /**
     * @param string $streamName
     * @param BaseEvent $event
     * @param string[] $categories
     * @return null|int
     */
    public function attach(string $streamName, BaseEvent $event, array $categories = []);

    /**
     * @param string $streamName
     * @param string[] $categories
     * @return \Iterator
     */
    public function stream(string $streamName, array $categories = []);
}
