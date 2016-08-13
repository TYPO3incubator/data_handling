<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Generic;

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
use TYPO3\CMS\DataHandling\Core\Domain\Event\Generic as GenericEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\State;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Applicable;

class ReadState extends State implements Applicable
{
    /**
     * @return ReadState
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(ReadState::class);
    }

    /**
     * @param AbstractEvent $event
     * @return ReadState
     */
    public function apply(AbstractEvent $event)
    {
        $eventNameParts = GeneralUtility::trimExplode('\\', get_class($event), true);
        $eventName = $eventNameParts[count($eventNameParts) - 1];
        $callable = array($this, 'apply' . ucfirst($eventName));

        if (is_callable($callable)) {
            call_user_func($callable, $event);
        }

        return $this;
    }

    public function applyCreateEvent(GenericEvent\CreatedEvent $event)
    {
        $this->reference = $event->getIdentity();
    }

    public function applyBranchEvent(GenericEvent\BranchedEvent $event)
    {
        $this->reference = $event->getIdentity();
    }

    public function applyTranslateEvent(GenericEvent\TranslatedEvent $event)
    {
        $this->reference = $event->getIdentity();
    }

    public function applyChangedEvent(GenericEvent\ChangedEvent $event)
    {
        $this->values = $event->getData();
    }

    public function applyDeletedEvent(GenericEvent\DeletedEvent $event)
    {
        // @todo Create and apply meta-state for entity
    }

    public function handleAttachRelationCommand(GenericEvent\AttachedRelationEvent $event)
    {
        $this->attachRelation($event->getRelation());
        return $this;
    }

    public function handleRemoveRelationCommand(GenericEvent\RemovedRelationEvent $event)
    {
        $this->removeRelation($event->getRelation());
        return $this;
    }

    public function handleOrderRelationsCommand(GenericEvent\OrderedRelationsEvent $event)
    {
        $this->orderRelations($event->getSequence()->get());
        return $this;
    }
}
