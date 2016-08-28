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
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EventReference;
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
     * @return GenericEntity
     */
    protected static function createNew(string $aggregateType)
    {
        $aggregateReference = EntityReference::instance()
            ->setName($aggregateType)
            ->setUuid(static::createUuid());

        $originatedEvent = MetaEvent\OriginatedEntityEvent::create(
            $aggregateReference
        );

        $genericEntity = static::instance();
        $genericEntity->subject = $aggregateReference;
        static::emitEvent(OriginEventRepository::instance(), $originatedEvent);

        return $genericEntity;
    }

    /**
     * @param string $aggregateType
     * @param int $workspaceId
     * @param string $locale
     * @return GenericEntity
     */
    public static function createdEntity(string $aggregateType, int $workspaceId, string $locale)
    {
        $genericEntity = static::createNew($aggregateType);

        $event = MetaEvent\CreatedEntityEvent::create(
            $genericEntity->getSubject(),
            $workspaceId,
            $locale
        );

        $genericEntity->apply($event);
        static::emitEvent($genericEntity->getGenericEntityRepository(), $event);

        return $genericEntity;
    }

    /**
     * @param int $workspaceId
     * @return GenericEntity
     */
    public function branchedEntityTo(int $workspaceId)
    {
        $branchedEntity = static::createNew($this->subject->getName());

        $event = MetaEvent\BranchedEntityToEvent::create(
            $this->subject,
            $branchedEntity->getSubject(),
            $workspaceId
        );

        $branchedEntity->branchedEntityFrom(
            EventReference::instance()
                ->setEntityReference($this->getSubject())
                ->setEventId($event->getEventId()),
            $workspaceId
        );

        $this->apply($event);
        static::emitEvent($this->getGenericEntityRepository(), $event);

        return $this;
    }

    /**
     * @param EventReference $fromReference
     * @param int $workspaceId
     * @return GenericEntity
     */
    public function branchedEntityFrom(EventReference $fromReference, int $workspaceId)
    {
        $event = MetaEvent\BranchedEntityFromEvent::create(
            $this->subject,
            $fromReference,
            $workspaceId
        );

        $this->apply($event);
        static::emitEvent($this->getGenericEntityRepository(), $event);

        return $this;
    }

    /**
     * @param string $locale
     * @return GenericEntity
     */
    public function translatedEntityTo(string $locale)
    {
        $translatedEntity = static::createNew($this->subject->getName());

        $event = MetaEvent\TranslatedEntityToEvent::create(
            $this->subject,
            $translatedEntity->getSubject(),
            $locale
        );

        $translatedEntity->translatedEntityFrom(
            EventReference::instance()
                ->setEntityReference($this->subject)
                ->setEventId($event->getEventId()),
            $locale
        );

        $this->apply($event);
        static::emitEvent($this->getGenericEntityRepository(), $event);

        return $this;
    }

    /**
     * @param EventReference $fromReference
     * @param string $locale
     * @return GenericEntity
     */
    public function translatedEntityFrom(EventReference $fromReference, string $locale)
    {
        $event = MetaEvent\TranslatedEntityFromEvent::create(
            $this->subject,
            $fromReference,
            $locale
        );

        $this->apply($event);
        static::emitEvent($this->getGenericEntityRepository(), $event);

        return $this;
    }

    /**
     * @param array $data
     * @return GenericEntity
     */
    public function changedEntity(array $data)
    {
        $event = MetaEvent\ChangedEntityEvent::create(
            $this->subject,
            $data
        );

        static::emitEvent($this->getGenericEntityRepository(), $event);
        $this->apply($event);

        return $this;
    }

    /**
     * @return GenericEntity
     */
    public function deletedEntity()
    {
        $event = MetaEvent\DeletedEntityEvent::create(
            $this->subject
        );

        $this->apply($event);
        static::emitEvent($this->getGenericEntityRepository(), $event);

        return $this;
    }

    /**
     * @param PropertyReference $relationReference
     * @return GenericEntity
     */
    public function attachedRelation(PropertyReference $relationReference)
    {
        $event = MetaEvent\AttachedRelationEvent::create(
            $this->subject,
            $relationReference
        );

        static::emitEvent($this->getGenericEntityRepository(), $event);
        $this->apply($event);

        return $this;
    }

    /**
     * @param PropertyReference $relationReference
     * @return GenericEntity
     */
    public function removedRelation(PropertyReference $relationReference)
    {
        $event = MetaEvent\RemovedRelationEvent::create(
            $this->subject,
            $relationReference
        );

        $this->apply($event);
        static::emitEvent($this->getGenericEntityRepository(), $event);

        return $this;
    }

    /**
     * @param AbstractSequence $sequence
     * @return GenericEntity
     */
    public function orderedRelations(AbstractSequence $sequence)
    {
        $event = MetaEvent\OrderedRelationsEvent::create(
            $this->subject,
            $sequence
        );

        $this->apply($event);
        static::emitEvent($this->getGenericEntityRepository(), $event);

        return $this;
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
    }

    protected function onBranchedEntityFromEvent(MetaEvent\BranchedEntityFromEvent $event)
    {
        $this->subject = $event->getAggregateReference();

        $aggregateReference = $event->getAggregateReference();
        $fromEntity = GenericEntityEventRepository::create($aggregateReference->getName())
            ->findByUuid($aggregateReference->getUuidInterface(), $event->getEventId());

        $this->setValues($fromEntity->getValues());
        $this->setRelations($fromEntity->getRelations());
        $this->getContext()->setWorkspaceId($event->getWorkspaceId());
    }

    protected function onTranslatedToEvent(MetaEvent\TranslatedEntityToEvent $event)
    {
    }

    protected function onTranslatedFromEvent(MetaEvent\TranslatedEntityFromEvent $event)
    {
        $this->subject = $event->getAggregateReference();

        $aggregateReference = $event->getAggregateReference();
        $fromEntity = GenericEntityEventRepository::create($aggregateReference->getName())
            ->findByUuid($aggregateReference->getUuidInterface(), $event->getEventId());

        $this->setValues($fromEntity->getValues());
        $this->setRelations($fromEntity->getRelations());
        $this->getContext()->setLanguageId($event->getLocale());
    }

    protected function onChangedEntityEvent(MetaEvent\ChangedEntityEvent $event)
    {
        $this->values = $event->getValues();
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
