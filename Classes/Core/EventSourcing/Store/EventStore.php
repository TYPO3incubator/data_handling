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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\StorableEvent;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\Driver\PersistableDriver;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\EventStream;

class EventStore implements AttachableStore
{
    /**
     * @var PersistableDriver
     */
    protected $driver;

    /**
     * @param PersistableDriver $driver
     * @return EventStore
     */
    public static function create(PersistableDriver $driver)
    {
        return GeneralUtility::makeInstance(EventStore::class, $driver);
    }

    /**
     * @param PersistableDriver $driver
     * @return EventStore
     */
    public function setDriver(PersistableDriver $driver)
    {
        $this->driver = $driver;
        return $this;
    }

    public function __construct(PersistableDriver $driver)
    {
        $this->setDriver($driver);
    }

    /**
     * @param string $streamName
     * @param AbstractEvent $event
     * @param string[] $categories
     * @param null $expectedVersion
     */
    public function attach(string $streamName, AbstractEvent $event, array $categories = [], $expectedVersion = null)
    {
        if (!$event instanceof StorableEvent) {
            throw new \RuntimeException('Event "' . get_class($event) . '" cannot be stored', 1470871139);
        }

        $eventVersion = $this->driver->attach($streamName, $event, $categories);

        if ($eventVersion !== null) {
            $event->setEventVersion($eventVersion);
        }
    }

    /**
     * @param string $streamName
     * @param string[] $categories
     * @return EventStream
     */
    public function stream(string $streamName, array $categories = [])
    {
        return $this->driver->stream($streamName, $categories);
    }
}
