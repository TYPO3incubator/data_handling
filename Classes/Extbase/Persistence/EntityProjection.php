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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Definition\AggregateEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Definition\EntityEvent;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Store\EventSelector;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\EventStream;
use TYPO3\CMS\DataHandling\Core\Process\Projection\Projecting;
use TYPO3\CMS\DataHandling\Core\Process\Projection\ProjectingTrait;
use TYPO3\CMS\DataHandling\Core\Service\ProjectionService;

class EntityProjection implements Projecting
{
    use ProjectingTrait;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
     */
    public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * @param EventStream $stream
     */
    public function projectStream(EventStream $stream)
    {
        foreach ($stream->forAll() as $event) {
            $this->handleEvent($event);
        }
    }

    /**
     * @param AbstractEvent $event
     */
    public function projectEvent(AbstractEvent $event)
    {
        if (
            empty($this->subjectName)
            || empty($this->repository)
            || empty($this->eventHandler)
        ) {
            return;
        }

        if ($event instanceof EntityEvent) {
            $subject = GeneralUtility::makeInstance($this->subjectName);
        } elseif ($event instanceof AggregateEvent) {
            $subject = $this->repository->findByUuid($event->getAggregateId());
        } else {
            return;
        }

        $this->eventHandler->setSubject($subject);
        $this->eventHandler->apply($event);

        if ($subject->_isNew()) {
            $this->repository->add($subject);
        } else {
            $this->repository->update($subject);
        }

        $this->persistenceManager->persistAll();
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
     * @param AbstractEvent $event
     */
    protected function handleEvent(AbstractEvent $event)
    {
        foreach ($this->findEventListeners($event) as $eventListener) {
            if ($event->isCancelled()) {
                break;
            }
            call_user_func(
                $eventListener,
                $this,
                $event
            );
        }

        if ($event->isCancelled()) {
            return;
        }

        $this->projectEvent($event);
    }
}
