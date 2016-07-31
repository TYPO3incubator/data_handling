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

use Rhumsaa\Uuid\Uuid;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\DataHandling\Core\Compatibility\DataHandling\Resolver as CompatibilityResolver;
use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\DataHandling\Resolver as CoreResolver;
use TYPO3\CMS\DataHandling\Domain\Object\Record\Bundle;
use TYPO3\CMS\DataHandling\Domain\Object\Record\BundleChangeMap;
use TYPO3\CMS\DataHandling\Domain\Object\Record\Change;
use TYPO3\CMS\DataHandling\Domain\Object\Record\Reference;
use TYPO3\CMS\DataHandling\Domain\Object\Record\State;

class CommandMapper
{
    /**
     * @var Bundle[]
     */
    protected $aggregates = [];

    /**
     * @var Bundle[]
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
     * @var BundleChangeMap[]
     */
    protected $dataCollectionBundleChangeMaps = [];

    /**
     * @var CommandMapperScope
     */
    protected $scope;

    /**
     * @return CommandMapper
     */
    public static function create()
    {
        return GeneralUtility::makeInstance(CommandMapper::class);
    }

    public function __construct()
    {
        $this->scope = CommandMapperScope::instance();
    }

    public function getProcessedAggregates(): array
    {
        return $this->processedAggregates;
    }

    /**
     * @param Bundle[] $aggregates
     * @return CommandMapper
     */
    public function setAggregates(array $aggregates): CommandMapper
    {
        foreach ($aggregates as $aggregate) {
            $reference = $aggregate->getReference();
            $identity = $reference->getName() . ':' . $reference->getUid();
            $this->aggregates[$identity] = $aggregate;
        }
        return $this;
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
        $this->buildPageBundleChangeMaps();
        $this->buildRecordBundleChangeMaps();
        $this->extendBundleChangeMaps();

        $this->mapDataCollectionCommands();
        $this->mapActionCollectionCommands();

        return $this;
    }

    protected function sanitizeCollections()
    {
        $this->unsetDataCollectionsToBeDeleted();
    }

    protected function buildPageBundleChangeMaps()
    {
        foreach ($this->createDataCollectionBundles(['pages']) as $bundle) {
            $this->dataCollectionBundleChangeMaps[] = $this->buildBundleChangeMap($bundle);
        }
    }

    protected function buildRecordBundleChangeMaps()
    {
        foreach ($this->createDataCollectionBundles(['!pages']) as $bundle) {
            $this->dataCollectionBundleChangeMaps[] = $this->buildBundleChangeMap($bundle);
        }
    }

    protected function extendBundleChangeMaps()
    {
        foreach ($this->dataCollectionBundleChangeMaps as $bundleChangeMap) {
            $bundle = $bundleChangeMap->getBundle();
            $change = $bundleChangeMap->getChange();

            $change->getCurrentState()->setValues(
                CompatibilityResolver\ValueResolver::instance()->resolve($bundle->getReference(), $bundle->getValues())
            );
            $change->getCurrentState()->setRelations(
                CompatibilityResolver\RelationResolver::instance()->resolve($bundle->getReference(), $bundle->getValues())
            );
        }
    }

    /**
     * @param string[] $conditions
     * @return Bundle[]
     */
    protected function createDataCollectionBundles(array $conditions = []): array
    {
        $bundles = [];
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
                $bundle = Bundle::instance();
                $bundle->getReference()->setName($tableName)->setUid($uid);
                $bundle->setValues($values);
                $bundles[] = $bundle;
            }
        }

        return $bundles;
    }

    protected function buildBundleChangeMap(Bundle $bundle): BundleChangeMap
    {
        $change = Change::instance();
        $change->setCurrentState(State::instance());
        $change->getCurrentState()->setReference(
            $bundle->getReference()
        );

        $currentStateReference = $change->getCurrentState()->getReference();

        if ($this->isValidUid($currentStateReference->getUid())) {
            $change->setPreviousState(
                $this->fetchState($currentStateReference)
            );
            $currentStateReference->setUuid(
                $change->getPreviousState()->getReference()->getUuid()
            );
        } else {
            $currentStateReference->setUuid(Uuid::uuid4());
            // @todo Check whether NEW-id is defined already and throw exception
            $this->scope->newChangesMap[$currentStateReference->getUid()] = $currentStateReference->getUuid();

            // @todo Check for nested new pages here
            $pidValue = $bundle->getValue('pid');
            if (!empty($this->scope->newChangesMap[$pidValue])) {
                $nodeReference = $this->scope->newChangesMap[$pidValue]->getCurrentState()->getReference();
                $change->getCurrentState()->getNodeReference()->import($nodeReference);
            } elseif ((string)$pidValue !== '0') {
                $nodeReference = Reference::instance()
                    ->setName('pages')
                    ->setUid($pidValue);
                $nodeReference->setUuid($this->fetchUuid($nodeReference));
                $change->getCurrentState()->getNodeReference()->import($nodeReference);
            }
        }

        $bundleChangeMap = BundleChangeMap::instance();
        $bundleChangeMap->setBundle($bundle)->setChange($change);

        return $bundleChangeMap;
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
        foreach ($this->extractDataCollectionAggregates() as $aggregate) {

        }
    }

    protected function mapActionCollectionCommands()
    {

    }

    protected function getAggregate(string $tableName, string $uid): Bundle
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
     * @return Bundle[]
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

    protected function isValidUid($uid): bool
    {
        return (!empty($uid) && MathUtility::canBeInterpretedAsInteger($uid));
    }

    protected function fetchUuid(Reference $reference): string
    {
        $queryBuilder = ConnectionPool::create()->getOriginQueryBuilder();
        $statement = $queryBuilder
            ->select('uuid')
            ->from($reference->getName())
            ->where($queryBuilder->expr()->eq('uid', $reference->getUid()))
            ->execute();
        return $statement->fetchColumn();
    }

    protected function fetchState(Reference $reference): State
    {
        $queryBuilder = ConnectionPool::create()->getOriginQueryBuilder();
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
