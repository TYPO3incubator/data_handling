<?php
namespace TYPO3\CMS\DataHandling\Core\Domain\Model\Projection;

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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Context;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Event;
use TYPO3\CMS\DataHandling\Core\Domain\Object\FromReference;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\BaseEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Process\Projection\Projection;
use TYPO3\CMS\DataHandling\Core\Service\ContextService;
use TYPO3\CMS\DataHandling\Core\Service\MetaModelService;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\BranchedToWorkspace;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntity\LocalStorageProjectionRepository;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntity\MultipleProjectionRepositoryHandler;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntity\OriginProjectionRepository;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntityEventRepository;
use TYPO3\CMS\DataHandling\Install\Service\EventInitializationService;

class GenericEntityProjection implements Projection
{
    /**
     * @var MultipleProjectionRepositoryHandler
     */
    private $handler;

    public function listensTo()
    {
        return [
            Event\CreatedEntityEvent::class,
            Event\BranchedEntityFromEvent::class,
            Event\TranslatedEntityFromEvent::class,

            Event\ModifiedEntityEvent::class,
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
        $this->handler = MultipleProjectionRepositoryHandler::instance();

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
        if ($event instanceof Event\ModifiedEntityEvent) {
            $this->projectModifiedEntityEvent($event);
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
        $addOrigin = $this->isOriginRequired($event);

        if ($event->getContext()->getWorkspaceId() !== 0) {
            $this->addSpecificProjectionRepositories($event, $addOrigin);
        } else {
            $this->addAllProjectionRepositories($event, $addOrigin);
        }

        $data = $this->getCreationData($event);
        $this->handler->add($data);
    }

    private function projectBranchedEntityFromEvent(Event\BranchedEntityFromEvent $event)
    {
        $addOrigin = $this->isOriginRequired($event);
        $this->addSpecificProjectionRepositories($event, $addOrigin);

        $sourceEntity = GenericEntityEventRepository::instance()
            ->findByAggregateReference(
                $event->getFromReference()->getEntityReference(),
                $event->getFromReference()->getEventId()
            );

        $data = array_merge(
            $sourceEntity->getValues(),
            $this->getCreationData($event)
        );

        $this->handler->add($data);
    }

    private function projectTranslatedEntityFromEvent(Event\TranslatedEntityFromEvent $event)
    {
        $addOrigin = $this->isOriginRequired($event);

        if ($event->getContext()->getWorkspaceId() !== 0) {
            $this->addSpecificProjectionRepositories($event, $addOrigin);
        } elseif ($this->isBranchedToWorkspace($event)) {
            $this->addSpecificProjectionRepositories($event, $addOrigin);
        } else {
            $this->addAllProjectionRepositories($event, $addOrigin);
        }

        $sourceEntity = GenericEntityEventRepository::instance()
            ->findByAggregateReference(
                $event->getFromReference()->getEntityReference(),
                $event->getFromReference()->getEventId()
            );

        $data = array_merge(
            $sourceEntity->getValues(),
            $this->getCreationData($event)
        );

        $this->handler->add($data);
    }

    private function projectModifiedEntityEvent(Event\ModifiedEntityEvent $event)
    {
        if ($event->getContext()->getWorkspaceId() !== 0) {
            $this->addSpecificProjectionRepositories($event);
        } elseif ($this->isBranchedToWorkspace($event)) {
            $this->addSpecificProjectionRepositories($event);
        } else {
            $this->addAllProjectionRepositories($event);
        }

        $this->handler->update(
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

        $this->handler->attachRelation(
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

        $this->handler->removeRelation(
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

        $this->handler->orderRelations(
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

        $this->handler->remove(
            $event->getAggregateId()->toString()
        );
    }

    private function projectPurgedEntityEvent(Event\PurgedEntityEvent $event)
    {
        $this->handler->purge(
            $event->getAggregateId()->toString()
        );
    }

    /**
     * @param Event\AbstractEvent $event
     * @return array
     */
    private function getCreationData(Event\AbstractEvent $event)
    {
        $metaModelService = MetaModelService::instance();
        $aggregateType = $event->getAggregateType();

        $isWorkspaceAware = $metaModelService
            ->isWorkspaceAware($aggregateType);
        $languageField = $metaModelService
            ->getLanguageFieldName($aggregateType);

        $metadata = $event->getMetadata();
        $uidValue = $event->getAggregateReference()->getUid();

        if (
            $uidValue === null
            && isset($metadata[EventInitializationService::KEY_UPGRADE]['uid'])
        ) {
            $uidValue = $metadata[EventInitializationService::KEY_UPGRADE]['uid'];
        }

        $data = [
            'uid' => $uidValue,
            Common::FIELD_UUID => $event->getAggregateId()->toString(),
        ];

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
            ->findRawByUuid($event->getAggregateId());

        return empty($rawValues);
    }

    /**
     * @param Event\AbstractEvent $event
     */
    private function addLocalStorageProjectionRepository(Event\AbstractEvent $event)
    {

        $repository = LocalStorageProjectionRepository::create(
            $this->createLocalStorageConnection($event->getContext()),
            $event->getAggregateType()
        );

        $this->handler->addRepository($repository);
    }

    /**
     * @param Event\AbstractEvent $event
     */
    private function addOriginProjectionRepository(Event\AbstractEvent $event)
    {
        $repository = OriginProjectionRepository::create(
            $event->getAggregateType()
        );

        $this->handler->addRepository($repository);
    }

    private function addAllProjectionRepositories(Event\AbstractEvent $event, bool $addOrigin = true)
    {
        if ($addOrigin) {
            $this->addOriginProjectionRepository($event);
        }

        $workspaceIds = ContextService::instance()->getWorkspaceIds();
        foreach ($workspaceIds as $workspaceId) {
            $context = Context::instance()
                ->setWorkspaceId($workspaceId);
            $repository = LocalStorageProjectionRepository::create(
                $this->createLocalStorageConnection($context),
                $event->getAggregateType()
            );

            $this->handler->addRepository($repository);
        }
    }

    private function addSpecificProjectionRepositories(Event\AbstractEvent $event, bool $addOrigin = true)
    {
        if ($addOrigin) {
            $this->addOriginProjectionRepository($event);
        }

        $this->addLocalStorageProjectionRepository($event);
    }

    /**
     * @param Context $context
     * @return Connection
     */
    private function createLocalStorageConnection(Context $context)
    {
        return ConnectionPool::instance()->provideLocalStorageConnection(
            $context->toLocalStorageName()
        );
    }
}
