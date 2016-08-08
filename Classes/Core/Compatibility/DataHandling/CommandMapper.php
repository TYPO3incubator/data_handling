<?php
namespace TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\Resolver as CompatibilityResolver;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\DataHandling\Resolver as CoreResolver;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Record\Change;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Record\Reference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Record\State;

class CommandMapper
{
    /**
     * @var array
     */
    protected $dataCollection = [];

    /**
     * @var array
     */
    protected $actionCollection = [];

    /**
     * @var Change[]
     */
    protected $dataCollectionChanges = [];

    /**
     * @var CommandMapperScope
     */
    protected $scope;

    /**
     * @return CommandMapper
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(CommandMapper::class);
    }

    public function __construct()
    {
        $this->scope = CommandMapperScope::instance();
    }

    public function setDataCollection(array $dataCollection): CommandMapper
    {
        $this->dataCollection = $dataCollection;
        return $this;
    }

    public function setActionCollection(array $actionCollection): CommandMapper
    {
        $this->actionCollection = $actionCollection;
        return $this;
    }

    public function mapCommands(): CommandMapper
    {
        $this->sanitizeCollections();
        $this->buildPageChanges();
        $this->buildRecordChanges();
        $this->extendChanges();

        $this->mapDataCollectionCommands();
        $this->mapActionCollectionCommands();

        return $this;
    }

    protected function sanitizeCollections()
    {
        $this->unsetDataCollectionsToBeDeleted();
    }

    protected function buildPageChanges()
    {
        foreach ($this->createDataCollectionChanges(['pages']) as $change) {
            $this->dataCollectionChanges[] = $change;
        }
    }

    protected function buildRecordChanges()
    {
        foreach ($this->createDataCollectionChanges(['!pages']) as $change) {
            $this->dataCollectionChanges[] = $change;
        }
    }

    protected function extendChanges()
    {
        foreach ($this->dataCollectionChanges as $change) {
            $this->extendChangeIdentity($change);

            $targetState = $change->getTargetState();

            $targetState->setValues(
                CompatibilityResolver\ValueResolver::instance()->resolve($targetState->getReference(), $targetState->getValues())
            );
            $targetState->setRelations(
                CompatibilityResolver\RelationResolver::instance()->resolve($targetState->getReference(), $targetState->getValues())
            );
        }
    }

    /**
     * @param string[] $conditions
     * @return Change[]
     */
    protected function createDataCollectionChanges(array $conditions = []): array
    {
        $changes = [];
        $onlyTableNames = [];
        $excludeTableNames = [];

        foreach ($conditions as $condition) {
            if (strpos($condition, '!') === 0) {
                $excludeTableNames[] = substr($condition, 1);
            } else {
                $onlyTableNames[] = $condition;
            }
        }

        foreach ($this->dataCollection as $tableName => $uidValues) {
            if (
                !empty($onlyTableNames) && !in_array($tableName, $onlyTableNames)
                || in_array($tableName, $excludeTableNames)
            ) {
                continue;
            }

            foreach ($uidValues as $uid => $values) {
                $targetState = State::instance()
                    ->setReference(Reference::instance()->setName($tableName)->setUid($uid))
                    ->setValues($values);
                $changes[] = Change::instance()->setTargetState($targetState);
            }
        }

        return $changes;
    }

    protected function extendChangeIdentity(Change $change)
    {
        $targetStateReference = $change->getTargetState()->getReference();

        if ($this->isValidUid($targetStateReference->getUid())) {
            $change->setNew(false);
            $change->setSourceState(
                $this->fetchState($targetStateReference)
            );
            $targetStateReference->setUuid(
                $change->getSourceState()->getReference()->getUuid()
            );
        } else {
            $change->setNew(true);
            $targetStateReference->setUuid(Uuid::uuid4());
            // @todo Check whether NEW-id is defined already and throw exception
            $this->scope->newChangesMap[$targetStateReference->getUid()] = $targetStateReference->getUuid();

            // @todo Check for nested new pages here
            $pidValue = $change->getTargetState()->getValue('pid');
            // relating to a new page
            if (!empty($this->scope->newChangesMap[$pidValue])) {
                $nodeReference = $this->scope->newChangesMap[$pidValue]->getTargetState()->getReference();
                $change->getTargetState()->getNodeReference()->import($nodeReference);
            // relating to an existing page
            } elseif ((string)$pidValue !== '0') {
                $nodeReference = Reference::instance()
                    ->setName('pages')
                    ->setUid($pidValue);
                $nodeReference->setUuid($this->fetchUuid($nodeReference));
                $change->getTargetState()->getNodeReference()->import($nodeReference);
            }
        }
    }

    protected function unsetDataCollectionsToBeDeleted()
    {
        foreach ($this->actionCollection as $tableName => $idCommands) {
            foreach ($idCommands as $id => $commands) {
                foreach ($commands as $command => $value) {
                    if ($value && $command == 'delete') {
                        if (isset($this->dataCollection[$tableName][$id])) {
                            unset($this->dataCollection[$tableName][$id]);
                        }
                    }
                }
            }
        }
    }

    protected function mapDataCollectionCommands()
    {
        // determine aggregates and process them
    }

    protected function mapActionCollectionCommands()
    {

    }

    protected function isValidUid($uid): bool
    {
        return (!empty($uid) && MathUtility::canBeInterpretedAsInteger($uid));
    }

    protected function fetchUuid(Reference $reference): string
    {
        $queryBuilder = ConnectionPool::instance()->getOriginQueryBuilder();
        $statement = $queryBuilder
            ->select('uuid')
            ->from($reference->getName())
            ->where($queryBuilder->expr()->eq('uid', $reference->getUid()))
            ->execute();
        return $statement->fetchColumn();
    }

    protected function fetchState(Reference $reference): State
    {
        $queryBuilder = ConnectionPool::instance()->getOriginQueryBuilder();
        $statement = $queryBuilder
            ->select('*')
            ->from($reference->getName())
            ->where($queryBuilder->expr()->eq('uid', $reference->getUid()))
            ->execute();
        $data = $statement->fetch();

        if (empty($data)) {
            throw new \RuntimeException('State for "' . $reference->getName() . ':' . $reference->getUid() . '" not available', 1469963429);
        }

        $state = State::instance();
        $state->getReference()->import($reference)->setUuid($data['uuid']);

        $state->setValues(
            CoreResolver\ValueResolver::instance()->resolve($state->getReference(), $data)
        );
        $state->setRelations(
            CoreResolver\RelationResolver::instance()->resolve($state->getReference(), $data)
        );

        return $state;
    }
}
