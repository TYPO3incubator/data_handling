<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\EventUpdate;

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
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Event\BaseEvent;
use TYPO3\CMS\EventSourcing\Infrastructure\EventStore\Updatable;

class ModifiedEntityEventUpdate implements Updatable
{
    public function listensTo()
    {
        return [
            // sure, this class does not exist anymore
            // that's what this update is for...
            Event\ModifiedEntityEvent::class
        ];
    }

    public function update(
        string $eventType,
        string $eventId,
        int $eventVersion,
        \DateTime $date,
        UuidInterface $aggregateId = null,
        $data,
        $metadata
    ): ?BaseEvent
    {
        if ($eventType === Event\ModifiedEntityEvent::class) {
            return Event\ChangedEntityValuesEvent::reconstitute(
                Event\ChangedEntityValuesEvent::class,
                $eventId,
                $eventVersion,
                $date,
                $aggregateId,
                $data,
                $metadata
            );
        }

        return null;
    }

}
