<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Event\Record;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventStore;

class EventEmitter implements SingletonInterface
{
    /**
     * @return EventEmitter
     */
    static public function instance()
    {
        return GeneralUtility::makeInstance(EventEmitter::class);
    }

    public function emitRecordEvent(AbstractEvent $event)
    {
        $streamName = 'record-' . $event->getTableName();
        if ($event->getIdentifier()) {
            $streamName .= '-' . $event->getIdentifier();
        }
        EventStore::instance()->append($streamName, $event);
    }
}
