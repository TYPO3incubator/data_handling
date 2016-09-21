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

use TYPO3\CMS\DataHandling\Core\Domain\Model\Event;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Meta\PropertyReference;
use TYPO3\CMS\DataHandling\Core\Domain\Object\Sequence\RelationSequence;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Repository\ProjectionRepository;

class MultipleProjectionRepositoryHandler implements ProjectionRepository
{
    /**
     * @return MultipleProjectionRepositoryHandler
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * @var AbstractProjectionRepository[]
     */
    private $repositories = [];

    /**
     * @param AbstractProjectionRepository $repository
     */
    public function addRepository(AbstractProjectionRepository $repository)
    {
        if (in_array($repository, $this->repositories)) {
            return;
        }

        $this->repositories[] = $repository;
    }

    /**
     * @param array $data
     */
    public function add(array $data)
    {
        foreach ($this->repositories as $repository) {
            $repository->add($data);
        }
    }

    /**
     * @param string $identifier
     */
    public function remove(string $identifier)
    {
        foreach ($this->repositories as $repository) {
            $repository->remove($identifier);
        }
    }

    /**
     * @param string $identifier
     */
    public function purge(string $identifier)
    {
        foreach ($this->repositories as $repository) {
            $repository->purge($identifier);
        }
    }

    /**
     * @param string $identifier
     * @param array $data
     */
    public function update(string $identifier, array $data)
    {
        foreach ($this->repositories as $repository) {
            $repository->update($identifier, $data);
        }
    }

    /**
     * @param string $identifier
     * @param PropertyReference $relationReference
     */
    public function attachRelation(string $identifier, PropertyReference $relationReference)
    {
        foreach ($this->repositories as $repository) {
            $repository->attachRelation($identifier, $relationReference);
        }
    }

    /**
     * @param string $identifier
     * @param PropertyReference $relationReference
     */
    public function removeRelation(string $identifier, PropertyReference $relationReference)
    {
        foreach ($this->repositories as $repository) {
            $repository->removeRelation($identifier, $relationReference);
        }
    }

    /**
     * @param string $identifier
     * @param RelationSequence $sequence
     */
    public function orderRelations(string $identifier, RelationSequence $sequence)
    {
        foreach ($this->repositories as $repository) {
            $repository->orderRelations($identifier, $sequence);
        }
    }
}
