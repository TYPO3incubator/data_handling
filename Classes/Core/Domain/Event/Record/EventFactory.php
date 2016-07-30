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

class EventFactory implements SingletonInterface
{
    /**
     * @return EventFactory
     */
    static public function getInstance()
    {
        return GeneralUtility::makeInstance(EventFactory::class);
    }

    public function createCreatedEvent(string $tableName, array $fieldValues)
    {
        $event = CreatedEvent::create();
        $event->setTableName($tableName);
        $event->setData($fieldValues);
        return $event;
    }

    public function createChangedEvent(string $tableName, array $fieldValues, int $identifier)
    {
        $event = ChangedEvent::create();
        $event->setTableName($tableName);
        $event->setIdentifier($identifier);
        $event->setData($fieldValues);
        return $event;
    }

    public function createDeletedEvent(string $tableName, array $fieldValues, int $identifier)
    {
        $event = DeletedEvent::create();
        $event->setTableName($tableName);
        $event->setIdentifier($identifier);
        $event->setData($fieldValues);
        return $event;
    }

    public function createPurgeEvent(string $tableName, int $identifier)
    {
        $event = PurgedEvent::create();
        $event->setTableName($tableName);
        $event->setIdentifier($identifier);
        return $event;
    }
}
