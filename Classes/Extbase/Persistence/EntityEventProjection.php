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
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\AggregateEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\EntityEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler\EventApplicable;
use TYPO3\CMS\DataHandling\Core\Process\Projection\EventProjecting;
use TYPO3\CMS\DataHandling\Extbase\DomainObject\AbstractProjectableEntity;

class EntityEventProjection extends AbstractEntityProjection implements EventProjecting
{
    /**
     * @return EntityEventProjection
     */
    public static function instance()
    {
        return Common::getObjectManager()->get(EntityEventProjection::class);
    }

    /**
     * @param AbstractEvent $event
     */
    public function project(AbstractEvent $event)
    {
        $this->handleListeners($event);

        if ($event->isCancelled() || !$this->canProcess()) {
            return;
        }

        // clear session state (possibly modified subject)
        $this->persistenceManager->clearState();
        // fetch a fresh subject instance from storage
        $subject = $this->provideSubject($event);
        if ($subject === null) {
            return;
        }

        if ($subject instanceof EventApplicable) {
            $subject->apply($event);
        } else {
            $this->eventHandler->apply($event);
            $this->eventHandler->setSubject($subject);
        }

        $this->persist($subject);
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
            return $this->fetchEventSubject($event->getAggregateId());
        }

        return null;
    }
}
