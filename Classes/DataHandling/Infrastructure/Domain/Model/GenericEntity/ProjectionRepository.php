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

use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Event;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Meta\PropertyReference;
use TYPO3\CMS\DataHandling\DataHandling\Domain\Model\GenericEntity\Aspect\Sequence\RelationSequence;

interface ProjectionRepository
extends \TYPO3\CMS\EventSourcing\Infrastructure\Domain\Model\Base\ProjectionRepository
{
    /**
     * @param string $identifier
     */
    public function remove(string $identifier);

    /**
     * @param string $identifier
     */
    public function purge(string $identifier);

    /**
     * @param string $identifier
     * @param PropertyReference $relationReference
     */
    public function attachRelation(string $identifier, PropertyReference $relationReference);

    /**
     * @param string $identifier
     * @param PropertyReference $relationReference
     */
    public function removeRelation(string $identifier, PropertyReference $relationReference);

    /**
     * @param string $identifier
     * @param RelationSequence $sequence
     */
    public function orderRelations(string $identifier, RelationSequence $sequence);
}
