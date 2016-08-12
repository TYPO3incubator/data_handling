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
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\Resolver as CompatibilityResolver;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\Database\Query\Restriction\LanguageRestriction;
use TYPO3\CMS\DataHandling\Core\DataHandling\Resolver as CoreResolver;
use TYPO3\CMS\DataHandling\Core\Domain\Command\AbstractCommand;
use TYPO3\CMS\DataHandling\Core\Domain\Command\Generic;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Generic\WriteState;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Context;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Generic\State;
use TYPO3\CMS\DataHandling\Core\MetaModel\Map;

class EventInitializationService
{
    const INSTRUCTION_ENTITY = 1;
    const INSTRUCTION_CONTEXT = 2;
    const INSTRUCTION_VALUES = 8;
    const INSTRUCTION_RELATIONS = 16;

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
        $fetchQueryBuilder = $this->getQueryBuilder();
        $fetchStatement = $fetchQueryBuilder
            ->select('*')
            ->from($tableName)
            ->where($fetchQueryBuilder->expr()->isNull(Common::FIELD_REVISION))
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

            $this->handleCommand(
                $writeState,
                Generic\CreateCommand::create($writeState->getReference())
                    ->setMetadata([ static::KEY_UPGRADE => [ 'uid' => $data['uid'] ] ])
            );
        } else {
            if (empty($data[Common::FIELD_UUID])) {
                throw new \RuntimeException('Value for uuid must be available', 1470840257);
            }

            $writeState->getReference()->setUuid($data[Common::FIELD_UUID])->setUid($data['uid']);
        }

        $temporaryState = State::instance();
        $reference = $writeState->getReference();

        if ($this->instruction & static::INSTRUCTION_CONTEXT) {
            // @todo Process context
        }

        if ($this->instruction & static::INSTRUCTION_VALUES) {
            if ($reference->getUuid() === null) {
                throw new \RuntimeException('Value for uuid must be available', 1470840258);
            }

            $temporaryState->setValues(
                CoreResolver\ValueResolver::instance()->resolve($reference, $data)
            );
            $this->handleCommand(
                $writeState,
                Generic\ChangeCommand::create($reference, $temporaryState->getValues())
            );
        }

        if ($this->instruction & static::INSTRUCTION_RELATIONS) {
            if ($reference->getUuid() === null) {
                throw new \RuntimeException('Value for uuid must be available', 1470840259);
            }

            $temporaryState->setRelations(
                CoreResolver\RelationResolver::instance()->resolve($writeState->getReference(), $data)
            );

            $metaModelSchema = Map::instance()->getSchema($reference->getName());
            foreach ($temporaryState->getRelations() as $relation) {
                $metaModelProperty = $metaModelSchema->getProperty($relation->getName());
                if ($metaModelProperty->hasActiveRelationTo($relation->getEntityReference()->getName())) {
                    $this->handleCommand(
                        $writeState,
                        Generic\AttachRelationCommand::create($reference, $relation)
                    );
                }
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
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        $queryBuilder = ConnectionPool::instance()->getOriginQueryBuilder();
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add($this->getWorkspaceRestriction())
            ->add($this->getLanguageRestriction());

        return $queryBuilder;
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
