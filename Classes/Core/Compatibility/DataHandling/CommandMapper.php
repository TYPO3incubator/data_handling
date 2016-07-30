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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\DataHandling\CommandManager;
use TYPO3\CMS\DataHandling\Core\Domain\Command\Record\CreateCommand;
use TYPO3\CMS\DataHandling\Domain\Model\GenericEntity;

class CommandMapper
{
    /**
     * @var GenericEntity[]
     */
    protected $aggregates = [];

    /**
     * @var GenericEntity[]
     */
    protected $processedAggregates = [];

    /**
     * @var array
     */
    protected $dataCollection = [];

    /**
     * @var array
     */
    protected $actionCollection = [];

    /**
     * @return CommandMapper
     */
    public static function create()
    {
        return GeneralUtility::makeInstance(CommandMapper::class);
    }

    public function getProcessedAggregates(): array
    {
        return $this->processedAggregates;
    }

    public function setAggregates(array $aggregates): CommandMapper
    {
        $this->aggregates = $aggregates;
        return $this;
    }

    public function mapDataCommands(array $dataCollection): CommandMapper
    {
        $this->dataCollection = $dataCollection;

        foreach ($this->extractDataCollectionAggregates() as $aggregate) {
            $this->determineEntityCommand($aggregate);
        }

        return $this;
    }

    public function mapActionCommands(array $actionCollection): CommandMapper
    {
        $this->actionCollection = $actionCollection;

        return $this;
    }

    protected function getAggregate(string $tableName, string $uid): GenericEntity
    {
        if ($this->hasAggregate($tableName, $uid)) {
            return $this->aggregates[$tableName . ':' . $uid];
        }
        return null;
    }

    protected function setProcessedAggregate(string $tableName, string $uid)
    {
        if ($this->hasAggregate($tableName, $uid)) {
            $identifier = $tableName . ':' . $uid;
            $this->processedAggregates[$identifier] = $this->aggregates[$identifier];
            unset($this->aggregates[$identifier]);
        }
    }

    protected function hasAggregate(string $tableName, string $uid): bool
    {
        return isset($this->aggregates[$tableName . ':' . $uid]);
    }

    /**
     * @return GenericEntity[]
     */
    protected function extractDataCollectionAggregates(): array
    {
        $aggregates = [];

        foreach ($this->dataCollection as $tableName => $uidValues) {
            foreach ($uidValues as $uid => $values) {
                if ($this->hasAggregate($tableName, $uid)) {
                    $aggregate = $this->getAggregate($tableName, $uid);
                    $aggregate->setValues($values);

                    $aggregates[$tableName . ':' . $uid] = $aggregate;
                    unset($this->dataCollection[$tableName][$uid]);
                    $this->setProcessedAggregate($tableName, $uid);
                }
            }
        }

        return $aggregates;
    }

    protected function determineEntityCommand(GenericEntity $entity)
    {
        if ($this->isValidUid($entity->getUid())) {
            $entity->setUuid(
                $this->fetchUuid($entity)
            );

            if ($this->isDifferentContext($entity)) {
                // @todo Add new workspace version handling
            }
        }

        if ($entity->getUuid() === null) {
            CommandManager::create()->handle(
                CreateCommand::create($entity->getEntityName())
            );
        }

    }

    protected function isValidUid($uid): bool
    {
        return (!empty($uid) && MathUtility::canBeInterpretedAsInteger($uid));
    }

    protected function fetchUuid(GenericEntity $entity): string
    {
        $queryBuilder = ConnectionPool::create()->getOriginQueryBuilder();
        $statement = $queryBuilder
            ->select('uuid')
            ->from($entity->getEntityName())
            ->where($queryBuilder->expr()->eq('uid', $entity->getUid()))
            ->execute();
        return $statement->fetchColumn();
    }

    protected function isDifferentContext(GenericEntity $entity): bool
    {
        return false;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
