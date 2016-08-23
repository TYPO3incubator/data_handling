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
use TYPO3\CMS\DataHandling\Extbase\DomainObject\AbstractProjectableEntity;

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
        // @todo Integrate override for CreateEvents in streaming mode
        foreach ($stream->forAll() as $event) {
            $this->handleListeners($this->streamListeners, $event);
            $this->projectEvent($event);
        }
    }

    /**
     * @param AbstractEvent $event
     */
    public function projectEvent(AbstractEvent $event)
    {
        $this->handleListeners($this->eventListeners, $event);

        if (
            $event->isCancelled()
            || empty($this->subjectName)
            || empty($this->repository)
            || empty($this->eventHandler)
        ) {
            return;
        }

        $subject = $this->provideSubject($event);
        if ($subject === null) {
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
     * @return null|AbstractProjectableEntity
     */
    protected function provideSubject(AbstractEvent $event)
    {
        if ($event instanceof EntityEvent) {
            return GeneralUtility::makeInstance($this->subjectName);
        } elseif ($event instanceof AggregateEvent) {
            return $this->repository->findByUuid($event->getAggregateId());
        } else {
            return null;
        }
    }
}
