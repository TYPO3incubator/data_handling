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

use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Committable;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Publishable;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelector;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventStorePool;

abstract class AbstractStream implements Publishable, Committable
{
    /**
     * @var string
     */
    protected $prefix = 'abstract-stream';

    /**
     * @var callable[]
     */
    protected $consumers = [];

    /**
     * @param string $prefix
     * @return GenericStream
     */
    public function setPrefix(string $prefix) {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * @param string $streamName
     * @return string
     */
    public function prefix(string $streamName): string
    {
        return EventSelector::cleanPrefixPart($this->prefix)
            . ($streamName ? EventSelector::DELIMITER_STREAM_NAME . $streamName : '');
    }

    /**
     * @param AbstractEvent $event
     * @return GenericStream
     */
    public function publish(AbstractEvent $event)
    {
        // @todo Add check agains event type via EventSelector
        foreach ($this->consumers as $consumer) {
            call_user_func($consumer, $event);
        }
        return $this;
    }

    /**
     * @param callable $consumer
     * @return GenericStream
     */
    public function subscribe(callable $consumer)
    {
        if (!in_array($consumer, $this->consumers, true)) {
            $this->consumers[] = $consumer;
        }
        return $this;
    }

    /**
     * @param AbstractEvent $event
     * @param array $categories
     * @return GenericStream
     */
    public function commit(AbstractEvent $event, array $categories = [])
    {
        $streamName = $this->determineStreamNameByEvent($event);

        $eventSelector = EventSelector::instance()
            ->setStreamName($streamName)
            ->setEvents([get_class($event)])
            ->setCategories($categories);

        EventStorePool::provide()
            ->getBestFor($eventSelector)
            ->attach($streamName, $event, $categories);

        return $this;
    }

    /**
     * @param EventSelector $selector
     * @return void
     */
    public function replay(EventSelector $selector)
    {
        // create absolute selector by adding prefix (if required)
        $absoluteSelector = $selector->toAbsolute($this->prefix);

        $iterator = EventStorePool::provide()
            ->getBestFor($absoluteSelector)
            ->stream(
                $absoluteSelector->getStreamName(),
                $absoluteSelector->getCategories()
            );

        foreach ($iterator as $event) {
            $this->publish($event);
        }

        // no return value, since replay should be the last action
        // and subscriptions have to be applied for this action
    }

    /**
     * @param AbstractEvent $event
     * @return string
     */
    abstract protected function determineStreamNameByEvent(AbstractEvent $event): string;
}
