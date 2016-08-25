<?php
namespace TYPO3\CMS\DataHandling\Core\Process;

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
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\BaseEvent;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelector;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;
use TYPO3\CMS\DataHandling\Core\Process\Projection\ProjectionPool;

class EventManager implements Instantiable
{
    /**
     * @return EventManager
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(EventManager::class);
    }

    /**
     * @param BaseEvent $event
     */
    public function manage(BaseEvent $event)
    {
        $concerning = EventSelector::instance()
            ->setEvents([get_class($event)]);

        try {
            $enrolment = ProjectionPool::provide()->getFor($concerning);
        } catch (\Exception $exception) {
            return;
        }

        $enrolment
            ->provide()->forEvent()
            ->project($event);
    }
}
