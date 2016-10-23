<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Infrastructure\EventStore;

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

use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Event\EventApplicable;

class Saga
{
    const EVENT_EXCLUDING = 'excluding';
    const EVENT_INCLUDING = 'including';

    /**
     * @param string|EventSelector $concerning
     * @return Saga
     */
    public static function create($concerning)
    {
        if (!($concerning instanceof EventSelector)) {
            $concerning = EventSelector::create($concerning);
        }

        if (empty($concerning->getStreamName())) {
            throw new \RuntimeException('No stream name defined', 1472124767);
        }

        $saga = new static();
        $saga->concerning = $concerning;
        return $saga;
    }

    /**
     * @var EventSelector
     */
    private $concerning;

    /**
     * @var string
     */
    private $excluding;

    /**
     * @var string
     */
    private $including;

    /**
     * Disable public constructor invocation.
     */
    private function __construct()
    {
    }

    /**
     * @param string $excluding
     * @return static
     */
    public function excluding(string $excluding)
    {
        $this->excluding = $excluding;
        return $this;
    }

    /**
     * @param string $including
     * @return static
     */
    public function including(string $including)
    {
        $this->including = $including;
        return $this;
    }

    /**
     * Wrapper for including/excluding.
     *
     * @param string $eventId
     * @param string $type
     * @return static
     */
    public function constraint(string $eventId, string $type)
    {
        if (empty($eventId)) {
            return $this;
        }

        if ($type === static::EVENT_EXCLUDING) {
            $this->excluding($eventId);
        }
        if ($type === static::EVENT_INCLUDING) {
            $this->including($eventId);
        }

        return $this;
    }

    /**
     * @param EventApplicable $state
     * @return EventApplicable
     */
    public function tell(EventApplicable $state)
    {
        $stream = EventStorePool::provide()
            ->getBestFor($this->concerning)
            ->stream($this->concerning->getStreamName());

        foreach ($stream as $event) {
            // stop telling events, before the event is applied
            if ($this->excluding === $event->getEventId()) {
                break;
            }

            $state->applyEvent($event);

            // stop telling events, after the event is applied
            if ($this->including === $event->getEventId()) {
                break;
            }
        }

        return $state;
    }
}
