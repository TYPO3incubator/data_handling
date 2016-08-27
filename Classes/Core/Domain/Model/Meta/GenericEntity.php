<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Meta;

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
use TYPO3\CMS\DataHandling\Core\Domain\Event\Meta as MetaEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\PropertyReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\State;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Sequence\AbstractSequence;
use TYPO3\CMS\DataHandling\Core\Domain\Repository\Meta\GenericEntityEventRepository;
use TYPO3\CMS\DataHandling\Core\Domain\Repository\Meta\OriginEventRepository;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler\CommandHandlerTrait;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler\EventApplicable;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Handler\EventHandlerTrait;

class GenericEntity extends State implements EventApplicable
{
    use CommandHandlerTrait;
    use EventHandlerTrait;

    /**
     * @return GenericEntity
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(GenericEntity::class);
    }

    /*
     * Command handling
     */

    /**
     * @param string $aggregateType
     * @param int $workspaceId
     * @param string $locale
     * @return GenericEntity
     */
    public static function createdEntity(string $aggregateType, int $workspaceId, string $locale)
    {
        $aggregateReference = EntityReference::instance()
            ->setName($aggregateType)
            ->setUuid(static::createUuid());

        $originatedEvent = MetaEvent\OriginatedEntityEvent::create(
            $aggregateReference
        );

        $event = MetaEvent\CreatedEntityEvent::create(
            $aggregateReference,
            $workspaceId,
            $locale
        );

        $genericEntity = static::instance();
        $genericEntity->apply($event);
        // applied first, to assign aggregateType
        static::emitEvent(OriginEventRepository::instance(), $originatedEvent);
        static::emitEvent($genericEntity->getGenericEntityRepository(), $event);

        return $genericEntity;
    }

    /**
     * @param EntityReference $fromReference
     * @param int $workspaceId
     * @return GenericEntity
     */
    public static function branchedEntityFrom(EntityReference $fromReference, int $workspaceId)
    {
        $aggregateReference = EntityReference::instance()
            ->setName($fromReference->getName())
            ->setUuid(static::createUuid());

        $event = MetaEvent\BranchedEntityFromEvent::create(
            $aggregateReference,
            $fromReference,
            $workspaceId
        );

        $genericEntity = static::instance();
        $genericEntity->apply($event);
        // applied first, to assign aggregateType
        static::emitEvent($genericEntity->getGenericEntityRepository(), $event);

        return $genericEntity;
    }

    /**
     * @param EntityReference $fromReference
     * @param string $locale
     * @return GenericEntity
     */
    public static function translatedEntityFrom(EntityReference $fromReference, string $locale)
    {
        $aggregateReference = EntityReference::instance()
            ->setName($fromReference->getName())
            ->setUuid(static::createUuid());

        $event = MetaEvent\TranslatedEntityFromEvent::create(
            $aggregateReference,
            $fromReference,
            $locale
        );

        $genericEntity = static::instance();
        $genericEntity->apply($event);
        // applied first, to assign aggregateType
        static::emitEvent($genericEntity->getGenericEntityRepository(), $event);

        return $genericEntity;
    }

    /**
     * @param int $workspaceId
     */
    public function branchedEntityTo(int $workspaceId)
    {
        $branchedEntity = static::branchedEntityFrom(
            $this->subject,
            $workspaceId
        );

        $event = MetaEvent\BranchedEntityToEvent::create(
            $this->subject,
            $branchedEntity->getSubject(),
            $workspaceId
        );

        static::emitEvent($this->getGenericEntityRepository(), $event);
        $this->apply($event);
    }

    /**
     * @param string $locale
     */
    public function translatedEntityTo(string $locale)
    {
        $translatedEntity = static::translatedEntityFrom(
            $this->subject,
            $locale
        );

        $event = MetaEvent\TranslatedEntityToEvent::create(
            $this->subject,
            $translatedEntity->getSubject(),
            $locale
        );

        static::emitEvent($this->getGenericEntityRepository(), $event);
        $this->apply($event);
    }

    /**
     * @param array $data
     */
    public function changedEntity(array $data)
    {
        $event = MetaEvent\ChangedEntityEvent::create(
            $this->subject,
            $data
        );

        static::emitEvent($this->getGenericEntityRepository(), $event);
        $this->apply($event);
    }

    public function deletedEntity()
    {
        $event = MetaEvent\DeletedEntityEvent::create(
            $this->subject
        );

        static::emitEvent($this->getGenericEntityRepository(), $event);
        $this->apply($event);
    }

    /**
     * @param PropertyReference $relationReference
     */
    public function attachedRelation(PropertyReference $relationReference)
    {
        $event = MetaEvent\AttachedRelationEvent::create(
            $this->subject,
            $relationReference
        );

        static::emitEvent($this->getGenericEntityRepository(), $event);
        $this->apply($event);
    }

    /**
     * @param PropertyReference $relationReference
     */
    public function removedRelation(PropertyReference $relationReference)
    {
        $event = MetaEvent\RemovedRelationEvent::create(
            $this->subject,
            $relationReference
        );

        static::emitEvent($this->getGenericEntityRepository(), $event);
        $this->apply($event);
    }

    /**
     * @param AbstractSequence $sequence
     */
    public function orderedRelations(AbstractSequence $sequence)
    {
        $event = MetaEvent\OrderedRelationsEvent::create(
            $this->subject,
            $sequence
        );

        static::emitEvent($this->getGenericEntityRepository(), $event);
        $this->apply($event);
    }


    /*
     * Event handling
     */

    protected function onCreatedEntityEvent(MetaEvent\CreatedEntityEvent $event)
    {
        $this->subject = $event->getAggregateReference();
    }

    protected function onBranchedEntityToEvent(MetaEvent\BranchedEntityToEvent $event)
    {
        $this->subject = $event->getAggregateReference();
    }

    protected function onBranchedEntityFromEvent(MetaEvent\BranchedEntityFromEvent $event)
    {
        $this->subject = $event->getAggregateReference();
    }

    protected function onTranslatedToEvent(MetaEvent\TranslatedEntityToEvent $event)
    {
        $this->subject = $event->getAggregateReference();
    }

    protected function onTranslatedFromEvent(MetaEvent\TranslatedEntityFromEvent $event)
    {
        $this->subject = $event->getAggregateReference();
    }

    protected function onChangedEntityEvent(MetaEvent\ChangedEntityEvent $event)
    {
        $this->values = $event->getData();
    }

    protected function onDeletedEntityEvent(MetaEvent\DeletedEntityEvent $event)
    {
        // @todo Create and apply meta-state for entity
    }

    protected function handleAttachedRelationEvent(MetaEvent\AttachedRelationEvent $event)
    {
        $this->attachRelation($event->getRelationReference());
    }

    protected function handleRemovedRelationEvent(MetaEvent\RemovedRelationEvent $event)
    {
        $this->removeRelation($event->getRelationReference());
    }

    protected function handleOrderedRelationsEvent(MetaEvent\OrderedRelationsEvent $event)
    {
        $this->orderRelations($event->getSequence()->get());
    }

    /**
     * @return GenericEntityEventRepository
     */
    protected function getGenericEntityRepository()
    {
        return GenericEntityEventRepository::create(
            $this->subject->getName()
        );
    }
}
