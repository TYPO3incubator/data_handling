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
use TYPO3\CMS\DataHandling\Core\Object\Providable;

class EventStorePool implements Providable
{
    /**
     * @var EventStorePool
     */
    static protected $eventStorePool;

    /**
     * @param bool $force
     * @return EventStorePool
     */
    public static function provide(bool $force = false)
    {
        if ($force || !isset(static::$eventStorePool)) {
            static::$eventStorePool = static::instance();
        }
        return static::$eventStorePool;
    }

    /**
     * @return EventStorePool
     */
    static public function instance()
    {
        return GeneralUtility::makeInstance(EventStorePool::class);
    }

    /**
     * @var EventStore[]
     */
    protected $eventStores = [];

    /**
     * @param EventStore $eventStore
     * @param string $name
     * @return EventStorePool
     */
    public function register(EventStore $eventStore, string $name)
    {
        if (isset($this->eventStores[$name])) {
            throw new \RuntimeException('Event store "' . $name . '" is already registered', 1470951132);
        }
        $this->eventStores[$name] = $eventStore;
        return $this;
    }

    /**
     * @param EventStore $eventStore
     * @return EventStorePool
     */
    public function registerDefault(EventStore $eventStore)
    {
        try {
            return $this->register($eventStore, 'default');
        } catch (\Exception $exception) {
        }
        return $this;
    }

    /**
     * @param string $name
     * @return EventStore
     */
    public function get(string $name)
    {
        if (!isset($this->eventStores[$name])) {
            throw new \RuntimeException('Event store "' . $name . '" does not exist', 1470951133);
        }
        return $this->eventStores[$name];
    }

    /**
     * @return EventStore
     */
    public function getDefault()
    {
        return $this->get('default');
    }
}
