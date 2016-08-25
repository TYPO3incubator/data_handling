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

use EventStore\StreamFeed\StreamFeedIterator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\BaseEvent;

class GetEventStoreIterator implements \Iterator, EventTraversable
{
    /**
     * @param StreamFeedIterator $feedIterator
     * @return GetEventStoreIterator
     */
    public static function create(StreamFeedIterator $feedIterator)
    {
        return GeneralUtility::makeInstance(GetEventStoreIterator::class, $feedIterator);
    }

    /**
     * @var StreamFeedIterator
     */
    protected $feedIterator;

    /**
     * @var BaseEvent
     */
    protected $event;

    public function __construct(StreamFeedIterator $feedIterator)
    {
        $this->feedIterator = $feedIterator;
    }

    /**
     * @return null|false|array
     */
    public function current()
    {
        return $this->event;
    }

    /**
     * @return null|string
     */
    public function key()
    {
        return ($this->event->getEventId() ?? null);
    }

    public function next()
    {
        $this->reconstituteNext();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return ($this->event !== null);
    }

    public function rewind()
    {
        $this->feedIterator->rewind();
        $this->reconstituteNext();
    }

    /**
     * @return bool
     */
    protected function reconstituteNext()
    {
        if (!$this->feedIterator->valid()) {
            return $this->invalidate();
        }

        /** @var \EventStore\StreamFeed\EntryWithEvent $item */
        $item = $this->feedIterator->current();
        $this->feedIterator->next();

        $eventClassName = $item->getEvent()->getType();
        if (!is_a($eventClassName, BaseEvent::class, true)) {
            return $this->invalidate();
        }

        $entryData = $this->extractPrivateProperty($item->getEntry(), 'json');
        $eventDate = new \DateTime($entryData['updated']);

        $this->event = call_user_func(
            $eventClassName . '::reconstitute',
            $item->getEvent()->getType(),
            $item->getEvent()->getEventId()->toNative(),
            $item->getEvent()->getVersion(),
            $eventDate,
            $item->getEvent()->getData(),
            $item->getEvent()->getMetadata()
        );

        return true;
    }

    /**
     * @return bool
     */
    protected function invalidate()
    {
        $this->event = null;
        return false;
    }

    /**
     * @param object $subject
     * @param string $propertyName
     * @return null|mixed
     */
    protected function extractPrivateProperty($subject, string $propertyName)
    {
        $reflection = new \ReflectionClass($subject);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        foreach ($properties as $property) {
            if ($property->getName() !== $propertyName) {
                continue;
            }
            $property->setAccessible(true);
            return $property->getValue($subject);
        }

        return null;
    }
}
