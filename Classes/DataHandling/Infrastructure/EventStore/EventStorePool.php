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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Common\Providable;

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
    public static function instance()
    {
        return GeneralUtility::makeInstance(EventStorePool::class);
    }

    /**
     * @var EventStoreEnrolment[]
     * @todo Switch to AE: enroll, enrollment
     */
    protected $enrolments = [];

    /**
     * @param string $name
     * @return EventStoreEnrolment
     */
    public function enrolStore(string $name)
    {
        $this->enrolments[$name] = EventStoreEnrolment::instance()->setName($name);
        return $this->enrolments[$name];
    }

    /**
     * @param string|EventSelector $concerning
     * @return EventStore
     */
    public function getBestFor($concerning)
    {
        if (!($concerning instanceof EventSelector)) {
            $concerning = EventSelector::create($concerning);
        }

        // @todo Improve to find best (better) matching store
        foreach ($this->enrolments as $enrolment) {
            if ($enrolment->getConcerning()->fulfills($concerning)) {
                return $enrolment->getStore();
            }
        }

        return $this->getDefault();
    }

    /**
     * @param string|EventSelector $concerning
     * @return EventStoreBundle
     */
    public function getAllFor($concerning)
    {
        if (!($concerning instanceof EventSelector)) {
            $concerning = EventSelector::create($concerning);
        }

        $bundle = EventStoreBundle::instance();

        foreach ($this->enrolments as $enrolment) {
            if ($enrolment->getConcerning()->fulfills($concerning)) {
                $bundle->append($enrolment->getStore());
            }
        }

        if ($bundle->count() === 0) {
            throw new \RuntimeException('No stores found for "' . $concerning . '"', 1471857566);
        }

        return $bundle;
    }

    /**
     * @return EventStore
     */
    public function getDefault()
    {
        foreach ($this->enrolments as $enrolment) {
            if (
                $enrolment->getConcerning()->isAll()
                || $enrolment->getConcerning()->getStreamName() === '*'
            ) {
                return $enrolment->getStore();
            }
        }

        throw new \RuntimeException('Default store cannot be determined from enrolments', 1471614261);
    }
}
