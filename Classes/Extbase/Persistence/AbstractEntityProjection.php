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

use Ramsey\Uuid\UuidInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler\EventApplicable;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Repository\EventRepository;
use TYPO3\CMS\DataHandling\Core\Framework\Process\Projection\ProjectingTrait;
use TYPO3\CMS\DataHandling\Extbase\DomainObject\AbstractProjectableEntity;

abstract class AbstractEntityProjection
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
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var ProjectionRepository
     */
    protected $projectionRepository;

    /**
     * @param EventRepository $eventRepository
     * @return $this
     */
    public function setEventRepository(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
        return $this;
    }

    /**
     * @param ProjectionRepository $projectionRepository
     * @return $this
     */
    public function setProjectionRepository(ProjectionRepository $projectionRepository)
    {
        $this->projectionRepository = $projectionRepository;
        return $this;
    }

    /**
     * @return bool
     */
    protected function canProcess()
    {
        if (
            empty($this->subjectName)
            || empty($this->eventRepository)
            || empty($this->projectionRepository)
        ) {
            return false;
        }

        if (
            empty($this->eventHandler)
            && !in_array(EventApplicable::class, class_implements($this->subjectName))
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return AbstractProjectableEntity
     */
    protected function createSubject()
    {
        if (empty($this->subjectName)) {
            throw new \RuntimeException('Cannot create subject', 1471946613);
        }
        return GeneralUtility::makeInstance($this->subjectName);
    }

    /**
     * @param UuidInterface $uuid
     * @return null|AbstractProjectableEntity
     */
    protected function fetchEventSubject(UuidInterface $uuid)
    {
        if (empty($this->eventRepository)) {
            throw new \RuntimeException('Cannot fetch event subject', 1471946615);
        }
        return $this->eventRepository->findByUuid($uuid);
    }

    /**
     * @param UuidInterface $uuid
     * @return null|AbstractProjectableEntity
     */
    protected function fetchProjectionSubject(UuidInterface $uuid)
    {
        if (empty($this->projectionRepository)) {
            throw new \RuntimeException('Cannot fetch projection subject', 1471946616);
        }
        return $this->projectionRepository->findByUuid($uuid);
    }

    /**
     * @param AbstractProjectableEntity $subject
     */
    protected function persist(AbstractProjectableEntity $subject)
    {
        $existingSubject = $this->fetchProjectionSubject(
            $subject->getUuidInterface()
        );

        // directly add new entities
        if ($existingSubject === null) {
            $this->projectionRepository->add($subject);

        // in case there is already a projection, try to re-use the UID
        // of that aggregate by removing it from repository and adding it
        // again with the previously used UID
        } else {
            $subject->_setProperty('uid', $existingSubject->getUid());
            $subject->setPid($existingSubject->getPid());
            $this->projectionRepository->update($subject);
        }

        $this->persistenceManager->persistAll();
    }
}
