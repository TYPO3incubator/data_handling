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
use TYPO3\CMS\DataHandling\Core\Domain\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Definition\AggregateEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Definition\EntityEvent;
use TYPO3\CMS\DataHandling\Core\Process\Projection\ProjectingTrait;
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
     * @return bool
     */
    protected function canProcess()
    {
        return (
            !empty($this->subjectName)
            && !empty($this->repository)
            && !empty($this->eventHandler)
        );
    }

    /**
     * @param AbstractEvent $event
     * @return null|AbstractProjectableEntity
     */
    protected function provideSubject(AbstractEvent $event)
    {
        if ($event instanceof EntityEvent) {
            return $this->createSubject();
        } elseif ($event instanceof AggregateEvent) {
            return $this->fetchSubject($event->getAggregateId());
        } else {
            return null;
        }
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
    protected function fetchSubject(UuidInterface $uuid)
    {
        if (empty($this->repository)) {
            throw new \RuntimeException('Cannot fetch subject', 1471946615);
        }
        return $this->repository->findByUuid($uuid);
    }
}
