<?php
namespace TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler;

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

use Ramsey\Uuid\UuidInterface;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\BaseEvent;
use TYPO3\CMS\DataHandling\Core\Utility\ClassNamingUtility;

trait EventHandlerTrait
{
    /**
     * @var int
     */
    protected $revision = null;

    /**
     * @var UuidInterface
     */
    protected $aggregateId;

    /**
     * @var bool
     */
    protected $projected = false;

    /**
     * @var BaseEvent[]
     */
    protected $recordedEvents = [];

    /**
     * @var string[]
     */
    protected $appliedEventIds = [];

    /**
     * @return int
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * @return UuidInterface
     */
    public function getAggregateId()
    {
        return $this->aggregateId;
    }

    /**
     * @return array
     */
    public function getRecordedEvents()
    {
        return $this->recordedEvents;
    }

    public function purgeRecordedEvents()
    {
        $this->recordedEvents = [];
    }

    protected function manageEvent(BaseEvent $event)
    {
        $this->recordEvent($event);
        $this->applyEvent($event);
    }

    protected function recordEvent(BaseEvent $event)
    {
        $this->recordedEvents[] = $event;
    }

    /**
     * @param BaseEvent $event
     */
    public function applyEvent(BaseEvent $event)
    {
        // this just checks for applied events in the current processing, the
        // ids of previously applied events might not be available in this scope
        if (in_array($event->getEventId(), $this->appliedEventIds)) {
            throw new \RuntimeException('Event "' . $event->getEventId() . '" was already applied', 1472041262);
        }
        // validate and assign event version to subject
        if (!$this->projected
            && $event->getEventVersion() !== null
            && $this->revision + 1 !== $event->getEventVersion()
            && ($this->revision !== null || $event->getEventVersion() !== 0)
        ) {
            $reason = 'got ' . $event->getEventVersion() . ', expected ' . ($this->revision + 1);
            throw new \RuntimeException('Unexpected event in sequence (' . $reason . ')', 1472044588);
        }
        $this->revision = $event->getEventVersion();
        $this->appliedEventIds[] = $event->getEventId();
        // determine method name, that is used to apply the event
        $methodName = $this->getEventHandlerMethodName($event);
        if (!method_exists($this, $methodName)) {
            return;
        }
        // assign aggregate ID if defined
        if (empty($event->getAggregateId())) {
            $this->aggregateId = $event->getAggregateId();
        }
        // apply event
        $this->{$methodName}($event);
    }

    /**
     * @param BaseEvent $event
     * @return string
     */
    protected function getEventHandlerMethodName(BaseEvent $event)
    {
        $eventName = ClassNamingUtility::getLastPart($event);
        return 'apply' . $eventName;
    }
}
