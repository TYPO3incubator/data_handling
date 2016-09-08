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
use TYPO3\CMS\DataHandling\Common;
use TYPO3\CMS\DataHandling\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\Resolver as CompatibilityResolver;
use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\Resolver\CommandResolver;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\GenericEntity;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Context;
use TYPO3\CMS\DataHandling\Core\Domain\Repository\Meta\GenericEntityEventRepository;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\DataHandling\CommandPublisher;
use TYPO3\CMS\DataHandling\Core\DataHandling\Resolver as CoreResolver;
use TYPO3\CMS\DataHandling\Core\Domain\Command\Meta\AbstractCommand;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\Change;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\State;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Command\DomainCommand;
use TYPO3\CMS\DataHandling\Core\Service\MetaModelService;
use TYPO3\CMS\DataHandling\Core\Utility\UuidUtility;

class CommandMapper
{
    /**
     * @var array
     */
    private $dataCollection = [];

    /**
     * @var array
     */
    private $actionCollection = [];

    /**
     * @var Change[]
     */
    private $dataCollectionChanges = [];

    /**
     * @var CommandMapperScope
     */
    private $scope;

    /**
     * @var AbstractCommand[]
     */
    private $commands = [];

    /**
     * @param array $dataCollection
     * @param array $actionCollection
     * @return CommandMapper
     */
    public static function create(array $dataCollection, array $actionCollection)
    {
        return GeneralUtility::makeInstance(
            CommandMapper::class,
            $dataCollection,
            $actionCollection
        );
    }

    public function __construct(array $dataCollection, array $actionCollection)
    {
        $this->dataCollection = $dataCollection;
        $this->actionCollection = $actionCollection;
        $this->mapCommands();
    }

    private function mapCommands()
    {
        $this->initialize();

        $this->sanitizeCollections();
        $this->buildPageChanges();
        $this->buildRecordChanges();
        $this->extendChanges();

        $this->mapDataCollectionCommands();
        $this->mapActionCollectionCommands();
    }

    /**
     * @return AbstractCommand[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function emitCommands()
    {
        foreach ($this->commands as $command) {
            CommandPublisher::provide()->publish($command);
        }
    }

    private function initialize()
    {
        $this->scope = CommandMapperScope::instance();
        $this->dataCollectionChanges = [];
        $this->commands = [];
    }

    private function sanitizeCollections()
    {
        $this->unsetDataCollectionsToBeDeleted();
    }

    private function buildPageChanges()
    {
        foreach ($this->createDataCollectionChanges(['pages']) as $change) {
            $this->dataCollectionChanges[] = $change;
        }
    }

    private function buildRecordChanges()
    {
        foreach ($this->createDataCollectionChanges(['!pages']) as $change) {
            $this->dataCollectionChanges[] = $change;
        }
    }

    private function extendChanges()
    {
        foreach ($this->dataCollectionChanges as $change) {
            $this->extendChangeIdentity($change);

            $targetState = $change->getTargetState();

            $targetState->setValues(
                CompatibilityResolver\ValueResolver::instance()->resolve($targetState->getSubject(), $targetState->getValues())
            );
            $targetState->setRelations(
                CompatibilityResolver\RelationResolver::instance()->resolve($targetState->getSubject(), $targetState->getValues())
            );

            unset($targetState);
        }
    }

    /**
     * @param string[] $conditions
     * @return Change[]
     */
    private function createDataCollectionChanges(array $conditions = []): array
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

            $languageFieldName = MetaModelService::instance()->getLanguageFieldName($tableName);

            foreach ($uidValues as $uid => $values) {
                $context = Context::instance()->setWorkspaceId($this->getWorkspaceId());
                // @todo validate against proper languages
                if (!empty($values[$languageFieldName])) {
                    $context->setLanguageId($values[$languageFieldName]);
                }

                $subject = EntityReference::instance()
                    ->setName($tableName)
                    ->setUid($uid);
                $targetState = State::instance()
                    ->setContext($context)
                    ->setSubject($subject)
                    ->setValues($values);
                $changes[] = Change::instance()
                    ->setTargetState($targetState);
            }
        }

        return $changes;
    }

    private function extendChangeIdentity(Change $change)
    {
        $targetStateReference = $change->getTargetState()->getSubject();

        if ($this->isValidUid($targetStateReference->getUid())) {
            $change->setNew(false);
            $change->setSourceState(
                $this->fetchState($targetStateReference)
            );
            $targetStateReference->setUuid(
                $change->getSourceState()->getSubject()->getUuid()
            );
        } else {
            $change->setNew(true);
            $targetStateReference->setUuid(Uuid::uuid4()->toString());
            // @todo Check whether NEW-id is defined already and throw exception
            $this->scope->newChangesMap[$targetStateReference->getUid()] = $targetStateReference->getUuid();

            // @todo Check for nested new pages here
            $pageIdValue = $change->getTargetState()->getValue('pid');
            // relating to a new page
            if (!empty($this->scope->newChangesMap[$pageIdValue])) {
                $nodeReference = $this->scope->newChangesMap[$pageIdValue]->getTargetState()->getSubject();
                $change->getTargetState()->getNode()->import($nodeReference);
            // negative page-id, fetch record and retrieve pid value
            } elseif ($pageIdValue < 0) {
                // @todo Add "MoveAfterRecordCommand"
                $recordReference = EntityReference::instance()->import($targetStateReference)->setUid(abs($pageIdValue));
                $nodeReference = EntityReference::instance()
                    ->setName('pages')
                    ->setUid($this->fetchPageId($recordReference));
                $nodeReference->setUuid($this->fetchUuid($nodeReference));
                $change->getTargetState()->getNode()->import($nodeReference);
            // relating to an existing page
            } elseif ((string)$pageIdValue !== '0') {
                $nodeReference = EntityReference::instance()
                    ->setName('pages')
                    ->setUid($pageIdValue);
                $nodeReference->setUuid($this->fetchUuid($nodeReference));
                $change->getTargetState()->getNode()->import($nodeReference);
            }
        }
    }

    private function unsetDataCollectionsToBeDeleted()
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

    private function mapDataCollectionCommands()
    {
        // sequence of changes ordered by accordant relative aggregate
        $aggregateResolver = CoreResolver\AggregateResolver::create(
            $this->dataCollectionChanges
        );
        // @todo Process aggregates in a bundle
        // - try to translate into specific commands
        // - handle sequences remaining commands (per aggregate)

        foreach ($aggregateResolver->getSequence() as $change) {
            $commands = CommandResolver::instance()
                ->setChange($change)
                ->resolve();
            $this->commands = array_merge($this->commands, $commands);
        }
    }

    private function mapActionCollectionCommands()
    {

    }

    private function isValidUid($uid): bool
    {
        return (!empty($uid) && MathUtility::canBeInterpretedAsInteger($uid));
    }

    private function fetchUuid(EntityReference $reference): string
    {
        return UuidUtility::fetchUuid($reference);
    }

    private function fetchPageId(EntityReference $reference): string
    {
        $queryBuilder = ConnectionPool::instance()->getOriginQueryBuilder();
        $statement = $queryBuilder
            ->select('pid')
            ->from($reference->getName())
            ->where($queryBuilder->expr()->eq('uid', abs($reference->getUid())))
            ->execute();
        return $statement->fetchColumn();
    }

    /**
     * @param EntityReference $reference
     * @return GenericEntity
     */
    private function fetchState(EntityReference $reference)
    {
        if ($reference->getUuid() === null) {
            $reference->setUuid($this->fetchUuid($reference));
        }

        // @todo lookup in context-based projection

        return GenericEntityEventRepository::create($reference->getName())
            ->findByUuid($reference->getUuidInterface());
    }

    /**
     * @param EntityReference $reference
     * @return State
     * @deprecated Kept for development, use fetchState() instead
     */
    private function fetchOriginState(EntityReference $reference): State
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
        $state->getSubject()->import($reference)->setUuid($data[Common::FIELD_UUID]);

        $state->setValues(
            CoreResolver\ValueResolver::instance()->resolve($state->getSubject(), $data)
        );
        $state->setRelations(
            CoreResolver\RelationResolver::instance()->resolve($state->getSubject(), $data)
        );

        return $state;
    }

    /**
     * @return int
     */
    private function getWorkspaceId()
    {
        return $this->getBackendUser()->workspace;
    }

    /**
     * @return BackendUserAuthentication
     */
    private function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
