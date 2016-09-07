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
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler\EventApplicable;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelector;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventStorePool;

class Saga
{
    const EVENT_EXCLUDING = 'excluding';
    const EVENT_INCLUDING = 'including';

    /**
     * @return Saga
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(Saga::class);
    }

    /**
     * @var string
     */
    protected $excluding;

    /**
     * @var string
     */
    protected $including;

    public function excluding(string $excluding)
    {
        $this->excluding = $excluding;
        return $this;
    }

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
     * @return $this
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
     * @param string|EventSelector $concerning
     * @return EventApplicable
     */
    public function tell(EventApplicable $state, $concerning)
    {
        if (!($concerning instanceof EventSelector)) {
            $concerning = EventSelector::create($concerning);
        }

        if (empty($concerning->getStreamName())) {
            throw new \RuntimeException('No stream name defined', 1472124767);
        }

        $stream = EventStorePool::provide()
            ->getBestFor($concerning)
            ->stream($concerning->getStreamName());

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
