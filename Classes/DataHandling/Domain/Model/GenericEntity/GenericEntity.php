<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity;

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

use Ramsey\Uuid\Uuid;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\DataHandling\Core\DataHandling\Resolver\RelationResolver;
use TYPO3\CMS\DataHandling\Core\DataHandling\Resolver\ValueResolver;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\Common\Context;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EventReference;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\PropertyReference;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\State;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\Sequence\AbstractSequence;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntityEventRepository;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Event\EventApplicable;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Event\EventHandlerTrait;
use TYPO3\CMS\EventSourcing\DataHandling\Infrastructure\EventStore\Saga;

class GenericEntity extends State implements EventApplicable
{
    use EventHandlerTrait;

    /**
     * @param Saga $saga
     * @return GenericEntity
     */
    public static function buildFromSaga(Saga $saga)
    {
        $genericEntity = new static();
        $saga->tell($genericEntity);
        return $genericEntity;
    }

    /**
     * @param Connection $connection
     * @param string $aggregateType
     * @param array $data
     * @return GenericEntity
     */
    public static function buildFromProjection(Connection $connection, string $aggregateType, array $data)
    {
        $aggregateReference = EntityReference::fromRecord($aggregateType, $data);

        $genericEntity = new static();
        $genericEntity->getSubject()->import($aggregateReference);

        $genericEntity->setValues(
            ValueResolver::instance()
                ->resolve($genericEntity->getSubject(), $data)
        );
        $genericEntity->setRelations(
            RelationResolver::create($connection)
                ->resolve($genericEntity->getSubject(), $data)
        );

        return $genericEntity;
    }

    /*
     * Command handling
     */

    /**
     * @param EntityReference $subject
     * @return EntityReference
     */
    protected static function createNewReference(EntityReference $subject)
    {
        // @todo Analyse whether it's good to re-use a given UUID
        $reference = EntityReference::create($subject->getName());
        if ($subject->getUuid() !== null) {
            $reference->setUuid($subject->getUuid());
        } else {
            $reference->setUuid(Uuid::uuid4()->toString());
        }
        return $reference;
    }

    /**
     * @param Context $context
     * @param EntityReference $subject
     * @return static
     */
    protected static function createNewEntity(Context $context, EntityReference $subject)
    {
        $aggregateReference = static::createNewReference($subject);
        $event = Event\OriginatedEntityEvent::create(
            $context,
            $aggregateReference
        );

        $genericEntity = new static();
        $genericEntity->subject = $aggregateReference;
        $genericEntity->manageEvent($event);

        return $genericEntity;
    }

    /**
     * @param Context $context
     * @param EntityReference $aggregateReference
     * @param EntityReference $nodeReference
     * @return static
     */
    public static function createEntity(Context $context, EntityReference $aggregateReference, EntityReference $nodeReference)
    {
        $genericEntity = static::createNewEntity($context, $aggregateReference);

        $event = Event\CreatedEntityEvent::create(
            $context,
            $genericEntity->getSubject(),
            $nodeReference
        );

        $genericEntity->manageEvent($event);

        return $genericEntity;
    }

    /**
     * @param Context $context
     * @return static
     */
    public function branchEntityTo(Context $context)
    {
        $branchedEntity = static::createNewEntity(
            $context,
            EntityReference::create($this->subject->getName())
        );

        $event = Event\BranchedEntityToEvent::create(
            $context,
            $this->subject,
            $branchedEntity->getSubject()
        );

        $branchedEntity->branchEntityFrom(
            $context,
            EventReference::instance()
                ->setEntityReference($this->getSubject())
                ->setEventId($event->getEventId())
        );

        $this->manageEvent($event);

        return $branchedEntity;
    }

    /**
     * @param Context $context
     * @param EventReference $fromReference
     * @return static
     */
    public function branchEntityFrom(Context $context, EventReference $fromReference)
    {
        $event = Event\BranchedEntityFromEvent::create(
            $context,
            $this->subject,
            $fromReference
        );

        $this->manageEvent($event);

        return $this;
    }

    /**
     * @param Context $context
     * @return static
     */
    public function translateEntityTo(Context $context)
    {
        $translatedEntity = static::createNewEntity(
            $context,
            EntityReference::create($this->subject->getName())
        );

        $event = Event\TranslatedEntityToEvent::create(
            $context,
            $this->subject,
            $translatedEntity->getSubject()
        );

        $translatedEntity->translateEntityFrom(
            $context,
            EventReference::instance()
                ->setEntityReference($this->subject)
                ->setEventId($event->getEventId())
        );

        $this->manageEvent($event);

        return $translatedEntity;
    }

    /**
     * @param Context $context
     * @param EventReference $fromReference
     * @return static
     */
    public function translateEntityFrom(Context $context, EventReference $fromReference)
    {
        $event = Event\TranslatedEntityFromEvent::create(
            $context,
            $this->subject,
            $fromReference
        );

        $this->manageEvent($event);

        return $this;
    }

    /**
     * @param Context $context
     * @param array $data
     */
    public function modifyEntity(Context $context, array $data)
    {
        $event = Event\ModifiedEntityEvent::create(
            $context,
            $this->subject,
            $data
        );

        $this->manageEvent($event);
    }

    /**
     * @param Context $context
     */
    public function deleteEntity(Context $context)
    {
        $event = Event\DeletedEntityEvent::create(
            $context,
            $this->subject
        );

        $this->manageEvent($event);
    }

    /**
     * @param Context $context
     * @param PropertyReference $relationReference
     */
    public function attachRelation(Context $context, PropertyReference $relationReference)
    {
        $event = Event\AttachedRelationEvent::create(
            $context,
            $this->subject,
            $relationReference
        );

        $this->manageEvent($event);
    }

    /**
     * @param Context $context
     * @param PropertyReference $relationReference
     */
    public function removeRelation(Context $context, PropertyReference $relationReference)
    {
        $event = Event\RemovedRelationEvent::create(
            $context,
            $this->subject,
            $relationReference
        );

        $this->manageEvent($event);
    }

    /**
     * @param Context $context
     * @param AbstractSequence $sequence
     */
    public function orderRelations(Context $context, AbstractSequence $sequence)
    {
        $event = Event\OrderedRelationsEvent::create(
            $context,
            $this->subject,
            $sequence
        );

        $this->manageEvent($event);
    }


    /*
     * Event handling
     */

    protected function applyCreatedEntityEvent(Event\CreatedEntityEvent $event)
    {
        $this->node = $event->getNodeReference();
        $this->subject = $event->getAggregateReference();
    }

    protected function applyBranchedEntityToEvent(Event\BranchedEntityToEvent $event)
    {
    }

    protected function applyBranchedEntityFromEvent(Event\BranchedEntityFromEvent $event)
    {
        $this->subject = $event->getAggregateReference();

        $fromEntity = GenericEntityEventRepository::instance()
            ->findByAggregateReference(
                $event->getFromReference()->getEntityReference(),
                $event->getFromReference()->getEventId()
            );

        $this->setNode(
            EntityReference::instance()->import(
                $fromEntity->getNode()
            )
        );
        $this->setValues(
            $fromEntity->getValues()
        );
        $this->setRelations(
            $fromEntity->getRelations()
        );
        $this->setContext(
            Context::create($event->getContext()->getWorkspaceId())
        );
    }

    protected function applyTranslatedToEvent(Event\TranslatedEntityToEvent $event)
    {
    }

    protected function applyTranslatedFromEvent(Event\TranslatedEntityFromEvent $event)
    {
        $this->subject = $event->getAggregateReference();

        $fromEntity = GenericEntityEventRepository::instance()
            ->findByAggregateReference(
                $event->getFromReference()->getEntityReference(),
                $event->getFromReference()->getEventId()
            );

        $this->setNode(
            EntityReference::instance()->import(
                $fromEntity->getNode()
            )
        );
        $this->setValues(
            $fromEntity->getValues()
        );
        $this->setRelations(
            $fromEntity->getRelations()
        );
        $this->setContext(
            Context::create(
                $this->getContext()->getWorkspaceId(),
                $event->getContext()->getLanguageId()
            )
        );
    }

    protected function applyModifiedEntityEvent(Event\ModifiedEntityEvent $event)
    {
        $this->values = $event->getValues();
    }

    protected function applyDeletedEntityEvent(Event\DeletedEntityEvent $event)
    {
        // @todo Create and apply meta-state for entity
    }

    protected function applyAttachedRelationEvent(Event\AttachedRelationEvent $event)
    {
        $this->relations[] = $event->getRelationReference();
    }

    protected function applyRemovedRelationEvent(Event\RemovedRelationEvent $event)
    {
        $relationIndex = array_search(
            $event->getRelationReference(),
            $this->relations,
            true
        );
        if ($relationIndex !== false) {
            unset($this->relations[$relationIndex]);
        }
    }

    protected function applyOrderedRelationsEvent(Event\OrderedRelationsEvent $event)
    {
        $relations = [];

        foreach($event->getSequence()->get() as $orderedRelation) {
            if (!in_array($orderedRelation, $this->relations, true)) {
                throw new \RuntimeException(
                    'Cannot define order with non-existing relation',
                    1471101357
                );
            }
            $relations[] = $orderedRelation;
        }

        $this->relations = $relations;
    }
}
