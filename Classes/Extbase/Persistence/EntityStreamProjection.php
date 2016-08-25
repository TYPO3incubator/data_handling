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

use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler\EventApplicable;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelector;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\EventStream;
use TYPO3\CMS\DataHandling\Core\Process\Projection\StreamProjecting;
use TYPO3\CMS\DataHandling\Core\Service\ProjectionService;
use TYPO3\CMS\DataHandling\Extbase\DomainObject\AbstractProjectableEntity;

class EntityStreamProjection extends AbstractEntityProjection implements StreamProjecting
{
    /**
     * @return EntityStreamProjection
     */
    public static function instance()
    {
        return Common::getObjectManager()->get(EntityStreamProjection::class);
    }

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
            foreach ($stream as $event) {
                $this->handleListeners($event);
            }
            return;
        }

        // clear session state (possibly modified subject)
        $this->persistenceManager->clearState();
        // continue processing entity and applying events
        $subject = $this->createSubject();

        if (!($subject instanceof EventApplicable)) {
            $this->eventHandler->setSubject($subject);
        }

        foreach ($stream as $event) {
            $this->handleListeners($event);
            $this->applyEvent($subject, $event);
        }

        // no UUID has been assigned, might happen if the stream
        // exists, but does not contain any applicable events that
        // would have been processed by an event handler
        if ($subject->getUuid() === null) {
            return;
        }

        $this->persist($subject);
    }

    /**
     * @param AbstractProjectableEntity $subject
     * @param AbstractEvent $event
     */
    protected function applyEvent(AbstractProjectableEntity $subject, AbstractEvent $event)
    {
        if ($event->isCancelled()) {
            return;
        }

        if ($subject instanceof EventApplicable) {
            $subject->apply($event);
        } else {
            $this->eventHandler->apply($event);
        }
    }
}
