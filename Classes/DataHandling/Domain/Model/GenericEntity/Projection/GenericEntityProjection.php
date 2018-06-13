<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Projection;

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
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\FromReference;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Event\BaseEvent;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Projection\Projection;
use TYPO3\CMS\EventSourcing\Core\Service\MetaModelService;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\BranchedToWorkspace;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntity\UniversalProjectionRepository;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntity\OriginProjectionRepository;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntityEventRepository;
use TYPO3\CMS\DataHandling\Install\Service\EventInitializationService;

class GenericEntityProjection implements Projection
{
    /**
     * @var UniversalProjectionRepository
     */
    private $repository;

    /**
     * @var MetaModelService
     */
    private $metaModelService;

    public function __construct()
    {
        $this->metaModelService = MetaModelService::instance();
    }

    public function listensTo()
    {
        return [
            Event\CreatedEntityEvent::class,
            Event\BranchedEntityFromEvent::class,
            Event\TranslatedEntityFromEvent::class,

            Event\ChangedEntityValuesEvent::class,
            Event\AttachedRelationEvent::class,
            Event\RemovedRelationEvent::class,
            Event\OrderedRelationsEvent::class,

            Event\DeletedEntityEvent::class,
            Event\PurgedEntityEvent::class,
        ];
    }

    /**
     * @param BaseEvent|Event\AbstractEvent $event
     */
    public function project(BaseEvent $event)
    {
        if (!($event instanceof Event\AbstractEvent)) {
            throw new \RuntimeException(
                'Invalid event type ' . get_class(($event)),
                1475400043
            );
        }

        $this->repository = UniversalProjectionRepository::create(
            $event->getAggregateType()
        );

        // entity creating
        if ($event instanceof Event\CreatedEntityEvent) {
            $this->projectCreatedEntityEvent($event);
        }
        if ($event instanceof Event\BranchedEntityFromEvent) {
            $this->projectBranchedEntityFromEvent($event);
        }
        if ($event instanceof Event\TranslatedEntityFromEvent) {
            $this->projectTranslatedEntityFromEvent($event);
        }

        // entity modification
        if ($event instanceof Event\ChangedEntityValuesEvent) {
            $this->projectChangedEntityValuesEvent($event);
        }
        if ($event instanceof Event\AttachedRelationEvent) {
            $this->projectAttachedRelationEvent($event);
        }
        if ($event instanceof Event\RemovedRelationEvent) {
            $this->projectRemovedRelationEvent($event);
        }
        if ($event instanceof Event\OrderedRelationsEvent) {
            $this->projectOrderedRelationsEvent($event);
        }

        // entity actions
        if ($event instanceof Event\DeletedEntityEvent) {
            $this->projectDeletedEntityEvent($event);
        }
        if ($event instanceof Event\PurgedEntityEvent) {
            $this->projectPurgedEntityEvent($event);
        }
    }

    private function projectCreatedEntityEvent(Event\CreatedEntityEvent $event)
    {
        if ($event->getContext()->getWorkspaceId() !== 0) {
            $this->addSpecificProjectionRepositories($event);
        } else {
            $this->addAllProjectionRepositories($event);
        }

        $data = array_merge(
            $this->getCreationData($event),
            $this->getNodeReferenceData($event->getNodeReference())
        );
        $this->repository->add($data);
    }

    private function projectBranchedEntityFromEvent(Event\BranchedEntityFromEvent $event)
    {
        $this->addSpecificProjectionRepositories($event);

        $sourceEntity = GenericEntityEventRepository::instance()
            ->findByAggregateReference(
                $event->getFromReference()->getEntityReference(),
                $event->getFromReference()->getEventId()
            );

        $data = array_merge(
            $sourceEntity->getValues(),
            $this->getCreationData($event),
            $this->getNodeReferenceData($sourceEntity->getNode())
        );

        $this->repository->add($data);
    }

    private function projectTranslatedEntityFromEvent(Event\TranslatedEntityFromEvent $event)
    {
        if ($event->getContext()->getWorkspaceId() !== 0) {
            $this->addSpecificProjectionRepositories($event);
        } elseif ($this->isBranchedToWorkspace($event)) {
            $this->addSpecificProjectionRepositories($event);
        } else {
            $this->addAllProjectionRepositories($event);
        }

        $sourceEntity = GenericEntityEventRepository::instance()
            ->findByAggregateReference(
                $event->getFromReference()->getEntityReference(),
                $event->getFromReference()->getEventId()
            );

        $data = array_merge(
            $sourceEntity->getValues(),
            $this->getCreationData($event),
            $this->getNodeReferenceData($sourceEntity->getNode())
        );

        $languagePointerField = $this->metaModelService
            ->getLanguagePointerFieldName($event->getAggregateType());
        $originalPointerField = $this->metaModelService
            ->getOriginalPointerField($event->getAggregateType());

        if ($languagePointerField !== null) {
            $data[$languagePointerField] = $this->retrieveUid(
                $sourceEntity->getSubject()
            );
        }
        if ($originalPointerField !== null) {
            $data[$originalPointerField] = $this->retrieveUid(
                $sourceEntity->getSubject()
            );
        }

        $this->repository->add($data);
    }

    private function projectChangedEntityValuesEvent(Event\ChangedEntityValuesEvent $event)
    {
        if ($event->getContext()->getWorkspaceId() !== 0) {
            $this->addSpecificProjectionRepositories($event);
        } elseif ($this->isBranchedToWorkspace($event)) {
            $this->addSpecificProjectionRepositories($event);
        } else {
            $this->addAllProjectionRepositories($event);
        }

        $this->repository->update(
            $event->getAggregateId()->toString(),
            $event->getValues()
        );
    }

    private function projectAttachedRelationEvent(Event\AttachedRelationEvent $event)
    {
        if ($event->getContext()->getWorkspaceId() !== 0) {
            $this->addSpecificProjectionRepositories($event);
        } elseif ($this->isBranchedToWorkspace($event)) {
            $this->addSpecificProjectionRepositories($event);
        } else {
            $this->addAllProjectionRepositories($event);
        }

        $this->repository->attachRelation(
            $event->getAggregateId()->toString(),
            $event->getRelationReference()
        );
    }

    private function projectRemovedRelationEvent(Event\RemovedRelationEvent $event)
    {
        if ($event->getContext()->getWorkspaceId() !== 0) {
            $this->addSpecificProjectionRepositories($event);
        } elseif ($this->isBranchedToWorkspace($event)) {
            $this->addSpecificProjectionRepositories($event);
        } else {
            $this->addAllProjectionRepositories($event);
        }

        $this->repository->removeRelation(
            $event->getAggregateId()->toString(),
            $event->getRelationReference()
        );
    }

    private function projectOrderedRelationsEvent(Event\OrderedRelationsEvent $event)
    {
        if ($event->getContext()->getWorkspaceId() !== 0) {
            $this->addSpecificProjectionRepositories($event);
        } elseif ($this->isBranchedToWorkspace($event)) {
            $this->addSpecificProjectionRepositories($event);
        } else {
            $this->addAllProjectionRepositories($event);
        }

        $this->repository->orderRelations(
            $event->getAggregateId()->toString(),
            $event->getSequence()
        );
    }

    private function projectDeletedEntityEvent(Event\DeletedEntityEvent $event)
    {
        if ($event->getContext()->getWorkspaceId() !== 0) {
            $this->addSpecificProjectionRepositories($event);
        } elseif ($this->isBranchedToWorkspace($event)) {
            $this->addSpecificProjectionRepositories($event);
        } else {
            $this->addAllProjectionRepositories($event);
        }

        $this->repository->remove(
            $event->getAggregateId()->toString()
        );
    }

    private function projectPurgedEntityEvent(Event\PurgedEntityEvent $event)
    {
        $this->repository->purge(
            $event->getAggregateId()->toString()
        );
    }

    /**
     * @param Event\AbstractEvent $event
     * @return array
     */
    private function getCreationData(Event\AbstractEvent $event)
    {
        $aggregateType = $event->getAggregateType();

        $isWorkspaceAware = $this->metaModelService
            ->isWorkspaceAware($aggregateType);
        $languageField = $this->metaModelService
            ->getLanguageFieldName($aggregateType);

        $metadata = $event->getMetadata();
        $uidValue = $event->getAggregateReference()->getUid();

        $data = [];
        $data[Common::FIELD_UUID] = $event->getAggregateId()->toString();

        if (
            $uidValue === null
            && isset($metadata[EventInitializationService::KEY_UPGRADE]['uid'])
        ) {
            $uidValue = $metadata[EventInitializationService::KEY_UPGRADE]['uid'];
        }
        if ($uidValue !== null) {
            $data['uid'] = $uidValue;
        }

        if ($isWorkspaceAware) {
            $data['t3ver_wsid'] = $event->getContext()->getWorkspaceId();
        }
        if ($languageField !== null)
        {
            $data[$languageField] = $event->getContext()->getLanguageId();
        }

        return $data;
    }

    /**
     * @param EntityReference $nodeReference
     * @return array
     */
    private function getNodeReferenceData(EntityReference $nodeReference)
    {
        $data = [];
        // use implicit value of 0 for root page
        $data['pid'] = $this->retrieveUid($nodeReference, 0);
        return $data;
    }

    /**
     * @param Event\AbstractEvent $event
     * @return bool
     */
    private function isBranchedToWorkspace(Event\AbstractEvent $event)
    {
        if ($event instanceof FromReference) {
            $aggregateReference = $event->getFromReference()->getEntityReference();
        } else {
            $aggregateReference = $event->getAggregateReference();
        }

        $branchedToWorkspace = BranchedToWorkspace::create(
            $aggregateReference,
            $event->getContext()->getWorkspaceId()
        );

        return $branchedToWorkspace->isTrue();
    }

    /**
     * Determines whether origin projection is required.
     *
     * Creating projections into origin if triggered by
     * upgrade wizard is ignored, since the that was
     * the initial source for accordant events.
     *
     * @param Event\AbstractEvent $event
     * @return bool
     */
    private function isOriginRequired(Event\AbstractEvent $event)
    {
        $metadata = $event->getMetadata();
        $triggerKey = EventInitializationService::KEY_TRIGGER;

        if (
            empty($metadata[$triggerKey])
            || $metadata[$triggerKey] !== EventInitializationService::class
        ) {
            return true;
        }

        $aggregateType = $event->getAggregateType();
        $rawValues = OriginProjectionRepository::create($aggregateType)
            ->findRawByUuid($event->getAggregateId()->toString());

        return empty($rawValues);
    }

    private function addAllProjectionRepositories(Event\AbstractEvent $event)
    {
        $this->repository->includeOrigin(
            $this->isOriginRequired($event)
        );
        $this->repository->forAll();
    }

    private function addSpecificProjectionRepositories(Event\AbstractEvent $event)
    {
        $this->repository->includeOrigin(
            $this->isOriginRequired($event)
        );
        $this->repository->forWorkspace(
            $event->getContext()->getWorkspaceId()
        );
    }

    /**
     * @param EntityReference $subject
     * @param null|int $defaultValue
     * @return null|int
     */
    private function retrieveUid(EntityReference $subject, int $defaultValue = null)
    {
        if (!empty($subject->getUid())) {
            return $subject->getUid();
        }

        $rawValues = OriginProjectionRepository::create($subject->getName())
            ->findRawByUuid($subject->getUuid());
        if (!empty($rawValues['uid'])) {
            return $subject
                ->setUid((int)$rawValues['uid'])
                ->getUid();
        }

        return $defaultValue;
    }
}
