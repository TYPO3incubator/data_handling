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
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelector;
use TYPO3\CMS\DataHandling\Core\Object\Instantiable;
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
     * @param AbstractEvent $event
     */
    public function manage(AbstractEvent $event)
    {
        $concerning = EventSelector::instance()
            ->setEvents([get_class($event)]);

        try {
            $enrolment = ProjectionPool::provide()->getFor($concerning);
        } catch (\Exception $exception) {
            return;
        }

        $enrolment
            ->provideEventProjection()
            ->project($event);
    }
}
