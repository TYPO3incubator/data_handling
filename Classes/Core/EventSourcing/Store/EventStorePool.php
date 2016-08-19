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
     * @var EventStoreEnrolment[]
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
