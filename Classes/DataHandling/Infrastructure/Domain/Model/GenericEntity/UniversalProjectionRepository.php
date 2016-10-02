<?php
namespace TYPO3\CMS\DataHandling\DataHandling\Infrastructure\Domain\Model\GenericEntity;

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

use TYPO3\CMS\DataHandling\Core\Database\ConnectionPool;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Context;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Event;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\PropertyReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Sequence\RelationSequence;
use TYPO3\CMS\DataHandling\Core\Service\ContextService;

class UniversalProjectionRepository implements ProjectionRepository
{
    /**
     * @param string $tableName
     * @return UniversalProjectionRepository
     */
    public static function create(string $tableName)
    {
        return new static($tableName);
    }

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var bool
     */
    private $includeOrigin = true;

    /**
     * @var bool
     */
    private $forAll = false;

    /**
     * @var int[]
     */
    private $forWorkspaces = [];

    /**
     * @param string $tableName
     */
    private function __construct(string $tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @param bool $includeOrigin
     */
    public function includeOrigin(bool $includeOrigin)
    {
        $this->includeOrigin = $includeOrigin;
    }

    public function forAll()
    {
        $this->forAll = true;
    }

    /**
     * @param int $workspaceId
     */
    public function forWorkspace(int $workspaceId)
    {
        if ($this->forAll) {
            throw new \LogicException(
                'Projection to all is defined already',
                1475399574
            );
        }

        if (!in_array($workspaceId, $this->forWorkspaces)) {
            $this->forWorkspaces[] = $workspaceId;
        }
    }

    /**
     * @param array $data
     */
    public function add(array $data)
    {
        foreach ($this->buildRepositories() as $repository) {
            $repository->add($data);
        }
    }

    /**
     * @param string $identifier
     */
    public function remove(string $identifier)
    {
        foreach ($this->buildRepositories() as $repository) {
            $repository->remove($identifier);
        }
    }

    /**
     * @param string $identifier
     */
    public function purge(string $identifier)
    {
        foreach ($this->buildRepositories() as $repository) {
            $repository->purge($identifier);
        }
    }

    /**
     * @param string $identifier
     * @param array $data
     */
    public function update(string $identifier, array $data)
    {
        foreach ($this->buildRepositories() as $repository) {
            $repository->update($identifier, $data);
        }
    }

    /**
     * @param string $identifier
     * @param PropertyReference $relationReference
     */
    public function attachRelation(string $identifier, PropertyReference $relationReference)
    {
        foreach ($this->buildRepositories() as $repository) {
            $repository->attachRelation($identifier, $relationReference);
        }
    }

    /**
     * @param string $identifier
     * @param PropertyReference $relationReference
     */
    public function removeRelation(string $identifier, PropertyReference $relationReference)
    {
        foreach ($this->buildRepositories() as $repository) {
            $repository->removeRelation($identifier, $relationReference);
        }
    }

    /**
     * @param string $identifier
     * @param RelationSequence $sequence
     */
    public function orderRelations(string $identifier, RelationSequence $sequence)
    {
        foreach ($this->buildRepositories() as $repository) {
            $repository->orderRelations($identifier, $sequence);
        }
    }

    /**
     * @return AbstractProjectionRepository[]
     */
    private function buildRepositories()
    {
        $repositories = [];
        $workspaceIds = ContextService::instance()
            ->getWorkspaceIds();
        $validWorkspaceIds = array_intersect(
            $workspaceIds,
            $this->forWorkspaces
        );

        if ($this->includeOrigin) {
            $repositories[] = OriginProjectionRepository::create(
                $this->tableName
            );
        }

        if ($this->forAll) {
            foreach ($workspaceIds as $workspaceId) {
                $repositories[] = $this->getLocalStorageProjectionRepository(
                    Context::create($workspaceId)
                );
            }
        } else {
            foreach ($validWorkspaceIds as $workspaceId) {
                $repositories[] = $this->getLocalStorageProjectionRepository(
                    Context::create($workspaceId)
                );
            }
        }

        return $repositories;
    }

    /**
     * @param Context $context
     * @return LocalStorageProjectionRepository
     */
    private function getLocalStorageProjectionRepository(Context $context)
    {
        $localStorage = ConnectionPool::instance()
            ->provideLocalStorageConnection(
                $context->asLocalStorageName()
            );
        $repository = LocalStorageProjectionRepository::create(
            $localStorage,
            $this->tableName
        );

        return $repository;
    }
}
