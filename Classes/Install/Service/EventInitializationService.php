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

use Doctrine\DBAL\Driver\Statement;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\Resolver as CompatibilityResolver;
use TYPO3\CMS\EventSourcing\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\Database\Query\Restriction\LanguageRestriction;
use TYPO3\CMS\DataHandling\Core\DataHandling\Resolver as CoreResolver;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\SuggestedState;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Command;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\Common\Context;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntityEventRepository;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\RelationMap;
use TYPO3\CMS\DataHandling\Core\Service\MetaModelService;
use TYPO3\CMS\DataHandling\Core\Utility\UuidUtility;
use TYPO3\CMS\DataHandling\Install\Domain\Model\MigrationEntity;

class EventInitializationService
{
    const INSTRUCTION_ENTITY = 1;
    const INSTRUCTION_VALUES = 8;
    const INSTRUCTION_RELATIONS = 16;
    const INSTRUCTION_ACTIONS = 128;

    const KEY_UPGRADE = 'upgrade';
    const KEY_TRIGGER = 'trigger';

    /**
     * @param Context $context
     * @return EventInitializationService
     */
    public static function create(Context $context)
    {
        return GeneralUtility::makeInstance(static::class, $context);
    }

    /**
     * @var Context
     */
    private $context;

    /**
     * @var int
     */
    private $instruction = 0;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context= $context;
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
        if (
            $this->isNotWorkspaceAware($tableName)
            || $this->isNotLanguageAware($tableName)
        ) {
            return;
        }

        if ($this->context->getLanguageId() > 0) {
            $tableName = MetaModelService::instance()
                ->getLanguageTableName($tableName);
        }

        $fetchStatement = $this->createFetchStatement($tableName);

        while ($row = $fetchStatement->fetch()) {
            $this->createEventsFor($tableName, $row);
            $this->defineInitialRevision($tableName, $row);
        }
    }

    /**
     * @param string $tableName
     * @param array $data
     */
    private function createEventsFor(string $tableName, array $data)
    {
        if (empty($data['uid'])) {
            throw new \RuntimeException('Value for uid must be available', 1470840257);
        }
        if (empty($data[Common::FIELD_UUID])) {
            throw new \RuntimeException('Value for uuid must be available', 1470840257);
        }

        $migrationEntity = $this->createMigrationEntity(
            $tableName, $data,
            $this->getUpgradeMetadata($data)
        );

        if ($this->instruction & static::INSTRUCTION_ENTITY) {
            $this->createEntityEvents($migrationEntity, $data);
        }

        if ($this->instruction & static::INSTRUCTION_VALUES) {
            $this->createValueEvents($migrationEntity, $data);
        }

        if ($this->instruction & static::INSTRUCTION_RELATIONS) {
            $this->createRelationEvents($migrationEntity, $data);
        }

        if ($this->instruction & static::INSTRUCTION_ACTIONS) {
            $this->createActionEvents($migrationEntity, $data);
        }
    }

    /**+
     * Creates AggregateReference command for specific context states.
     *
     * @param MigrationEntity $migrationEntity
     * @param array $data
     */
    private function createEntityEvents(MigrationEntity $migrationEntity, array $data)
    {
        /** @var MigrationEntity[] $entities */
        $entities = [];
        $metadata = $this->getUpgradeMetadata($data);
        $tableName = $migrationEntity->getSubject()->getName();

        $isWorkspaceAspect = $this->isWorkspaceAspect($tableName, $data);
        $isTranslationAspect = $this->isTranslationAspect($tableName, $data);

        $context = $this->determineContext($tableName, $data);

        // no workspace, no translation -> just CreateEntityCommand
        if (!$isWorkspaceAspect && !$isTranslationAspect) {
            $entities['create'] = MigrationEntity::createEntityMigration(
                $context,
                $migrationEntity->getSubject(),
                $migrationEntity->getNode(),
                $metadata
            );
        // at least workspace -> either CreateEntityCommand or BranchEntityCommand
        } elseif ($isWorkspaceAspect) {
            $versionState = VersionState::cast($data['t3ver_state']);

            if ($versionState->equals(VersionState::NEW_PLACEHOLDER_VERSION)) {
                $entities['create'] = MigrationEntity::createEntityMigration(
                    $context,
                    $migrationEntity->getSubject(),
                    $migrationEntity->getNode(),
                    $metadata
                );
            } else {
                $liveData = $this->fetchRecordByUid($tableName, $data['t3ver_oid']);
                $liveEntity = $this->createMigrationEntity(
                    $tableName, $liveData,
                    $this->getUpgradeMetadata($liveData)
                );

                $entities['branch'] = $liveEntity->branchEntityToMigration(
                    $context,
                    $migrationEntity->getSubject(),
                    $metadata
                );
            }

        }
        // additionally translation, CreateEntityCommand or BranchEntityCommand have been issued before
        // determine whether to base TranslationCommand on live subject or branched workspace subject
        if ($isTranslationAspect) {
            $languagePointerField = MetaModelService::instance()->getLanguagePointerFieldName($tableName);
            $pointsToTableName = MetaModelService::instance()->getLanguagePointerTableName($tableName);
            $pointsToData = $this->fetchRecordByUid($pointsToTableName, $data[$languagePointerField]);
            $pointsToEntity = $this->createMigrationEntity(
                $pointsToTableName, $pointsToData,
                $this->getUpgradeMetadata($pointsToData)
            );

            // Skip the case that translation points to newly created workspace
            // version (instead of pointing to existing live version)...
            if (
                !$isWorkspaceAspect
                || !VersionState::cast($data['t3ver_state'])->equals(VersionState::NEW_PLACEHOLDER_VERSION)
                || !VersionState::cast($pointsToData['t3ver_state'])->equals(VersionState::NEW_PLACEHOLDER)
            ) {
                $entities['translate'] = $pointsToEntity->translateEntityToMigration(
                    $context,
                    $migrationEntity->getSubject(),
                    $metadata
                );
            }
        }

        foreach ($entities as $entity) {
            GenericEntityEventRepository::instance()->commit($entity);
        }
    }

    /**
     * Creates ModifiedEntityEvent for assigned values.
     *
     * @param MigrationEntity $migrationEntity
     * @param array $data
     */
    private function createValueEvents(MigrationEntity $migrationEntity, array $data)
    {
        $tableName = $migrationEntity->getSubject()->getName();
        $context = $this->determineContext($tableName, $data);

        // skip, if in valid workspace context, but record is
        // not in default version state, thus not only modified
        if (
            $this->isWorkspaceAspect($tableName, $data)
            && !VersionState::cast($data['t3ver_state'])->equals(
                VersionState::DEFAULT_STATE
            )
        )
        {
            return;
        }

        $temporaryState = SuggestedState::instance()->setValues(
            CoreResolver\ValueResolver::instance()->resolve(
                $migrationEntity->getSubject(), $data
            )
        );

        $migrationEntity->modifyEntity($context, $temporaryState->getValues());
        GenericEntityEventRepository::instance()->commit($migrationEntity);
    }

    /**
     * Creates AttachedRelationEvents for relations (inline, group, select, special language).
     *
     * @param MigrationEntity $migrationEntity
     * @param array $data
     */
    private function createRelationEvents(MigrationEntity $migrationEntity, array $data)
    {
        $tableName = $migrationEntity->getSubject()->getName();
        $context = $this->determineContext($tableName, $data);

        $relationResolver = CoreResolver\RelationResolver::create(
            ConnectionPool::instance()->getOriginConnection()
        );
        $temporaryState = SuggestedState::instance()->setRelations(
            $relationResolver->resolve(
                $migrationEntity->getSubject(),
                $data
            )
        );

        $metaModelSchema = RelationMap::provide()->getSchema($migrationEntity->getSubject()->getName());
        foreach ($temporaryState->getRelations() as $relation) {
            $metaModelProperty = $metaModelSchema->getProperty($relation->getName());
            if ($metaModelProperty->hasActiveRelationTo($relation->getEntityReference()->getName())) {
                $migrationEntity->attachRelation($context, $relation);
            }
        }

        GenericEntityEventRepository::instance()->commit($migrationEntity);
    }

    /**
     * Creates command for specific context state.
     *
     * @param MigrationEntity $migrationEntity
     * @param array $data
     */
    private function createActionEvents(MigrationEntity $migrationEntity, array $data)
    {
        $tableName = $migrationEntity->getSubject()->getName();
        $context = $this->determineContext($tableName, $data);

        if ($this->isWorkspaceAspect($tableName, $data)) {
            $versionState = VersionState::cast($data['t3ver_state']);

            if ($versionState->equals(VersionState::DELETE_PLACEHOLDER)) {
                $migrationEntity->deleteEntity($context);
            } elseif ($versionState->equals(VersionState::MOVE_POINTER)) {
                // MoveBeforeCommand or MoveAfterCommand (or OrderRelationsComman for parent node)
                // @todo Implement events
            }
        }

        GenericEntityEventRepository::instance()->commit($migrationEntity);
    }

    /**
     * @param string $tableName
     * @param array $data
     * @return bool
     */
    private function isWorkspaceAspect(string $tableName, array $data)
    {
        return (
            $this->context->getWorkspaceId() > 0
            && MetaModelService::instance()->isWorkspaceAware($tableName)
            && isset($data['t3ver_wsid']) && $data['t3ver_wsid'] > 0
        );
    }

    /**
     * @param string $tableName
     * @return bool
     */
    private function isNotWorkspaceAware(string $tableName)
    {
        return (
            $this->context->getWorkspaceId() > 0
            && !MetaModelService::instance()->isWorkspaceAware($tableName)
        );
    }

    /**
     * @param string $tableName
     * @param array $data
     * @return bool
     */
    private function isTranslationAspect(string $tableName, array $data)
    {
        $languageField = MetaModelService::instance()->getLanguageFieldName($tableName);
        $languagePointerField = MetaModelService::instance()->getLanguagePointerFieldName($tableName);

        return (
            $this->context->getLanguageId() > 0
            && $languageField !== null
            && $languagePointerField !== null
            && isset($data[$languagePointerField])
            && $data[$languagePointerField] > 0
        );
    }

    /**
     * @param string $tableName
     * @return bool
     */
    private function isNotLanguageAware(string $tableName)
    {
        $languageField = MetaModelService::instance()->getLanguageFieldName($tableName);

        return (
            $this->context->getLanguageId() > 0
            && $languageField === null
        );
    }

    /**
     * @param array $data
     * @return array
     */
    private function getUpgradeMetadata(array $data)
    {
        return [
            static::KEY_UPGRADE => [
                'uid' => $data['uid']
            ],
            static::KEY_TRIGGER => EventInitializationService::class,
        ];
    }

    /**
     * @param string $tableName
     * @param int $uid
     * @return array
     */
    private function fetchRecordByUid(string $tableName, int $uid)
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
     * @param array $data
     */
    private function defineInitialRevision(string $tableName, array $data)
    {
        ConnectionPool::instance()->getOriginConnection()->update(
            $tableName,
            [Common::FIELD_REVISION => 0],
            [Common::FIELD_UUID => $data[Common::FIELD_UUID]]
        );
    }

    /**
     * @param string $tableName
     * @return Statement
     */
    private function createFetchStatement(string $tableName)
    {
        $fetchQueryBuilder = $this->getQueryBuilder();
        $metaModelService = MetaModelService::instance();

        $predicates = [];
        if ($this->instruction & static::INSTRUCTION_ENTITY) {
            $predicates[] = $fetchQueryBuilder->expr()->isNull(Common::FIELD_REVISION);
        } else {
            $predicates[] = $fetchQueryBuilder->expr()->isNotNull(Common::FIELD_REVISION);
        }

        $orderBy = $metaModelService->getOrderByExpression($tableName);
        if ($orderBy === null) {
            $orderBy = $metaModelService->getSortingField($tableName);
        }
        if ($orderBy !== null) {
            foreach (QueryHelper::parseOrderBy($orderBy) as $fieldAndDirection) {
                $fetchQueryBuilder->addOrderBy(...$fieldAndDirection);
            }
        }

        $fetchStatement = $fetchQueryBuilder
            ->select('*')
            ->from($tableName)
            ->where(...$predicates)
            ->execute();

        return $fetchStatement;
    }

    /**
     * @return QueryBuilder
     */
    private function getQueryBuilder()
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
    private function getDeletedRestriction()
    {
        return GeneralUtility::makeInstance(DeletedRestriction::class);
    }

    /**
     * @return BackendWorkspaceRestriction
     */
    private function getWorkspaceRestriction()
    {
        return GeneralUtility::makeInstance(
            BackendWorkspaceRestriction::class,
            $this->context->getWorkspaceId(),
            false
        );
    }

    /**
     * @return LanguageRestriction
     */
    private function getLanguageRestriction()
    {
        return LanguageRestriction::create($this->context->getLanguageId());
    }

    /**
     * @param string $tableName
     * @param array $data
     * @param array $metadata
     * @return MigrationEntity
     */
    private function createMigrationEntity(string $tableName, array $data, array $metadata)
    {
        $nodeReference = EntityReference::create('pages')->setUid($data['pid']);
        $nodeReference->setUuid(UuidUtility::fetchUuid($nodeReference));
        $entityReference = EntityReference::fromRecord($tableName, $data);

        return MigrationEntity::instance()
            ->setNode($nodeReference)
            ->setSubject($entityReference)
            ->setMetadata($metadata);
    }

    /**
     * @param string $tableName
     * @param array $data
     * @return Context
     */
    private function determineContext(string $tableName, array $data)
    {
        $isWorkspaceAspect = $this->isWorkspaceAspect($tableName, $data);
        $isTranslationAspect = $this->isTranslationAspect($tableName, $data);
        $languageField = MetaModelService::instance()->getLanguageFieldName($tableName);

        return Context::create(
            ($isWorkspaceAspect ? $data['t3ver_wsid'] : 0),
            ($isTranslationAspect ? $data[$languageField] : 0)
        );
    }
}
