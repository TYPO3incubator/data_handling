<?php
namespace TYPO3\CMS\DataHandling\Core\EventSourcing\Stream;

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
use TYPO3\CMS\DataHandling\Core\EventSourcing\Publishable;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventStore;

class StreamProvider implements Publishable
{
    /**
     * @var StreamProvider[]
     */
    static protected $streamProviders = [];

    /**
     * @param string $name
     * @return StreamProvider
     */
    public static function provideFor(string $name)
    {
        if (!isset(static::$streamProviders[$name])) {
            static::$streamProviders[$name] = static::create($name);
        }
        return static::$streamProviders[$name];
    }

    /**
     * @return StreamProvider
     */
    public static function create(string $name)
    {
        return GeneralUtility::makeInstance(StreamProvider::class, $name);
    }

    /**
     * @param string $name
     *
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string[]
     */
    protected $eventNames = [];

    /**
     * @var EventStore
     */
    protected $store;

    /**
     * @var AbstractStream
     */
    protected $stream;

    /**
     * @param array $eventNames
     * @return StreamProvider
     */
    public function setEventNames(array $eventNames)
    {
        $this->eventNames = $eventNames;
        return $this;
    }

    /**
     * @param EventStore $store
     * @return StreamProvider
     */
    public function setStore(EventStore $store)
    {
        $this->store = $store;
        return $this;
    }

    /**
     * @param AbstractStream $stream
     * @return StreamProvider
     */
    public function setStream(AbstractStream $stream)
    {
        $this->stream = $stream->setName($this->name);
        return $this;
    }

    /**
     * @param AbstractEvent $event
     * @return StreamProvider
     */
    public function publish(AbstractEvent $event)
    {
        if ($this->isValidEvent($event)) {
            $this->store->append($this->stream->determineStreamName($event), $event);
            $this->stream->publish($event);
        }
        return $this;
    }

    /**
     * @param string $streamName
     * @return \Iterator
     */
    public function open(string $streamName) {
        return $this->store->open($streamName);
    }

    /**
     * @param AbstractEvent $event
     * @return bool
     */
    protected function isValidEvent(AbstractEvent $event): bool
    {
        if (empty($this->eventNames)) {
            return true;
        }
        foreach ($this->eventNames as $eventName) {
            if (is_a($event, $eventName)) {
                return true;
            }
        }
        return false;
    }
}
