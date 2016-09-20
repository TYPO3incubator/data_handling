<?php
namespace TYPO3\CMS\DataHandling\Core\Process\Projection;

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
use TYPO3\CMS\DataHandling\Core\Domain\Model\Event\AbstractEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Model\GenericEntity;
use TYPO3\CMS\DataHandling\Core\Domain\Object\AggregateReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntityEventRepository;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\AggregateEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\BaseEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\EntityEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Process\Projection\ProjectingTrait;

class AbstractGenericEntityProjection
{
    use ProjectingTrait;

    /**
     * @param EntityReference $aggregateReference
     * @param string $eventId
     * @return GenericEntity
     */
    protected function fetchEventSubject(EntityReference $aggregateReference, string $eventId = '')
    {
        $eventRepository = GenericEntityEventRepository::create(
            $aggregateReference->getName()
        );
        return $eventRepository->findByAggregateReference(
            $aggregateReference,
            $eventId
        );
    }

    /**
     * @param AbstractEvent $event
     * @return GenericEntity
     */
    protected function provideSubject(AbstractEvent $event)
    {
        if ($event instanceof EntityEvent) {
            return GenericEntity::instance();
        } else {
            return $this->fetchEventSubject(
                $event->getAggregateReference(),
                $event->getEventId()
            );
        }
    }
}
