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
use TYPO3\CMS\DataHandling\Core\Domain\Command\Generic\AbstractCommand;
use TYPO3\CMS\DataHandling\Core\Domain\Command\Generic;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Generic\WriteState;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Context;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\State;
use TYPO3\CMS\DataHandling\Core\MetaModel\Map;
use TYPO3\CMS\DataHandling\Core\Service\MetaModelService;

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
            $this->createCommandsFor($tableName, $row);
        }
    }

    /**
     * @param string $tableName
     * @param array $data
     */
    protected function createCommandsFor(string $tableName, array $data)
    {
        if (empty($data['uid'])) {
            throw new \RuntimeException('Value for uid must be available', 1470840257);
        }

        $writeState = WriteState::reference(EntityReference::instance()->setName($tableName));

        if ($this->instruction & static::INSTRUCTION_ENTITY) {
            if (!empty($data[Common::FIELD_UUID])) {
                throw new \RuntimeException('Value for uuid is already defined', 1470840256);
            }
            $this->createEntityCommands($writeState, $data);
        } else {
            if (empty($data[Common::FIELD_UUID])) {
                throw new \RuntimeException('Value for uuid must be available', 1470840257);
            }
            $writeState->getReference()->setUuid($data[Common::FIELD_UUID])->setUid($data['uid']);
        }

        $reference = $writeState->getReference();

        if ($this->instruction & static::INSTRUCTION_VALUES) {
            if ($reference->getUuid() === null) {
                throw new \RuntimeException('Value for uuid must be available', 1470840258);
            }
            $this->createValueCommands($writeState, $data);
        }

        if ($this->instruction & static::INSTRUCTION_RELATIONS) {
            if ($reference->getUuid() === null) {
                throw new \RuntimeException('Value for uuid must be available', 1470840259);
            }
            $this->createRelationCommands($writeState, $data);
        }

        if ($this->instruction & static::INSTRUCTION_ACTIONS) {
            $this->createActionCommands($writeState, $data);
        }
    }

    /**+
     * Creates Identifiable command for specific context state.
     *
     * @param WriteState $writeState
     * @param array $data
     */
    protected function createEntityCommands(WriteState $writeState, array $data)
    {
        $tableName = $writeState->getReference()->getName();
        $metadata = $this->getUpgradeMetadata($data);

        $isWorkspaceAspect = $this->isWorkspaceAspect($tableName);
        $isTranslationAspect = $this->isTranslationAspect($tableName, $data);
        $languagePointerField = MetaModelService::instance()->getLanguagePointerFieldName($tableName);

        // no workspace, no translation -> just CreateCommand
        if (!$isWorkspaceAspect && !$isTranslationAspect) {
            $this->handleCommand(
                $writeState,
                Generic\CreateCommand::create($writeState->getReference())->setMetadata($metadata)
            );
        // at least workspace -> either CreateCommand or BranchCommand
        } elseif ($isWorkspaceAspect) {
            $versionState = VersionState::cast($data['t3ver_state']);

            if ($versionState->equals(VersionState::NEW_PLACEHOLDER_VERSION)) {
                $this->handleCommand(
                    $writeState,
                    Generic\CreateCommand::create($writeState->getReference())->setMetadata($metadata)
                );
            } else {
                $liveData = $this->fetchRecordByUid($tableName, $data['t3ver_oid']);
                $liveReference = EntityReference::fromRecord($tableName, $liveData);
                $this->handleCommand(
                    $writeState,
                    Generic\BranchCommand::create($liveReference)->setMetadata($metadata)
                );
            }

        }
        // additionally translation, CreateCommand or BranchCommand have been issued before
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
                $pointsToReference = $writeState->getReference();
            }


            $this->handleCommand(
                $writeState,
                Generic\TranslateCommand::create($pointsToReference)->setMetadata($metadata)
            );
        }
    }

    /**
     * Creates ChangeCommand for assigning values.
     *
     * @param WriteState $writeState
     * @param array $data
     */
    protected function createValueCommands(WriteState $writeState, array $data)
    {
        $tableName = $writeState->getReference()->getName();
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
            CoreResolver\ValueResolver::instance()->resolve($writeState->getReference(), $data)
        );
        $this->handleCommand(
            $writeState,
            Generic\ChangeCommand::create($writeState->getReference(), $temporaryState->getValues())->setMetadata($metadata)
        );
    }

    /**
     * Creates AttachRelationCommands for relations (inline, group, select, special language).
     *
     * @param WriteState $writeState
     * @param array $data
     */
    protected function createRelationCommands(WriteState $writeState, array $data)
    {
        $metadata = $this->getUpgradeMetadata($data);

        $temporaryState = State::instance()->setRelations(
            CoreResolver\RelationResolver::instance()->resolve($writeState->getReference(), $data)
        );

        $metaModelSchema = Map::instance()->getSchema($writeState->getReference()->getName());
        foreach ($temporaryState->getRelations() as $relation) {
            $metaModelProperty = $metaModelSchema->getProperty($relation->getName());
            if ($metaModelProperty->hasActiveRelationTo($relation->getEntityReference()->getName())) {
                $this->handleCommand(
                    $writeState,
                    Generic\AttachRelationCommand::create($writeState->getReference(), $relation)->setMetadata($metadata)
                );
            }
        }
    }

    /**
     * Creates command for specific context state.
     *
     * @param WriteState $writeState
     * @param array $data
     */
    protected function createActionCommands(WriteState $writeState, array $data)
    {
        $tableName = $writeState->getReference()->getName();
        $metadata = $this->getUpgradeMetadata($data);

        if ($this->isWorkspaceAspect($tableName)) {
            $versionState = VersionState::cast($data['t3ver_state']);

            if ($versionState->equals(VersionState::DELETE_PLACEHOLDER)) {
                $this->handleCommand(
                    $writeState,
                    Generic\DeleteCommand::create($writeState->getReference())->setMetadata($metadata)
                );
            } elseif ($versionState->equals(VersionState::MOVE_POINTER)) {
                // MoveBeforeCommand or MoveAfterCommand (or OrderRelationsComman for parent node)
                // @todo Implement commands
            }
        }
    }

    /**
     * @param WriteState $writeState
     * @param AbstractCommand $command
     */
    protected function handleCommand(WriteState $writeState, AbstractCommand $command)
    {
        $metadata = (array)$command->getMetadata();
        $metadata['trigger'] = EventInitializationService::class;
        $writeState->handleCommand($command->setMetadata($metadata));
    }

    /**
     * @param string $tableName
     * @return bool
     */
    protected function isWorkspaceAspect(string $tableName)
    {
        return (
            $this->context->getWorkspace() > 0
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
            $this->context->getLanguage() > 0
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
        if ($this->context->getWorkspace() === 0) {
            // in live workspace, don't include overlays
            $workspaceRestriction = GeneralUtility::makeInstance(
                BackendWorkspaceRestriction::class,
                $this->context->getWorkspace(),
                false
            );
        } else {
            // in a real workspace include overlays
            $workspaceRestriction = GeneralUtility::makeInstance(
                BackendWorkspaceRestriction::class,
                $this->context->getWorkspace(),
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
        return LanguageRestriction::create($this->context->getLanguage());
    }
}