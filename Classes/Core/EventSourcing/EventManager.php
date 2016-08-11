<?php
namespace TYPO3\CMS\DataHandling\Core\EventSourcing;

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
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\AbstractStream;
use TYPO3\CMS\DataHandling\Core\Object\Providable;

class EventManager implements Providable
{
    /**
     * @var EventManager
     */
    static protected $eventManager;

    /**
     * @param bool $force
     * @return EventManager
     */
    public static function provide(bool $force = false)
    {
        if (!isset(static::$eventManager)) {
            static::$eventManager = static::instance();
        }
        return static::$eventManager;
    }

    /**
     * @return EventManager
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(EventManager::class);
    }

    /**
     * @var AbstractStream[][]
     */
    protected $streams = [];

    /**
     * @param AbstractStream $stream
     * @param string $eventType
     * @return EventManager
     */
    public function bindStream(AbstractStream $stream, string $eventType = AbstractEvent::class) {
        if (!is_a($eventType, AbstractEvent::class, true)) {
            throw new \RuntimeException('Event type must inherit from "' . AbstractEvent::class . '"', 1470853798);
        }
        if (!isset($this->streams[$eventType])) {
            $this->streams[$eventType] = [];
        }
        if (!in_array($stream, $this->streams[$eventType], true)) {
            $this->streams[$eventType][] = $stream;
        }
        return $this;
    }

    /**
     * @param string $className
     * @return AbstractStream[]
     */
    public function findStreams(string $className): array
    {
        $streams = [];
        foreach ($this->flattenStreams() as $stream) {
            if (is_a($stream, $className)) {
                $streams[] = $stream;
            }
        }
        return $streams;
    }

    /**
     * @return EventManager
     */
    public function purgeStreams()
    {
        $this->streams = [];
        return $this;
    }

    /**
     * @param AbstractEvent $event
     * @return EventManager
     */
    public function handle(AbstractEvent $event)
    {
        foreach ($this->streams as $eventType => $streams) {
            if (!is_a($event, $eventType)) {
                continue;
            }
            foreach ($streams as $stream) {
                $stream->publish($event);
            }
        }
        return $this;
    }

    /**
     * @return AbstractStream[]
     */
    protected function flattenStreams(): array
    {
        $flatStreams = [];
        foreach ($this->streams as $eventType => $streams) {
            foreach ($streams as $stream) {
                if (!in_array($stream, $flatStreams, true)) {
                    $flatStreams[] = $stream;
                }
            }
        }
        return $flatStreams;
    }
}
