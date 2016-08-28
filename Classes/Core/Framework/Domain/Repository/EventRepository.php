<?php
namespace TYPO3\CMS\DataHandling\Core\Framework\Domain\Repository;

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
use TYPO3\CMS\DataHandling\Core\EventSourcing\Saga;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\BaseEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler\EventApplicable;

/**
 * The event repository for Accounts
 */
interface EventRepository
{
    /**
     * @param UuidInterface $uuid
     * @param string $eventId
     * @param string $type
     * @return EventApplicable
     */
    public function findByUuid(UuidInterface $uuid, string $eventId = '', string $type = Saga::EVENT_EXCLUDING);

    /**
     * @param BaseEvent $event
     */
    public function addEvent(BaseEvent $event);
}
