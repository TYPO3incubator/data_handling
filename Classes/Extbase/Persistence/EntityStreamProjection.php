<?php
namespace TYPO3\CMS\DataHandling\Extbase\Persistence;

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

use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelector;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\EventStream;
use TYPO3\CMS\DataHandling\Core\Process\Projection\StreamProjecting;
use TYPO3\CMS\DataHandling\Core\Service\ProjectionService;

class EntityStreamProjection extends AbstractEntityProjection  implements StreamProjecting
{
    /**
     * @param string $streamName
     */
    public function triggerProjection(string $streamName)
    {
        ProjectionService::instance()->project(
            EventSelector::instance()->setStreamName($streamName)
        );
    }

    /**
     * @param EventStream $stream
     */
    public function project(EventStream $stream)
    {
        // if real entity cannot be processed, just process
        // registered listeners and directly return after that
        if (!$this->canProcess()) {
            foreach ($stream->forAll() as $event) {
                $this->handleListeners($event);
            }
            return;
        }

        // continue processing entity and applying events
        $subject = $this->createSubject();
        $this->eventHandler->setSubject($subject);

        foreach ($stream->forAll() as $event) {
            $this->handleListeners($event);
            $this->applyEvent($event);
        }

        // no UUID has been assigned, might happen if the stream
        // exists, but does not contain any applicable events that
        // would have been processed by an event handler
        if ($subject->getUuid() === null) {
            return;
        }

        $existingSubject = $this->fetchSubject(
            $subject->getUuidInterface()
        );

        // in case there is already a projection, try to re-use the UID
        // of that aggregate by removing it from repository and adding it
        // again with the previously used UID
        if ($existingSubject !== null) {
            $subject->_setProperty('uid', $existingSubject->getUid());
            $subject->setPid($existingSubject->getPid());

            $this->repository->remove($existingSubject);
            $this->persistenceManager->persistAll();
            $this->persistenceManager->clearState();
        }

        // always add, there's no update when streaming
        $this->repository->add($subject);
        $this->persistenceManager->persistAll();
    }

    /**
     * @param AbstractEvent $event
     */
    protected function applyEvent(AbstractEvent $event)
    {
        if ($event->isCancelled()) {
            return;
        }

        $this->eventHandler->apply($event);
    }
}
