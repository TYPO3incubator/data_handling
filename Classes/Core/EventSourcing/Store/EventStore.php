<?php
namespace TYPO3\CMS\DataHandling\Core\EventSourcing\Store;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Storable;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\Driver\DriverInterface;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\Driver\SqlDriver;

class EventStore implements SingletonInterface
{
    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @return EventStore
     */
    static public function instance()
    {
        return GeneralUtility::makeInstance(EventStore::class);
    }

    public function __construct()
    {
        $this->driver = GeneralUtility::makeInstance(SqlDriver::class);
    }

    public function append($streamName, AbstractEvent $event, $expectedVersion = null)
    {
        if (!$event instanceof Storable) {
            throw new \RuntimeException('Event "' . get_class($event) . '" cannot be stored', 1470871139);
        }

        $this->driver->append($streamName, $event);
    }

    public function open($streamName)
    {

    }
}
