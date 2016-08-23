<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Handler;

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

use H4ck3r31\BankAccountExample\Domain\Repository\EventRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\Process\EventPublisher;

trait CommandHandlerTrait
{
    /**
     * @return UuidInterface
     */
    protected static function createUuid()
    {
        return Uuid::uuid4();
    }

    /**
     * @param EventRepository $repository
     * @param AbstractEvent $event
     */
    protected function provideEvent(EventRepository $repository, AbstractEvent $event)
    {
        $repository->addEvent($event);
        EventPublisher::instance()->publish($event);
    }
}
