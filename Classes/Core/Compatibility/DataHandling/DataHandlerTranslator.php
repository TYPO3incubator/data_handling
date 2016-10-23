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
use TYPO3\CMS\DataHandling\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\Resolver as CompatibilityResolver;
use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\Resolver\CommandResolver;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\GenericEntity;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\Common\Context;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\SuggestedState;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntity\OriginProjectionRepository;
use TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntityEventRepository;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\DataHandling\Resolver as CoreResolver;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\Change;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\EntityReference;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Command\CommandBus;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\TcaCommand\TcaCommandManager;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\TcaCommand\TcaCommandTranslator;
use TYPO3\CMS\DataHandling\Core\Service\MetaModelService;
use TYPO3\CMS\DataHandling\Core\Utility\UuidUtility;

/*
 * - first create all suggested states
 * - extend changes by entity information
 * - extend changes by resolved values and relations
 * - then(!) convert to regular state
 * - build changes
 * - fetch source state (generic entity)
 */

class DataHandlerTranslator
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
     * @var EntityReference[]
     */
    private $newSubjects = [];

    /**
     * @var DataHandlerScope
     */
    private $scope;

    /**
     * @param array $dataCollection
     * @param array $actionCollection
     * @return DataHandlerTranslator
     */
    public static function create(array $dataCollection, array $actionCollection)
    {
        return GeneralUtility::makeInstance(
            DataHandlerTranslator::class,
            $dataCollection,
            $actionCollection
        );
    }

    /**
     * @param array $dataCollection
     * @param array $actionCollection
     */
    public function __construct(array $dataCollection, array $actionCollection)
    {
        $this->dataCollection = $dataCollection;
        $this->actionCollection = $actionCollection;
    }

    /**
     * @return array
     */
    public function getDataCollection()
    {
        return $this->dataCollection;
    }

    /**
     * @return array
     */
    public function getActionCollection()
    {
        return $this->actionCollection;
    }

    /**
     * @return EntityReference[]
     */
    public function getNewSubjects()
    {
        return $this->newSubjects;
    }

    public function process()
    {
        $this->initialize();

        $this->sanitizeCollections();
        $this->buildPageChanges();
        $this->buildRecordChanges();
        $this->extendChanges();

        $this->mapDataCollectionCommands();
        $this->mapActionCollectionCommands();

        $this->provideNewSubjects();
    }

    private function initialize()
    {
        $this->scope = DataHandlerScope::instance();
        $this->dataCollectionChanges = [];
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
        // extend all changes & register new entities in scope
        foreach ($this->dataCollectionChanges as $change) {
            $this->extendChangeIdentity($change);
        }
        // process all changes & resolve value and relations
        foreach ($this->dataCollectionChanges as $change) {
            $targetState = $change->getTargetState();

            $targetState->setValues(
                CompatibilityResolver\ValueResolver::instance()
                    ->setScope($this->scope)
                    ->resolve($targetState->getSubject(), $targetState->getSuggestedValues())
            );
            $targetState->setRelations(
                CompatibilityResolver\RelationResolver::instance()
                    ->setScope($this->scope)
                    ->resolve($targetState->getSubject(), $targetState->getSuggestedValues())
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
                || !MetaModelService::instance()->shallListenEvents($tableName)
            ) {
                // @todo Remove work-around for a FormEngine bug
                foreach(array_keys($uidValues) as $uid) {
                    if (!MathUtility::canBeInterpretedAsInteger($uid)) {
                        $this->scope->ignoredNewEntityReferences[$uid]
                            = EntityReference::create($tableName)->setUid($uid);
                    }
                }
                continue;
            }

            $languageFieldName = MetaModelService::instance()->getLanguageFieldName($tableName);

            foreach ($uidValues as $uid => $values) {
                // @todo validate against proper languages
                if (!empty($values[$languageFieldName])) {
                    $context = Context::create(
                        $this->getWorkspaceId(),
                        (int)$values[$languageFieldName]
                    );
                } else {
                    $context = Context::create(
                        $this->getWorkspaceId()
                    );
                }

                $subject = EntityReference::instance()
                    ->setName($tableName)
                    ->setUid($uid);
                $targetState = SuggestedState::instance()
                    ->setContext($context)
                    ->setSubject($subject)
                    ->setSuggestedValues($values);
                $changes[] = Change::instance()
                    ->setTargetState($targetState);
            }

            unset($this->dataCollection[$tableName]);
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
            $this->scope->acceptedNewEntityReferences[$targetStateReference->getUid()] = $targetStateReference;

            // @todo Check for nested new pages here
            $pageIdValue = $change->getTargetState()->getSuggestedValue('pid');
            // relating to a new page
            if (!empty($this->scope->acceptedNewEntityReferences[$pageIdValue])) {
                $nodeReference = $this->scope->acceptedNewEntityReferences[$pageIdValue];
                $change->getTargetState()->getNode()->import($nodeReference);
            // negative page-id, fetch record and retrieve pid value
            } elseif ($pageIdValue < 0) {
                // @todo Add "MoveAfterRecordCommand"
                $recordReference = EntityReference::instance()
                    ->import($targetStateReference)
                    ->setUid(abs($pageIdValue));
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
            if (!MetaModelService::instance()->shallListenEvents($tableName)) {
                continue;
            }
            foreach ($idCommands as $id => $commands) {
                if (!isset($this->dataCollection[$tableName][$id])) {
                    continue;
                }
                foreach ($commands as $command => $value) {
                    if (!empty($value) && $command == 'delete') {
                        unset($this->dataCollection[$tableName][$id]);
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

        foreach ($aggregateResolver->getRootAggregates() as $rootAggregate) {
            $rootAggregateChanges = $aggregateResolver->getBottomUpChanges(
                $rootAggregate
            );
            // resolve meta commands
            $resolver = CommandResolver::create($rootAggregateChanges);
            $commands = $resolver->getCommands();
            // try to translate into specific commands
            $commands = TcaCommandTranslator::create($commands)->translate();

            foreach ($commands as $command) {
                CommandBus::provide()->handle($command);
            }
        }
    }

    private function mapActionCollectionCommands()
    {

    }

    private function provideNewSubjects()
    {
        foreach ($this->scope->acceptedNewEntityReferences as $placeholder => $newEntityReference) {
            $uid = UuidUtility::fetchUid($newEntityReference);
            if (empty($uid)) {
                continue;
            }
            $this->newSubjects[$placeholder] = EntityReference::instance()
                ->import($newEntityReference)
                ->setUid($uid);
        }
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

        $genericEntity = null;
        if (!TcaCommandManager::provide()->has($reference->getName())) {
            $genericEntity = $this->fetchEventState($reference);
        }

        if (
            $genericEntity === null
            || $genericEntity->getSubject()->getUuidInterface() === null
        ) {
            $genericEntity = $this->fetchOriginState($reference);
        }

        return $genericEntity;
    }

    /**
     * @param EntityReference $reference
     * @return GenericEntity
     */
    private function fetchEventState(EntityReference $reference)
    {
        return GenericEntityEventRepository::instance()
            ->findByAggregateReference($reference);
    }

    /**
     * @param EntityReference $reference
     * @return GenericEntity
     */
    private function fetchOriginState(EntityReference $reference)
    {
        $repository = OriginProjectionRepository::create($reference->getName());
        $genericEntity = $repository->findOneByUid($reference->getUid());

        if ($genericEntity === null) {
            throw new \RuntimeException('State for "' . $reference->getName() . ':' . $reference->getUid() . '" not available', 1469963429);
        }

        return $genericEntity;
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
