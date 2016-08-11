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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSerializer;

class SqlDriver implements DriverInterface
{
    /**
     * @var EventSerializer
     */
    protected $eventSerializer;

    public function append(string $streamName, AbstractEvent $event)
    {
        $rawEvent = [
            'event_stream' => $streamName,
            'event_uuid' => $event->getUuid(),
            'event_name' => get_class($event),
            'event_date' => $event->getDate()->format('Y-m-d H:i:s.u'),
            'data' => $event->exportData(),
            'metadata' => $event->getMetadata(),
        ];

        foreach ($rawEvent as $propertyName => $propertyValue) {
            if ($propertyValue === null) {
                unset($rawEvent[$propertyName]);
            } elseif (is_array($propertyValue)) {
                $rawEvent[$propertyName] = json_encode($propertyValue);
            }
        }

        ConnectionPool::instance()
            ->getOriginConnection()
            ->insert('sys_event_store', $rawEvent);
    }

    public function open(string $eventStream)
    {

    }

    /**
     * @return EventSerializer
     * @deprecated Currently not used
     */
    protected function getEventSerializer()
    {
        if (isset($this->eventSerializer)) {
            return $this->eventSerializer;
        }

        $this->eventSerializer = GeneralUtility::makeInstance(EventSerializer::class);
        return $this->eventSerializer;
    }
}
