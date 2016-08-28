<?php
namespace TYPO3\CMS\DataHandling\Install\Service;

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
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\Resolver as CompatibilityResolver;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\Database\Query\Restriction\LanguageRestriction;
use TYPO3\CMS\DataHandling\Core\DataHandling\Resolver as CoreResolver;
use TYPO3\CMS\DataHandling\Core\Domain\Command\Meta;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Meta as MetaEvent;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Context;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EventReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\State;
use TYPO3\CMS\DataHandling\Core\Domain\Repository\Meta\GenericEntityEventRepository;
use TYPO3\CMS\DataHandling\Core\MetaModel\Map;
use TYPO3\CMS\DataHandling\Core\Service\MetaModelService;
use TYPO3\CMS\DataHandling\Core\Utility\UuidUtility;

class EventInitializationService
{
    const INSTRUCTION_ENTITY = 1;
    const INSTRUCTION_VALUES = 8;
    const INSTRUCTION_RELATIONS = 16;
    const INSTRUCTION_ACTIONS = 128;

    const KEY_UPGRADE = 'upgrade';

    /**
     * @return EventInitializationService
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(EventInitializationService::class);
    }

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var int
     */
    protected $instruction = 0;

    /**
     * @param Context $context
     * @return EventInitializationService
     */
    public function setContext(Context $context): EventInitializationService
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @param int $instruction
     * @return EventInitializationService
     */
    public function setInstruction(int $instruction): EventInitializationService
    {
        $this->instruction = $instruction;
        return $this;
    }

    /**
     * @param string $tableName
     */
    public function process(string $tableName)
    {
        $predicates = [];
        $fetchQueryBuilder = $this->getQueryBuilder();

        if ($this->instruction & static::INSTRUCTION_ENTITY) {
            $predicates[] = $fetchQueryBuilder->expr()->isNull(Common::FIELD_REVISION);
        } else {
            $predicates[] = $fetchQueryBuilder->expr()->isNotNull(Common::FIELD_REVISION);
        }

        $fetchStatement = $fetchQueryBuilder
            ->select('*')
            ->from($tableName)
            ->where(...$predicates)
            ->execute();

        while ($row = $fetchStatement->fetch()) {
            $this->createEventsFor($tableName, $row);
            $this->projectRevision($tableName, $row);
        }
    }

    /**
     * @param string $tableName
     * @param array $data
     */
    protected function createEventsFor(string $tableName, array $data)
    {
        if (empty($data['uid'])) {
            throw new \RuntimeException('Value for uid must be available', 1470840257);
        }
        if (empty($data[Common::FIELD_UUID])) {
            throw new \RuntimeException('Value for uuid must be available', 1470840257);
        }

        $nodeReference = EntityReference::create('pages')->setUuid($data['pid']);
        $nodeReference->setUuid(UuidUtility::fetchUuid($nodeReference));
        $entityReference = EntityReference::fromRecord($tableName, $data);

        $genericEntity = State::instance()
            ->setNode($nodeReference)
            ->setSubject($entityReference);

        if ($this->instruction & static::INSTRUCTION_ENTITY) {
            $this->createEntityEvents($genericEntity, $data);
        } else {
            $genericEntity->getSubject()->setUid($data['uid']);
        }

        if ($this->instruction & static::INSTRUCTION_VALUES) {
            $this->createValueEvents($genericEntity, $data);
        }

        if ($this->instruction & static::INSTRUCTION_RELATIONS) {
            $this->createRelationEvents($genericEntity, $data);
        }

        if ($this->instruction & static::INSTRUCTION_ACTIONS) {
            $this->createActionEvents($genericEntity, $data);
        }
    }

    /**+
     * Creates AggregateReference command for specific context states.
     *
     * @param State $state
     * @param array $data
     */
    protected function createEntityEvents(State $state, array $data)
    {
        $tableName = $state->getSubject()->getName();
        $metadata = $this->getUpgradeMetadata($data);

        $isWorkspaceAspect = $this->isWorkspaceAspect($tableName);
        $isTranslationAspect = $this->isTranslationAspect($tableName, $data);

        $languageField = MetaModelService::instance()->getLanguageFieldName($tableName);
        $languagePointerField = MetaModelService::instance()->getLanguagePointerFieldName($tableName);
        $languageId = ($isTranslationAspect ? $data[$languageField] : 0);

        // no workspace, no translation -> just CreateEntityCommand
        if (!$isWorkspaceAspect && !$isTranslationAspect) {
            $this->handleEvent(
                MetaEvent\CreatedEntityEvent::create(
                    $state->getSubject(),
                    $state->getNode(),
                    0,
                    0
                )->setMetadata($metadata)
            );
        // at least workspace -> either CreateEntityCommand or BranchEntityCommand
        } elseif ($isWorkspaceAspect) {
            $workspaceId = $data['t3ver_wsid'];
            $versionState = VersionState::cast($data['t3ver_state']);

            if ($versionState->equals(VersionState::NEW_PLACEHOLDER_VERSION)) {
                $this->handleEvent(
                    MetaEvent\CreatedEntityEvent::create(
                        $state->getSubject(),
                        $state->getNode(),
                        $workspaceId,
                        $languageId
                    )->setMetadata($metadata)
                );
            } else {
                $liveData = $this->fetchRecordByUid($tableName, $data['t3ver_oid']);
                $liveReference = EntityReference::fromRecord($tableName, $liveData);

                $branchEntityToEvent = MetaEvent\BranchedEntityToEvent::create(
                    $liveReference,
                    $state->getSubject(),
                    $workspaceId
                );
                $this->handleEvent(
                    $branchEntityToEvent->setMetadata($metadata)
                );

                // @todo Decide whether to keep, without this event, the workspace version cannot be projected alone
                $this->handleEvent(
                    MetaEvent\BranchedEntityFromEvent::create(
                        $state->getSubject(),
                        EventReference::instance()
                            ->setEntityReference($liveReference)
                            ->setEventId($branchEntityToEvent->getEventId()),
                        $workspaceId
                    )->setMetadata($metadata)
                );
            }

        }
        // additionally translation, CreateEntityCommand or BranchEntityCommand have been issued before
        // determine whether to base TranslationCommand on live subject or branched workspace subject
        if ($isTranslationAspect) {
            $pointsToTableName = MetaModelService::instance()->getLanguagePointerTableName($tableName);
            $pointsToData = $this->fetchRecordByUid($pointsToTableName, $data[$languagePointerField]);
            $pointsToReference = EntityReference::fromRecord($pointsToTableName, $pointsToData);

            // Translation points to newly created workspace version, instead
            // of existing live version, so use workspace version as subject.
            if (
                $isWorkspaceAspect
                && VersionState::cast($data['t3ver_state'])->equals(VersionState::NEW_PLACEHOLDER_VERSION)
                && VersionState::cast($pointsToData['t3ver_state'])->equals(VersionState::NEW_PLACEHOLDER)
            ) {
                $pointsToReference = $state->getSubject();
            }

            $translatedEntityToEvent = MetaEvent\TranslatedEntityToEvent::create(
                $pointsToReference,
                $state->getSubject(),
                $languageId
            );
            $this->handleEvent(
                $translatedEntityToEvent->setMetadata($metadata)
            );

            $this->handleEvent(
                MetaEvent\TranslatedEntityFromEvent::create(
                    $state->getSubject(),
                    EventReference::instance()
                        ->setEntityReference($pointsToReference)
                        ->setEventId($translatedEntityToEvent->getEventId()),
                    $languageId
                )->setMetadata($metadata)
            );
        }
    }

    /**
     * Creates ChangedEntityEvent for assigned values.
     *
     * @param State $state
     * @param array $data
     */
    protected function createValueEvents(State $state, array $data)
    {
        $tableName = $state->getSubject()->getName();
        $metadata = $this->getUpgradeMetadata($data);

        // skip, if in valid workspace context, but record is
        // not in default version state, thus not only modified
        if (
            $this->isWorkspaceAspect($tableName)
            && !VersionState::cast($data['t3ver_state'])->equals(VersionState::DEFAULT_STATE))
        {
            return;
        }

        $temporaryState = State::instance()->setValues(
            CoreResolver\ValueResolver::instance()->resolve($state->getSubject(), $data)
        );

        $this->handleEvent(
            MetaEvent\ChangedEntityEvent::create(
                $state->getSubject(),
                $temporaryState->getValues()
            )->setMetadata($metadata)
        );
    }

    /**
     * Creates AttachedRelationEvents for relations (inline, group, select, special language).
     *
     * @param State $state
     * @param array $data
     */
    protected function createRelationEvents(State $state, array $data)
    {
        $metadata = $this->getUpgradeMetadata($data);

        $temporaryState = State::instance()->setRelations(
            CoreResolver\RelationResolver::instance()->resolve($state->getSubject(), $data)
        );

        $metaModelSchema = Map::instance()->getSchema($state->getSubject()->getName());
        foreach ($temporaryState->getRelations() as $relation) {
            $metaModelProperty = $metaModelSchema->getProperty($relation->getName());
            if ($metaModelProperty->hasActiveRelationTo($relation->getEntityReference()->getName())) {
                $this->handleEvent(
                    MetaEvent\AttachedRelationEvent::create(
                        $state->getSubject(),
                        $relation
                    )->setMetadata($metadata)
                );
            }
        }
    }

    /**
     * Creates command for specific context state.
     *
     * @param State $state
     * @param array $data
     */
    protected function createActionEvents(State $state, array $data)
    {
        $tableName = $state->getSubject()->getName();
        $metadata = $this->getUpgradeMetadata($data);

        if ($this->isWorkspaceAspect($tableName)) {
            $versionState = VersionState::cast($data['t3ver_state']);

            if ($versionState->equals(VersionState::DELETE_PLACEHOLDER)) {
                $this->handleEvent(
                    MetaEvent\DeletedEntityEvent::create(
                        $state->getSubject()
                    )->setMetadata($metadata)
                );
            } elseif ($versionState->equals(VersionState::MOVE_POINTER)) {
                // MoveBeforeCommand or MoveAfterCommand (or OrderRelationsComman for parent node)
                // @todo Implement events
            }
        }
    }

    /**
     * @param MetaEvent\AbstractEvent $event
     */
    protected function handleEvent(MetaEvent\AbstractEvent $event)
    {
        $metadata = (array)$event->getMetadata();
        $metadata['trigger'] = EventInitializationService::class;

        $aggregateType = $event->getAggregateReference()->getName();
        GenericEntityEventRepository::create($aggregateType)->addEvent($event);
    }

    /**
     * @param string $tableName
     * @return bool
     */
    protected function isWorkspaceAspect(string $tableName)
    {
        return (
            $this->context->getWorkspaceId() > 0
            && MetaModelService::instance()->isWorkspaceAware($tableName)
        );
    }

    /**
     * @param string $tableName
     * @param array $data
     * @return bool
     */
    protected function isTranslationAspect(string $tableName, array $data)
    {
        $languageField = MetaModelService::instance()->getLanguageFieldName($tableName);
        $languagePointerField = MetaModelService::instance()->getLanguagePointerFieldName($tableName);

        return (
            $this->context->getLanguageId() > 0
            && $languageField !== null
            && $languagePointerField !== null
            && $data[$languagePointerField] > 0
        );
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getUpgradeMetadata(array $data)
    {
        return [
            static::KEY_UPGRADE => [
                'uid' => $data['uid']
            ]
        ];
    }

    /**
     * @param string $tableName
     * @param array $row
     */
    protected function projectRevision(string $tableName, array $row)
    {
        $uuid = Uuid::fromString($row[Common::FIELD_UUID]);
        $revision = GenericEntityEventRepository::create($tableName)
            ->findByUuid($uuid)
            ->getRevision();

        if (
            !empty($row[Common::FIELD_REVISION])
            && $revision === $row[Common::FIELD_REVISION]
        ) {
            return;
        }

        $data[Common::FIELD_REVISION] = $revision;

        ConnectionPool::instance()->getOriginConnection()
            ->update($tableName, $data, [Common::FIELD_UUID => $uuid->toString()]);
    }

    /**
     * @param string $tableName
     * @param int $uid
     * @return array
     */
    protected function fetchRecordByUid(string $tableName, int $uid)
    {
        $fetchQueryBuilder = $this->getQueryBuilder();
        $fetchQueryBuilder->getRestrictions()
            ->removeAll()
            ->add($this->getDeletedRestriction());
        return $fetchQueryBuilder
            ->select('*')
            ->from($tableName)
            ->where($fetchQueryBuilder->expr()->eq('uid', $uid))
            ->execute()
            ->fetch();
    }

    /**
     * @param string $tableName
     * @param int $uid
     * @return array
     */
    protected function fetchVersionRecordForUid(string $tableName, int $uid)
    {
        $fetchQueryBuilder = $this->getQueryBuilder();
        $fetchQueryBuilder->getRestrictions()
            ->removeAll()
            ->add($this->getDeletedRestriction())
            ->add($this->getWorkspaceRestriction());
        return $fetchQueryBuilder
            ->select('*')
            ->from($tableName)
            ->where($fetchQueryBuilder->expr()->eq('t3ver_oid', $uid))
            ->execute()
            ->fetch();
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        $queryBuilder = ConnectionPool::instance()->getOriginQueryBuilder();
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add($this->getDeletedRestriction())
            ->add($this->getWorkspaceRestriction())
            ->add($this->getLanguageRestriction());

        return $queryBuilder;
    }

    /**
     * @return DeletedRestriction
     */
    protected function getDeletedRestriction()
    {
        return GeneralUtility::makeInstance(DeletedRestriction::class);
    }

    /**
     * @return BackendWorkspaceRestriction
     */
    protected function getWorkspaceRestriction()
    {
        if ($this->context->getWorkspaceId() === 0) {
            // in live workspace, don't include overlays
            $workspaceRestriction = GeneralUtility::makeInstance(
                BackendWorkspaceRestriction::class,
                $this->context->getWorkspaceId(),
                false
            );
        } else {
            // in a real workspace include overlays
            $workspaceRestriction = GeneralUtility::makeInstance(
                BackendWorkspaceRestriction::class,
                $this->context->getWorkspaceId(),
                true
            );
        }
        return $workspaceRestriction;
    }

    /**
     * @return LanguageRestriction
     */
    protected function getLanguageRestriction()
    {
        return LanguageRestriction::create($this->context->getLanguageId());
    }
}
